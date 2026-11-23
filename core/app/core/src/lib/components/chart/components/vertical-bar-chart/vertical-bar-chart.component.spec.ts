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

import {of} from 'rxjs';
import {LanguageStore} from '../../../../store/language/language.store';
import {VerticalBarChartComponent} from './vertical-bar-chart.component';

describe('VerticalBarChartComponent', () => {
    let component: VerticalBarChartComponent;

    beforeEach(() => {
        const language = jasmine.createSpyObj<LanguageStore>('LanguageStore', ['getFieldLabel']);
        language.getFieldLabel.and.returnValue('');
        language.getFieldLabel.withArgs('LBL_LEGEND').and.returnValue('Legend');
        component = new VerticalBarChartComponent(null, null, language);
    });

    it('uses chart defaults when no options are set', () => {
        component.dataSource = {options: {}, getResults: () => of({}), tooltipFormatting: undefined};

        expect(component.scheme).toBe('picnic');
        expect(component.xAxis).toBeFalse();
        expect(component.yAxis).toBeTrue();
        expect(component.legendTitle).toBe('Legend');
        expect(component.formatTooltipValue(25)).toBe(25);
    });

    it('applies configured axis and formatting options', () => {
        component.dataSource = {
            options: {xAxis: 'true', yAxis: 'false', maxXAxisTickLength: '8', yAxisTickFormatting: true},
            getResults: () => of({}),
            tickFormatting: (value: number) => `$${value}`,
            tooltipFormatting: value => `Total: ${value}`
        };

        expect(component.xAxis).toBeTrue();
        expect(component.yAxis).toBeFalse();
        expect(component.maxXAxisTickLength).toBe(8);
        expect(component.yAxisTickFormatting(25)).toBe('$25');
        expect(component.formatTooltipValue(25)).toBe('Total: 25');
    });
});
