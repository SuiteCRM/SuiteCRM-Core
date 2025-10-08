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

import {PageSelection} from '../../../../common/views/list/list-navigation.model';
import {SelectionStatus} from '../../../../common/views/list/record-selection.model';
import {SearchCriteriaFieldFilter} from '../../../../common/views/list/search-criteria.model';
import {take} from 'rxjs/operators';
import {ListViewStore} from './list-view.store';
import {listviewMockData, listviewStoreMock} from './list-view.store.spec.mock';
import {localStorageServiceMock} from '../../../../services/local-storage/local-storage.service.spec.mock';
import {SavedFilter} from '../../../../store/saved-filters/saved-filter.model';

describe('Listview Store', () => {
    const service: ListViewStore = listviewStoreMock;

    beforeEach(() => {
    });

    it('#load', (done: DoneFn) => {
        service.recordList.updateSelection(SelectionStatus.NONE);
        service.init('accounts').subscribe(data => {
            expect(data.records[0]).toEqual(jasmine.objectContaining(listviewMockData.recordList.records[0]));
            done();
        });
    });

    it('#updateSelection select all', () => {
        service.recordList.updateSelection(SelectionStatus.NONE);
        service.recordList.updateSelection(SelectionStatus.ALL);
        service.selection$.pipe(take(1)).subscribe(data => {
            expect(data.all).toEqual(true);
            expect(data.status).toEqual(SelectionStatus.ALL);
            expect(data.selected).toEqual({});
        }).unsubscribe();
    });

    it('#updateSelection select page', () => {
        service.recordList.updateSelection(SelectionStatus.NONE);
        service.init('accounts').subscribe(() => {
            service.recordList.updateSelection(SelectionStatus.PAGE);
            service.selection$.pipe(take(1)).subscribe(selection => {
                expect(selection.all).toEqual(false);
                expect(selection.status).toEqual(SelectionStatus.SOME);
                expect(selection.selected).toEqual({'29319818-dc26-f57d-03e1-5ed77dedd691': '29319818-dc26-f57d-03e1-5ed77dedd691'});
                expect(selection.count).toEqual(1);
            }).unsubscribe();
        }).unsubscribe();

    });

    it('#updateSelection deselect all', () => {
        service.recordList.updateSelection(SelectionStatus.NONE);
        service.selection$.pipe(take(1)).subscribe(data => {
            expect(data.all).toEqual(false);
            expect(data.status).toEqual(SelectionStatus.NONE);
            expect(data.selected).toEqual({});
            expect(data.count).toEqual(0);
        }).unsubscribe();
    });

    it('#updateSelection toggleSelection', () => {
        service.recordList.updateSelection(SelectionStatus.NONE);
        service.recordList.toggleSelection('ac319818-dc26-f57d-03e1-5ed77dedd691');

        service.selection$.pipe(take(1)).subscribe(data => {
            expect(data.status).toEqual(SelectionStatus.SOME);
            expect(data.selected).toEqual({
                'ac319818-dc26-f57d-03e1-5ed77dedd691': 'ac319818-dc26-f57d-03e1-5ed77dedd691',
            });

            expect(data.count).toEqual(1);

            service.recordList.toggleSelection('ac319818-dc26-f57d-03e1-5ed77dedd691');
            service.selection$.pipe(take(1)).subscribe(newData => {
                expect(newData.status).toEqual(SelectionStatus.NONE);
                expect(newData.selected).toEqual({});
                expect(newData.count).toEqual(0);
            }).unsubscribe();
        }).unsubscribe();


    });

    it('#changePage next', () => {
        service.init('accounts').pipe(take(1)).subscribe(() => {
            service.recordList.changePage(PageSelection.FIRST);
            const before = {...service.recordList.getPagination()};
            service.recordList.changePage(PageSelection.NEXT);
            const after = service.recordList.getPagination();

            expect(before.next).toEqual(after.current);
        });
    });

    it('#changePage previous', () => {
        service.init('accounts').pipe(take(1)).subscribe(() => {
            service.recordList.changePage(PageSelection.LAST);
            const before = {...service.recordList.getPagination()};
            service.recordList.changePage(PageSelection.PREVIOUS);
            const after = service.recordList.getPagination();

            expect(before.previous).toEqual(after.current);
        });
    });

    it('#updateCriteria with reload', () => {
        service.init('accounts').pipe(take(1)).subscribe(() => {

            const filters = service.activeFilters;
            filters.default = {
                key: 'default',
                module: 'saved-search',
                criteria: {
                    filters: {
                        name: {
                            operator: '=',
                            values: ['test 2']
                        } as SearchCriteriaFieldFilter
                    }
                },
                attributes: {}
            } as SavedFilter;

            const criteria = filters.default.criteria;

            service.setFilters(filters);
            const savedCriteria = service.recordList.criteria;
            const localStorageCriteria = localStorageServiceMock.get('active-filters').accounts;

            expect(criteria).toEqual(savedCriteria);
            expect(criteria).toEqual(localStorageCriteria.default.criteria);
        });
    });

    it('#updateCriteria without reload', () => {
        service.init('accounts').pipe(take(1)).subscribe(() => {

            const filters = service.activeFilters;
            filters.default.criteria.filters.name = {
                operator: '=',
                values: ['test 2']
            };

            const criteria = filters.default.criteria;

            service.setFilters(filters, false);
            const savedCriteria = service.recordList.criteria;
            const localStorageCriteria = localStorageServiceMock.get('active-filters').accounts;

            expect(criteria).toEqual(savedCriteria);
            expect(criteria).not.toEqual(localStorageCriteria.criteria);
        });
    });
});

