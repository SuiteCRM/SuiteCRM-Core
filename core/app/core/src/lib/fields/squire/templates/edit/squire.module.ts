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

import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {EditorModule} from '@tinymce/tinymce-angular';
import {SquireEditFieldComponent} from "./squire.component";
import {ButtonModule} from "../../../../components/button/button.module";
import {DropdownButtonModule} from "../../../../components/dropdown-button/dropdown-button.module";
import {LabelModule} from "../../../../components/label/label.module";
import {PopupButtonModule} from "../../../../components/popups/components/popup-button/popup-button.module";
import {ColorSelectorComponent} from "../../../../components/popups/components/color-selector/color-selector.component";
import {
    InsertLinkPopupButtonComponent
} from "../../../../components/popups/components/insert-link-popup-button/insert-link-popup-button.component";
import {MonacoEditorComponent} from "../../../../components/monaco-editor/monaco-editor.component";

@NgModule({
    declarations: [SquireEditFieldComponent],
    exports: [SquireEditFieldComponent],
    imports: [
        CommonModule,
        FormsModule,
        EditorModule,
        ReactiveFormsModule,
        ButtonModule,
        DropdownButtonModule,
        LabelModule,
        PopupButtonModule,
        ColorSelectorComponent,
        InsertLinkPopupButtonComponent,
        MonacoEditorComponent,
    ],
    providers: []
})
export class SquireEditFieldModule {
}
