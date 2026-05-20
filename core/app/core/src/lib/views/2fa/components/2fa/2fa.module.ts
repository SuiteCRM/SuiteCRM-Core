/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
import {TwoFactorComponent} from "./2fa.component";
import {CommonModule} from "@angular/common";
import {RouterModule} from "@angular/router";
import {LabelModule} from "../../../../components/label/label.module";
import {ImageModule} from "../../../../components/image/image.module";
import {ModuleTitleModule} from '../../../../components/module-title/module-title.module';
import {StatusBarModule} from "../../../../components/status-bar/status-bar.module";
import {FieldModule} from "../../../../fields/field.module";
import {ButtonModule} from "../../../../components/button/button.module";
import {SvgIconComponent} from "angular-svg-icon";
import {HtmlSanitizeModule} from "../../../../pipes/html-sanitize/html-sanitize.module";
import {TrustHtmlModule} from "../../../../pipes/trust-html/trust-html.module";
import {PaginatorModule} from "primeng/paginator";
import {TwoFactorCheckModule} from "../2fa-check/2fa-check.module";
import {RecordThreadModule} from "../../../../containers/record-thread/components/record-thread/record-thread.module";
import {WidgetPanelModule} from "../../../../components/widget-panel/widget-panel.module";
import {FormsModule} from "@angular/forms";

@NgModule({
    declarations: [
        TwoFactorComponent
    ],
    exports: [
        TwoFactorComponent
    ],
    imports: [
        CommonModule,
        RouterModule,
        ModuleTitleModule,
        LabelModule,
        ImageModule,
        StatusBarModule,
        FieldModule,
        ButtonModule,
        SvgIconComponent,
        HtmlSanitizeModule,
        TrustHtmlModule,
        PaginatorModule,
        TwoFactorCheckModule,
        RecordThreadModule,
        WidgetPanelModule,
        FormsModule
    ]
})
export class TwoFactorModule {
}
