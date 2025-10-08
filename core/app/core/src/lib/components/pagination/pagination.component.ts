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

import {Component, Input, OnInit} from '@angular/core';
import {combineLatestWith, Observable} from 'rxjs';
import {map} from 'rxjs/operators';
import {PaginationDataSource} from '../../common/components/pagination/pagination.model';
import {PageSelection, PaginationCount} from '../../common/views/list/list-navigation.model';
import {LanguageStore, LanguageStringMap} from '../../store/language/language.store';

export interface PaginationViewModel {
    appStrings: LanguageStringMap;
    pageCount: PaginationCount;
}

@Component({
    selector: 'scrm-pagination',
    templateUrl: 'pagination.component.html'
})
export class PaginationComponent implements OnInit {

    @Input() allowPagination = true;
    @Input() state: PaginationDataSource;
    displayResponsiveTable: any;

    appStrings$: Observable<LanguageStringMap> = this.languageStore.appStrings$;
    vm$: Observable<PaginationViewModel> = null;

    constructor(protected languageStore: LanguageStore) {
    }

    ngOnInit(): void {
        const pageCount$ = this.state.getPaginationCount();

        this.vm$ = this.appStrings$.pipe(
            combineLatestWith(pageCount$),
            map(([appStrings, pageCount]: [LanguageStringMap, PaginationCount]) => ({appStrings, pageCount}))
        );
    }

    first(): void {
        this.state.changePage(PageSelection.FIRST);
    }

    previous(): void {
        this.state.changePage(PageSelection.PREVIOUS);
    }

    next(): void {
        this.state.changePage(PageSelection.NEXT);
    }

    last(): void {
        this.state.changePage(PageSelection.LAST);
    }
}
