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

import {Component, EventEmitter, Input, OnInit, Output, signal, WritableSignal} from '@angular/core';
import {ButtonModule} from "../button/button.module";
import {ImageModule} from "../image/image.module";
import {FileSizePipe} from "../../pipes/file-size/file-size.pipe";
import {ButtonInterface} from "../../common/components/button/button.model";
import {UploadedFile} from "./uploaded-file.model";
import {NgIf} from "@angular/common";
import {LabelModule} from "../label/label.module";
import {animate, style, transition, trigger} from "@angular/animations";


@Component({
    selector: 'scrm-uploaded-file',
    standalone: true,
    imports: [
        ButtonModule,
        ImageModule,
        FileSizePipe,
        NgIf,
        LabelModule
    ],
    templateUrl: './uploaded-file.component.html',
    animations: [
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
export class UploadedFileComponent implements OnInit {

    @Input() file: UploadedFile;
    @Input() maxTextWidth: string;
    @Input() minWidth: string = '0px';
    @Input() allowClear: boolean = true;
    @Input() savedIcon: string = 'file-earmark-arrow-down';
    @Input() uploadingIcon: string = 'file-earmark-arrow-up';
    @Input() uploadedIcon: string = 'file-earmark-check';
    @Input() errorIcon: string = 'file-earmark-x';
    @Input() compact: boolean = false;
    @Input() displaySize: boolean = true;
    @Output('clear') clear: EventEmitter<UploadedFile> = new EventEmitter<UploadedFile>();
    clearButtonConfig: ButtonInterface;
    errorClearButtonConfig: ButtonInterface;
    uploadedFile: WritableSignal<UploadedFile> = signal(null);
    textMaxWidth: WritableSignal<string> = signal('200px');

    constructor() {
    }

    ngOnInit(): void {
        this.buildClearButtonConfig();
        this.initMaxWidth();

        if (this.file) {
            this.uploadedFile.set(this.file);
        }
    }


    protected initMaxWidth(): void {
        if (this.maxTextWidth) {
            this.textMaxWidth.set(this.maxTextWidth);
        } else {
            this.textMaxWidth.set('200px');
        }
    }

    protected buildClearButtonConfig(): void {
        this.clearButtonConfig = {
            klass: 'btn btn-sm m-0 border-0 upload-clear-button',
            icon: 'x-circle',
            onClick: () => {
                this.clear.emit(this.uploadedFile());
            }
        } as ButtonInterface;

        this.errorClearButtonConfig = {
            klass: 'btn btn-sm m-0 border-0 upload-clear-button upload-clear-button-error',
            icon: 'x-circle',
            onClick: () => {
                this.clear.emit(this.uploadedFile());
            }
        } as ButtonInterface;
    }
}
