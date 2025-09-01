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
import {UploadedFile} from "../../components/uploaded-file/uploaded-file.model";

@Component({template: ''})
export class BaseAttachmentComponent extends BaseFileComponent {

    breakpoint: number;
    compact: boolean;
    chunks: number;
    popoverMaxTextLength: string;
    popoverMinWidth: string;
    maxTextWidth: string;
    minWidth: string;
    storageType: string;
    ignoreLimit: boolean;
    limitConfigKey: string;

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjects, legacyEntrypointLinkBuilder);
    }

    clearFile(event) {
        const updatedFiles = this.uploadedFiles().filter((file) => file.id !== event.id);
        this.uploadedFiles.set(updatedFiles);
    }

    initUploadedFiles() {
        const files = this.field.valueList ?? {};

        const uploadedFiles: UploadedFile[] = [];

        Object.values(files).forEach((file) => {
            const mapped = this.mapFile(file);
            uploadedFiles.push(mapped);
        })

        this.uploadedFiles.set(uploadedFiles);
    }

    mapFile(file): UploadedFile {
        return {
            id: file?.id ?? '',
            name: file?.attributes?.original_name ?? '',
            size: file?.attributes?.size ?? 0,
            type: file?.attributes?.type ?? '',
            contentUrl: file?.attributes?.contentUrl ?? '',
            status: signal('saved'),
            progress: signal(100),
            dateCreated: file?.attributes?.date_entered || ''
        } as UploadedFile
    }

    protected getValuesFromMetadata() {
        const metadata = this.field.metadata ?? {};
        this.breakpoint = metadata?.breakpoint ?? 3;
        this.chunks = metadata?.maxPerRow ?? 3;
        this.compact = metadata?.compact ?? false;
        this.popoverMaxTextLength = metadata?.popoverMaxTextLength ?? '';
        this.popoverMinWidth = metadata?.popoverMinWidth ?? '';
        this.storageType = this.field.metadata.storage_type ?? 'private-document';
        this.maxTextWidth = metadata?.maxTextWidth ?? '';
        this.minWidth = metadata?.minWidth ?? '';
        this.limitConfigKey = metadata.limitConfigKey ?? 'recordview_attachments_limit';
        this.ignoreLimit = metadata.ignoreLimit ?? false;
    }
}
