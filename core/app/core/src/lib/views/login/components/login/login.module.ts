/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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
import {FormsModule} from '@angular/forms';
import {RouterModule} from '@angular/router';
import {LoginUiComponent} from './login.component';
import {LoginUiRoutes} from './login.routes';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {ButtonLoadingUiModule} from '../../../../directives/button-loading/button-loading.module';
import {LogoUiModule} from '../../../../components/logo/logo.module';
import {ImageModule} from '../../../../components/image/image.module';
import {TwoFactorCheckModule} from "../../../2fa/components/2fa-check/2fa-check.module";

@NgModule({
    declarations: [
        LoginUiComponent
    ],
    exports: [
        LoginUiComponent
    ],
    imports: [
        FormsModule,
        LogoUiModule,
        RouterModule.forChild(LoginUiRoutes),
        CommonModule,
        AngularSvgIconModule,
        ImageModule,
        ButtonLoadingUiModule,
        TwoFactorCheckModule
    ]
})
export class LoginUiModule {
}
