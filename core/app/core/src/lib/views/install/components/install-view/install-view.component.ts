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

import {Component, OnDestroy, OnInit} from '@angular/core';
import {Observable} from 'rxjs';
import {ActivatedRoute} from '@angular/router';
import {ViewMode} from '../../../../common/views/view.model';
import {InstallViewStore} from '../../store/install-view/install-view.store';
import {InstallViewModel} from '../../store/install-view/install-view.store.model';

@Component({
    selector: 'scrm-install-view',
    templateUrl: './install-view.component.html',
    styleUrls: [],
    providers: [InstallViewStore]
})
export class InstallViewComponent implements OnInit, OnDestroy {

    vm$: Observable<InstallViewModel> = null;
    showStatusBar = false;

    constructor(
        protected store: InstallViewStore,
        private route: ActivatedRoute
    ) {
    }

    ngOnInit(): void {
        let mode = 'edit' as ViewMode;
        const data = (this.route.snapshot && this.route.snapshot.data) || {};

        if (data.mode) {
            mode = data.mode;
        }

        this.store.init(mode);
        this.vm$ = this.store.vm$;
    }

    ngOnDestroy(): void {
        this.store.clear();
    }
}
