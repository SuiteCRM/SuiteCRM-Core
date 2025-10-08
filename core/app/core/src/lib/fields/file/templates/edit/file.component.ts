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

import {Component, HostListener, signal, ViewChild, WritableSignal} from '@angular/core';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from "../../../field-logic-display/field-logic-display.manager";
import {UploadedFile} from "../../../../components/uploaded-file/uploaded-file.model";
import {MediaObjectsService} from "../../../../services/media-objects/media-objects.service";
import {BaseFileComponent} from "../../../base/base-file.component";
import {FileUploadAreaComponent} from "../../../../components/file-upload-area/file-upload-area.component";
import {isEqual} from "lodash-es";
import {Record} from "../../../../common/record/record.model";
import {
    LegacyEntrypointLinkBuilder
} from "../../../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";

@Component({
    selector: 'scrm-file-edit',
    templateUrl: './file.component.html',
    styleUrls: []
})
export class FileEditFieldComponent extends BaseFileComponent {

    @HostListener('window:resize', ['$event'])
    onResize(): void {
        this.calculateDynamicMaxWidth();
    }

    @ViewChild('uploadArea') uploadArea: FileUploadAreaComponent;
    @ViewChild('wrapper') wrapper: HTMLDivElement;

    displayUploadArea: WritableSignal<boolean> = signal(true);
    textMaxWidth: WritableSignal<string> = signal('200px');
    protected storageType: string = '';

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjects, legacyEntrypointLinkBuilder);
    }

    ngOnInit() {
        this.storageType = this?.field?.metadata?.storage_type ?? '';
        this.compact = this.field.metadata?.compact ?? false;
        if (this.validStorageTypes.includes(this.storageType)) {
            this.isValidStorageType = true;
        }
        this.initUploadedFile();
    }

    protected initUploadedFile(): void {

        super.initUploadedFile();

        if (this.field.valueObject && this.field.valueObject.id) {
            this.displayUploadArea.set(false);
        }
    }


    onFileAdd(files: FileList) {
        const uploadedField = this.uploadFile(
            this.storageType,
            files[0],
            (uploadFile: UploadedFile) => {
                this.setValue(uploadFile)
                this.uploadedFile.set(uploadFile);
            }
        );
        this.uploadedFile.set(uploadedField ?? null);
        this.displayUploadArea.set(false);
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
        this.uploadArea.resetUploadArea();
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

    protected calculateDynamicMaxWidth(): void {

        const ancestorSelector = this?.field?.metadata?.dynamicWidthAncestor ?? 'scrm-file-edit'
        const dynamicWidthAdjustment = 30;
        let containerWidth = '';

        const ancestor = this.findAncestor(this?.wrapper, ancestorSelector);
        if (ancestor) {
            let offSetWidth = ancestor?.offsetWidth ?? 0;

            if (offSetWidth && dynamicWidthAdjustment) {
                offSetWidth = offSetWidth - dynamicWidthAdjustment;
            }
            containerWidth = (offSetWidth).toString();
        }

        if (containerWidth) {
            containerWidth = containerWidth + 'px';
        } else {
            containerWidth = this?.field?.metadata?.width ?? '200px';
        }

        this.textMaxWidth.set(containerWidth)
    }

    protected findAncestor(el: HTMLElement, selector: string) {
        let found = false;
        let iterations = 0;

        while (!found || iterations > 50) {
            el = el?.parentElement ?? null;
            if (!el) {
                found = true;
                break;
            }

            if (el.matches(selector)) {
                found = true;
            }
            iterations++;
        }

        if (!found) {
            el = null;
        }

        return el;
    }
}
