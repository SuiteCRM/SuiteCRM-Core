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
import {AfterViewInit, Component, OnInit, signal, ViewChild, WritableSignal} from "@angular/core";
import {FileUploadAreaComponent} from "../../../../components/file-upload-area/file-upload-area.component";
import {BaseAttachmentComponent} from "../../../base/base-attachment.component";
import {DropdownButtonInterface} from "../../../../common/components/button/dropdown-button.model";
import {MediaObjectsService} from "../../../../services/media-objects/media-objects.service";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {UploadedFile} from "../../../../components/uploaded-file/uploaded-file.model";
import {DataTypeFormatter} from "../../../../services/formatters/data-type.formatter.service";
import {FieldLogicManager} from "../../../field-logic/field-logic.manager";
import {FieldLogicDisplayManager} from "../../../field-logic-display/field-logic-display.manager";
import {
    LegacyEntrypointLinkBuilder
} from "../../../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";
import {DropdownButtonComponent} from "../../../../components/dropdown-button/dropdown-button.component";

@Component({
    selector: 'scrm-attachments-edit',
    templateUrl: './attachment.component.html',
    styles: [],
})
export class AttachmentEditFieldComponent extends BaseAttachmentComponent implements OnInit, AfterViewInit {

    @ViewChild('uploadArea') uploadArea: FileUploadAreaComponent;
    @ViewChild('dropdownButtonComponent') dropdownButtonComponent: DropdownButtonComponent;
    @ViewChild('wrapper') wrapper: HTMLElement;

    dropdownButton: DropdownButtonInterface;
    dropdownButtonEl: HTMLElement;

    displayUploadArea: WritableSignal<boolean> = signal(false);

    ngOnInit() {
        super.ngOnInit();
        this.getValuesFromMetadata();

        if (this.validStorageTypes.includes(this.storageType)) {
            this.isValidStorageType = true;
        }

        this.buildButtonItems();
        this.initUploadedFiles();
    }

    ngAfterViewInit() {
        this.dropdownButtonEl = this.dropdownButtonComponent?.dropdownButtonDiv?.nativeElement;
    }

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjectsService: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder,
        protected systemConfigs: SystemConfigStore
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjectsService, legacyEntrypointLinkBuilder);
    }

    onFileAdd(files: FileList) {
        if (!files || files.length === 0) {
            return;
        }

        Object.values(files).forEach((file) => {
            this.mediaObjectsService.uploadFile(
                this.storageType,
                file,
                this?.record?.module ?? '',
                this?.field?.name ?? '',
                () => {
                },
                (uploadFile: UploadedFile) => {
                    const uploadedFiles = [uploadFile, ...this.uploadedFiles() ?? []];
                    this.setValue(uploadedFiles);
                    this.uploadedFiles.set(uploadedFiles);
                },
                () => {
                }
            );
        })

        this.displayUploadArea.set(false);
    }

    buildButtonItems(): void {
        this.dropdownButton = {
            labelKey: 'LBL_EMAIL_ATTACHMENT',
            icon: 'paperclip',
            klass: 'btn-sm btn btn-outline-main',
            items: [{
                label: 'Upload from files',
                klass: 'btn-outline-main rounded',
                onClick: (): void => {
                    this.uploadArea.triggerFileInput()
                }
            },],

        } as DropdownButtonInterface
    }

    protected setValue(uploadFile: UploadedFile[]): void {

        if (!uploadFile || !uploadFile.length) {
            this.field.valueObject = null;
            this.setFormControlValue(null);
            return;
        }

        let mapped = [];

        Object.values(uploadFile).forEach((file) => {
            const uploadFileRecord = this.mapToRecord(file);
            mapped = [...mapped, uploadFileRecord];
        })

        this.field.valueObject = mapped;
        this.setFormControlValue(mapped);
    }

    protected setFormControlValue(newValue: any): void {
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
}
