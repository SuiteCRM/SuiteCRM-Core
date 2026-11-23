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

import {fakeAsync, tick} from '@angular/core/testing';
import {Subject} from 'rxjs';
import {SeriesResult} from '../../../../common/containers/chart/chart.model';
import {PieGridChartComponent} from './pie-grid-chart.component';

describe('PieGridChartComponent', () => {
    it('converts numeric string values and clears results on an empty update', fakeAsync(() => {
        const results = new Subject<SeriesResult>();
        const component = new PieGridChartComponent(null, null, null);
        component.dataSource = {
            options: {minWidth: 200},
            getResults: () => results,
            tooltipFormatting: value => value
        };
        component.ngOnInit();
        try {
            expect(component.minWidth).toBe(200);
            results.next({singleSeries: [{name: 'New', value: '12.5'}, {name: 'Closed', value: 0}]});
            tick(500);
            expect(component.results).toEqual([{name: 'New', value: 12.5}, {name: 'Closed', value: 0}]);
            results.next({singleSeries: []});
            tick(500);
            expect(component.results).toEqual([]);
            expect(component.height).toBe(50);
        } finally {
            component.ngOnDestroy();
        }
    }));
});
