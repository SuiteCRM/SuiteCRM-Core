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

import {Component, signal} from '@angular/core';
import {DataTypeFormatter} from "../../services/formatters/data-type.formatter.service";
import {FieldLogicManager} from "../field-logic/field-logic.manager";
import {FieldLogicDisplayManager} from "../field-logic-display/field-logic-display.manager";
import {BaseFileComponent} from "./base-file.component";
import {MediaObjectsService} from "../../services/media-objects/media-objects.service";
import {
    LegacyEntrypointLinkBuilder
} from "../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";
import {Attachment} from "../../components/uploaded-file/uploaded-file.model";
import {SystemConfigStore} from "../../store/system-config/system-config.store";
import {Record} from "../../common/record/record.model";

@Component({template: ''})
export class BaseAttachmentComponent extends BaseFileComponent {

    breakpoint: number;
    compact: boolean;
    chunks: number;
    minWidth: string;
    popoverLinkPosition: string;
    storageType: string;

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder,
        protected systemConfig: SystemConfigStore
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjects, legacyEntrypointLinkBuilder);
    }

    clearFile(event) {

        const id = event?.id ?? '';

        if (id === '') {
            const updatedFiles = this.attachments().filter((file) => file.sourceRecordId !== event.sourceRecordId);
            this.field.valueObject = Object.values(this.field.valueObject).filter((file: Record) => file.attributes.source_record_id !== event.sourceRecordId);
            this.field.valueList = this.field.valueObject;
            this.attachments.set(updatedFiles);
            return;
        }

        const updatedFiles = this.attachments().filter((file) => file.id !== event.id);
        this.field.valueObject = Object.values(this.field.valueObject).filter((file: Record) => file.id !== event.id);
        this.field.valueList = this.field.valueObject;
        this.attachments.set(updatedFiles);
    }

    initAttachments() {
        this.field.valueObject = this.field?.valueList ?? this.field?.valueObject ?? {};

        const uploadedFiles: Attachment[] = [];

        Object.values(this.field.valueObject).forEach((file) => {
            const mapped = this.mapFile(file);
            uploadedFiles.push(mapped);
        })

        this.attachments.set(uploadedFiles);
    }

    mapFile(file): Attachment {

        if (!file?.attributes?.attachmentType && !file?.attributes?.source_record_id) {
            file.attributes.attachmentType = 'file';
        }

        let contentUrl = file?.attributes?.contentUrl ?? '';
        if (contentUrl && (!contentUrl.startsWith('https://') && !contentUrl.startsWith('http://')) && !contentUrl.startsWith('.')) {
            contentUrl = '.' + contentUrl ?? '';
        }

        const status = file?.attributes?.status ?? 'saved';

        return {
            id: file?.id ?? '',
            name: file?.attributes?.original_name ?? '',
            size: file?.attributes?.size ?? 0,
            type: file?.attributes?.type ?? '',
            attachmentType: file?.attributes?.attachmentType ?? 'file',
            sourceRecordId: file?.attributes?.source_record_id ?? '',
            contentUrl: contentUrl,
            status: signal(status),
            errorMessage: signal(file.attributes?.errorMessage ?? ''),
            attachmentIcon: file?.attributes?.attachmentIcon ?? '',
            errorLabelKey: file?.attributes?.errorLabelKey ?? '',
            progress: signal(100),
            dateCreated: file?.attributes?.date_entered || ''
        } as Attachment;
    }

    protected getValuesFromMetadata(mode: string): void {

        const config = this.systemConfig.getUi('attachments') ?? {};
        const modeConfig = config[mode] ?? {};

        const metadata = this.field.metadata ?? {};
        this.breakpoint = metadata?.breakpoint ?? modeConfig['breakpoint'] ?? null;
        this.chunks = metadata?.maxPerRow ?? modeConfig['maxPerRow'] ?? null;
        this.compact = metadata?.compact ?? modeConfig['compact'] ?? false;
        this.popoverLinkPosition = metadata?.popoverLinkPosition ?? modeConfig['popoverLinkPosition'] ?? 'bottom';
        this.storageType = this.field.metadata.storage_type ?? 'private-documents';
        this.minWidth = metadata?.minWidth ?? modeConfig['minWidth'] ?? '185px';
    }

    protected mapToRecord(uploadFile: Attachment): Record {
        return {
            id: uploadFile?.id ?? uploadFile?.name ?? '',
            module: 'media-objects',
            attributes: {
                id: uploadFile?.id ?? uploadFile?.name ?? '',
                name: uploadFile?.name ?? '',
                attachmentType: uploadFile?.attachmentType ?? 'file',
                errorLabelKey: uploadFile?.errorLabelKey ?? '',
                source_record_id: uploadFile?.sourceRecordId ?? '',
                size: uploadFile?.size ?? '',
                type: uploadFile?.type ?? '',
                status: uploadFile?.status() ?? 'saved',
                contentUrl: uploadFile?.contentUrl ?? '',
                original_name: uploadFile?.name ?? '',
            }
        } as Record;
    }
}
