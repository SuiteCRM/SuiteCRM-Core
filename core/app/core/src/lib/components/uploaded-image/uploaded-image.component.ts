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


import {Component, EventEmitter, Input, OnInit, Output} from "@angular/core";
import {UploadedFile} from "../uploaded-file/uploaded-file.model";
import {NgIf} from "@angular/common";
import {ButtonModule} from "../button/button.module";
import {ButtonInterface} from "../../common/components/button/button.model";
import {LabelModule} from "../label/label.module";
import {ImageModule} from "../image/image.module";

@Component({
    selector: 'scrm-uploaded-image',
    standalone: true,
    imports: [
        NgIf,
        ButtonModule,
        LabelModule,
        ImageModule
    ],
    templateUrl: './uploaded-image.component.html',
})
export class UploadedImageComponent implements OnInit {
    @Input() file: UploadedFile;
    @Input() maxHeight: string = '100px';
    @Input() allowClear: boolean = true;

    @Output('clear') clear: EventEmitter<UploadedFile> = new EventEmitter<UploadedFile>();

    clearButtonConfig: ButtonInterface;
    errorClearButtonConfig: ButtonInterface;

    ngOnInit() {
        this.buildClearButtonConfig()
    }

    protected buildClearButtonConfig(): void {
        this.clearButtonConfig = {
            klass: 'btn btn-sm m-0 border-0 upload-clear-button',
            icon: 'x-circle',
            onClick: () => {
                this.clear.emit();
            }
        } as ButtonInterface;

        this.errorClearButtonConfig = {
            klass: 'btn btn-sm m-0 border-0 upload-clear-button upload-clear-button-error',
            icon: 'x-circle',
            onClick: () => {
                this.clear.emit();
            }
        } as ButtonInterface;
    }

}
