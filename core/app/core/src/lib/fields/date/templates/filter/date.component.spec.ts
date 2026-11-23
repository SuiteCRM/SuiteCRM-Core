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

import {UntypedFormControl} from '@angular/forms';
import {Injector, runInInjectionContext} from '@angular/core';
import {FieldHandlerRegistry} from '../../../../services/record/field/handler/field-handler.registry';
import {fieldHandlerRegistry} from '../../../../services/record/record.manager.spec.mock';
import {DateAdapter} from '../../../base/datetime/date/date-adapter.service';
import {DateParserFormatter} from '../../../base/datetime/date/date-parser-formatter.service';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {dataTypeFormatterMock} from '../../../../services/formatters/data-type.formatter.spec.mock';
import {DateFilterFieldComponent} from './date.component';

describe('DateFilterFieldComponent', () => {
    it('loads the date and stores changes in the internal format', () => {
        const injector = Injector.create({providers: [{provide: FieldHandlerRegistry, useValue: fieldHandlerRegistry}]});
        const component = runInInjectionContext(injector, () => new DateFilterFieldComponent(
            dateFormatterMock,
            new DateAdapter(dateFormatterMock),
            new DateParserFormatter(dateFormatterMock),
            dataTypeFormatterMock,
            null,
            null
        ));
        component.field = {
            type: 'date',
            value: '',
            criteria: {operator: '=', values: ['2020-05-01']},
            metadata: {date_time_format: 'dd.MM.yyyy'},
            formControl: new UntypedFormControl()
        };

        component.ngOnInit();
        try {
            expect(component.dateModel).toEqual({year: 2020, month: 5, day: 1});
            expect(component.field.formControl.value).toBe('01.05.2020');
            component.field.formControl.setValue('02.05.2020');
            expect(component.field.value).toBe('2020-05-02');
            expect(component.field.criteria.values).toEqual(['2020-05-02']);
        } finally {
            component.ngOnDestroy();
        }
    });
});
