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

import {TestBed} from '@angular/core/testing';
import {dynamicLabelsMock} from './dynamic-label.service.spec.mock';
import {Field, FieldDefinition} from '../../common/record/field.model';

describe('DynamicLabel', () => {
    const service = dynamicLabelsMock;
    beforeEach(() => TestBed.configureTestingModule({}));

    it('should be created', () => {

        expect(service).toBeTruthy();
    });

    it('should parse several template parts', () => {

        const template = '{{fields.name.label}} | {{fields.amount.value}} | Min: {{context.min|int}}';
        const context = {
            min: '1000',
            module: 'accounts'
        };
        const fields = {
            name: {
                value: 'Some Company',
                labelKey: 'LBL_NAME',
                type: 'varchar'
            } as Field,
            amount: {
                value: '1000.50',
                labelKey: 'LBL_AMOUNT',
                type: 'currency',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = 'Name: | $1,000.50 | Min: 1,000';

        expect(parsed).toContain(expected);
    });

    it('should support int filter', () => {

        const template = '{{context.min|int}}';
        const context = {
            min: '1000',
            module: 'accounts'
        };
        const fields = {};

        const parsed = service.parse(template, context, fields);

        const expected = '1,000';

        expect(parsed).toContain(expected);
    });

    it('should support float filter', () => {

        const template = '{{context.min|float}}';
        const context = {
            min: '1000.50',
            module: 'accounts'
        };
        const fields = {};

        const parsed = service.parse(template, context, fields);

        const expected = '1,000.5';

        expect(parsed).toContain(expected);
    });

    it('should support currency filter', () => {

        const template = '{{context.min|currency}}';
        const context = {
            min: '1000.50',
            module: 'accounts'
        };
        const fields = {};

        const parsed = service.parse(template, context, fields);

        const expected = '$1,000.50';

        expect(parsed).toContain(expected);
    });

    it('should support date filter', () => {

        const template = '{{context.date|date}}';
        const context = {
            date: '2020-12-12',
            module: 'accounts'
        };
        const fields = {};

        const parsed = service.parse(template, context, fields);

        const expected = '12.12.2020';

        expect(parsed).toContain(expected);
    });

    it('should support datetime filter', () => {

        const template = '{{context.date|datetime}}';
        const context = {
            date: '2020-12-12 12:30:40',
            module: 'accounts'
        };
        const fields = {};

        const parsed = service.parse(template, context, fields);

        const expected = '12.12.2020 12.30.40';

        expect(parsed).toContain(expected);
    });

    it('should parse field label', () => {

        const template = '{{fields.name.label}}';
        const context = {
            module: 'accounts'
        };
        const fields = {
            name: {
                value: 'Some Company',
                labelKey: 'LBL_NAME',
                type: 'varchar'
            } as Field,
            amount: {
                value: '1000.50',
                labelKey: 'LBL_AMOUNT',
                type: 'currency',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = 'Name:';

        expect(parsed).toContain(expected);
    });

    it('should parse field currency value', () => {

        const template = '{{fields.amount.value}}';
        const context = {
            module: 'accounts'
        };
        const fields = {
            amount: {
                value: '1000.50',
                labelKey: 'LBL_AMOUNT',
                type: 'currency',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = '$1,000.50';

        expect(parsed).toContain(expected);
    });

    it('should parse field int value', () => {

        const template = '{{fields.amount.value}}';
        const context = {
            module: 'accounts'
        };
        const fields = {
            amount: {
                value: '1000',
                labelKey: 'LBL_AMOUNT',
                type: 'int',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = '1,000';

        expect(parsed).toContain(expected);
    });

    it('should parse field float value', () => {

        const template = '{{fields.amount.value}}';
        const context = {
            module: 'accounts'
        };
        const fields = {
            amount: {
                value: '1000.50',
                labelKey: 'LBL_AMOUNT',
                type: 'float',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = '1,000.5';

        expect(parsed).toContain(expected);
    });

    it('should parse field enum value', () => {

        const template = '{{fields.type.value}}';
        const context = {
            module: 'accounts'
        };
        const fields = {
            type: {
                value: '_analyst',
                labelKey: 'LBL_TYPE',
                definition: {
                    options: 'account_type_dom',
                } as FieldDefinition,
                type: 'enum',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = 'Analyst';

        expect(parsed).toContain(expected);
    });

    it('should parse field dynamicenum value', () => {

        const template = '{{fields.type.value}}';
        const context = {
            module: 'accounts'
        };
        const fields = {
            type: {
                value: '_analyst',
                labelKey: 'LBL_TYPE',
                definition: {
                    options: 'account_type_dom',
                } as FieldDefinition,
                type: 'dynamicenum',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = 'Analyst';

        expect(parsed).toContain(expected);
    });

    it('should parse field multienum value', () => {

        const template = '{{fields.type.value}}';
        const context = {
            module: 'accounts'
        };
        const fields = {
            type: {
                valueList: ['_analyst', '_prospect'],
                labelKey: 'LBL_TYPE',
                definition: {
                    options: 'account_type_dom',
                } as FieldDefinition,
                type: 'multienum',
            } as Field
        };

        const parsed = service.parse(template, context, fields);

        const expected = 'Analyst, Prospect';

        expect(parsed).toContain(expected);
    });

});
