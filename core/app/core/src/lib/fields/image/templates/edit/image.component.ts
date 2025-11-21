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

import {Component, OnInit, signal, WritableSignal} from "@angular/core";
import {MediaObjectsService} from "../../../../services/media-objects/media-objects.service";
import {BaseFileComponent} from "../../../base/base-file.component";
import {DataTypeFormatter} from "../../../../services/formatters/data-type.formatter.service";
import {FieldLogicManager} from "../../../field-logic/field-logic.manager";
import {FieldLogicDisplayManager} from "../../../field-logic-display/field-logic-display.manager";
import {
    LegacyEntrypointLinkBuilder
} from "../../../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";
import {UploadedFile} from "../../../../components/uploaded-file/uploaded-file.model";
import {isEqual} from "lodash-es";
import {Record} from "../../../../common/record/record.model";
import {animate, style, transition, trigger} from "@angular/animations";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {BaseImageComponent} from "../../../base/base-image.component";

@Component({
    selector: 'scrm-image-edit',
    templateUrl: './image.component.html',
    styles: [],
    animations: [
        trigger('scaleIn', [
            transition(':enter', [
                style({transform: 'scale(0.5)', opacity: 0}),
                animate('200ms cubic-bezier(0.4,0,0.2,1)', style({transform: 'scale(1)', opacity: 1})),
            ]),
        ]),
        trigger('scaleInOut', [
            transition(':enter', [
                style({transform: 'scale(0.5)', opacity: 0}),
                animate('200ms cubic-bezier(0.4,0,0.2,1)', style({transform: 'scale(1)', opacity: 1})),
            ]),
            transition(':leave', [
                animate('200ms cubic-bezier(0.4,0,0.2,1)', style({transform: 'scale(0.5)', opacity: 0})),
            ]),
        ]),
    ],
})
export class ImageEditFieldComponent extends BaseImageComponent implements OnInit {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder,
        protected systemConfigs: SystemConfigStore,
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjects, legacyEntrypointLinkBuilder, systemConfigs);
    }
    displayUploadArea: WritableSignal<boolean> = signal(true);

    validStorageTypes: string[] = [
        'private-images',
        'public-images',
    ];

    ngOnInit() {
        this.storageType = this?.field?.metadata?.storage_type ?? '';
        if (this.validStorageTypes.includes(this.storageType)) {
            this.isValidStorageType = true;
        }
        this.maxHeight = this.getMaxHeight();

        const hasFile = this.field?.valueObject &&
            (this.field.valueObject.id || this.field.valueObject.value);

        if (hasFile) {
            this.loading.set(true);
            this.initUploadedFile();
            this.displayUploadArea.set(false);
            this.setLoading(false, true);
            return;
        }

        this.setLoading(false, true)
    }

    onFileAdd(files: FileList) {
        const uploadedField = this.uploadFile(
            this.storageType,
            files[0],
            (uploadFile: UploadedFile) => {
                this.setValue(uploadFile)
                this.uploadedFile.set(uploadFile);
            },
        );
        this.uploadedFile.set(uploadedField ?? null);
        this.displayUploadArea.set(false);
    }

    protected setValue(uploadFile: UploadedFile): void {
        const uploadFileRecord = this.mapToRecord(uploadFile);

        this.field.valueObject = uploadFileRecord;
        this.setFormControlValue(uploadFileRecord);
    }

    protected setFormControlValue(newValue: any): void {
        if (isEqual(this?.field?.formControl?.value?.id, newValue?.id)) {
            this.field.formControl.markAsPristine();
            return;
        }
        this.field.formControl.setValue(
            newValue,
            {
                emitEvent: false,
                emitModelToViewChange: false,
                emitViewToModelChange: false
            }
        );

        this.field.formControl.markAsDirty();
    }

    clearUpload() {
        this.displayUploadArea.set(true);
        this.uploadedFile.set(null);
        this.setValue({
            id: '',
            module: 'media-objects',
            attributes: {
                id: ''
            }
        } as Record)
    }
}
