/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

import {Component, signal, WritableSignal} from "@angular/core";
import {BaseFieldComponent} from "./base-field.component";
import {UploadedFile} from "../../components/uploaded-file/uploaded-file.model";
import {DataTypeFormatter} from "../../services/formatters/data-type.formatter.service";
import {FieldLogicManager} from "../field-logic/field-logic.manager";
import {FieldLogicDisplayManager} from "../field-logic-display/field-logic-display.manager";
import {MediaObjectsService, UploadSuccessCallback} from "../../services/media-objects/media-objects.service";
import {Record} from "../../common/record/record.model";
import {FieldValue} from "../../common/record/field.model";
import {
    LegacyEntrypointLinkBuilder
} from "../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";


@Component({template: ''})
export class BaseFileComponent extends BaseFieldComponent {

    filenameLink: string = '';

    isLegacy: boolean = true;
    compact: boolean = false;
    uploadedFile: WritableSignal<UploadedFile> = signal(null);
    uploadedFiles: WritableSignal<UploadedFile[]> = signal([]);
    isValidStorageType: boolean = false;

    validStorageTypes: string[] = [
        'archived-documents',
        'private-documents',
        'private-images',
        'public-documents',
        'public-images',
    ];

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    protected uploadFile(storageType: string, file: File, onUpload: UploadSuccessCallback): UploadedFile {

        const uploadedFile = this.mediaObjects.uploadFile(
            storageType,
            file,
            this?.record?.module ?? '',
            this?.field?.name ?? '',
            (progress: number) => {
            },
            (uploadFile: UploadedFile) => {
                onUpload(uploadFile);
            },
            (error) => {
            }
        );

        return uploadedFile;
    }

    protected subscribeValueChanges(): void {
    }

    protected mapToRecord(uploadFile: UploadedFile): Record {
        return {
            id: uploadFile?.id ?? uploadFile?.name ?? '',
            module: 'media-objects',
            attributes: {
                id: uploadFile?.id ?? uploadFile?.name ?? '',
                name: uploadFile?.name ?? '',
                size: uploadFile?.size ?? '',
                type: uploadFile?.type ?? '',
                contentUrl: uploadFile?.contentUrl ?? '',
                original_name: uploadFile?.name ?? '',
            }
        } as Record;
    }

    protected initUploadedFile(): void {
        const id = this.record.id;
        const type = this.record.module;

        if (this.field.valueObject && this.field.valueObject.id) {
            this.isLegacy = false;
            this.initFileFromValueObject(this.field.valueObject);

            this.subs.push(this.field.valueChanges$.subscribe((fieldValue: FieldValue) => {
                this.initFileFromValueObject(this.field.valueObject);
            }));
        }

        this.filenameLink = this.legacyEntrypointLinkBuilder.getDownloadEntrypointLink(id, type);
    }

    protected initFileFromValueObject(valueObject: any): void {

        if (!valueObject) {
            this.uploadedFile.set(null);
            return;
        }

        let contentUrl = valueObject?.attributes?.contentUrl ?? '';

        if (contentUrl && (!contentUrl.startsWith('https://') && !contentUrl.startsWith('http://'))) {
            contentUrl = '.' + contentUrl ?? '';
        }

        this.uploadedFile.set({
            id: valueObject?.id ?? '',
            name: valueObject?.attributes?.original_name ?? '',
            size: valueObject?.attributes?.size ?? 0,
            type: valueObject?.attributes?.type ?? '',
            contentUrl: contentUrl || '',
            status: signal('saved'),
            progress: signal(100),
            dateCreated: valueObject?.attributes?.date_entered || ''
        } as UploadedFile);
    }
}
