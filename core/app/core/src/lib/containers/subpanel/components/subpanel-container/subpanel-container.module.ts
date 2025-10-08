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
import {SubpanelContainerComponent} from './subpanel-container.component';
import {SubpanelModule} from '../subpanel/subpanel.module';
import {RouterModule} from '@angular/router';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {InlineLoadingSpinnerModule} from '../../../../components/inline-loading-spinner/inline-loading-spinner.module';
import {FieldModule} from '../../../../fields/field.module';
import {GridWidgetModule} from '../../../../components/grid-widget/grid-widget.module';
import {LabelModule} from '../../../../components/label/label.module';
import {ImageModule} from '../../../../components/image/image.module';

@NgModule({
    declarations: [SubpanelContainerComponent],
    exports: [SubpanelContainerComponent],
    imports: [
        CommonModule,
        NgbModule,
        ImageModule,
        RouterModule,
        SubpanelModule,
        InlineLoadingSpinnerModule,
        FieldModule,
        GridWidgetModule,
        LabelModule,
    ]
})
export class SubpanelContainerModule {
}
