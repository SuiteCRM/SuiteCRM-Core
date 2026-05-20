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

import {ComponentFixture, TestBed, waitForAsync} from '@angular/core/testing';
import {Component, ViewChild} from '@angular/core';
import {Field} from '../../../../common/record/field.model';
import {Record} from '../../../../common/record/record.model';
import {RouterTestingModule} from '@angular/router/testing';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {moduleNameMapperMock} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service.spec.mock';
import {UserPreferenceStore} from '../../../../store/user-preference/user-preference.store';
import {datetimeFormatterMock} from '../../../../services/formatters/datetime/datetime-formatter.service.spec.mock';
import {CurrencyFormatter} from '../../../../services/formatters/currency/currency-formatter.service';
import {userPreferenceStoreMock} from '../../../../store/user-preference/user-preference.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {RelateEditFieldModule} from './relate.module';
import {numberFormatterMock} from '../../../../services/formatters/number/number-formatter.spec.mock';
import {RecordListStoreFactory} from '../../../../store/record-list/record-list.store.factory';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {listStoreFactoryMock} from '../../../../store/record-list/record-list.store.spec.mock';
import {RelateEditFieldComponent} from './relate.component';
import {dateFormatterMock} from '../../../../services/formatters/datetime/date-formatter.service.spec.mock';
import {DateFormatter} from '../../../../services/formatters/datetime/date-formatter.service';
import {RecordListModalModule} from '../../../../containers/record-list-modal/components/record-list-modal/record-list-modal.module';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {currencyFormatterMock} from '../../../../services/formatters/currency/currency-formatter.service.spec.mock';
import {DatetimeFormatter} from '../../../../services/formatters/datetime/datetime-formatter.service';
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';
import {waitUntil} from '../../../../../testing/utils.spec';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';
import {UntypedFormControl} from '@angular/forms';

@Component({
    selector: 'relate-edit-field-test-host-component',
    template: '<scrm-relate-edit #relate [field]="field" [record]="record"></scrm-relate-edit>'
})
class RelateEditFieldTestHostComponent {
    @ViewChild('relate') relate: RelateEditFieldComponent;
    field: Field = {
        type: 'relate',
        value: 'Related Account',
        valueObject: {
            id: '123',
            name: 'Related Account',
        },
        definition: {
            module: 'Accounts',
            // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
            id_name: 'account_id',
            rname: 'name'
        },
        formControl: new UntypedFormControl({
            id: '123',
            name: 'Related Account',
        })
    };

    record: Record = {
        type: '',
        module: 'contacts',
        attributes: {
            // eslint-disable-next-line camelcase, @typescript-eslint/camelcase
            contact_id: '1'
        }
    };
}

describe('RelateRecordEditFieldComponent', () => {
    let testHostComponent: RelateEditFieldTestHostComponent;
    let testHostFixture: ComponentFixture<RelateEditFieldTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                RelateEditFieldTestHostComponent,
            ],
            imports: [
                RouterTestingModule,
                RelateEditFieldModule,
                BrowserDynamicTestingModule,
                NoopAnimationsModule,
                RecordListModalModule
            ],
            providers: [
                {provide: UserPreferenceStore, useValue: userPreferenceStoreMock},
                {provide: NumberFormatter, useValue: numberFormatterMock},
                {provide: DatetimeFormatter, useValue: datetimeFormatterMock},
                {provide: DateFormatter, useValue: dateFormatterMock},
                {provide: CurrencyFormatter, useValue: currencyFormatterMock},
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: RecordListStoreFactory, useValue: listStoreFactoryMock},
                {provide: ModuleNameMapper, useValue: moduleNameMapperMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock},
            ],
        })
            .compileComponents();

        testHostFixture = TestBed.createComponent(RelateEditFieldTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have value', async () => {
        expect(testHostComponent).toBeTruthy();

        testHostFixture.detectChanges();

        const field = testHostFixture.nativeElement.getElementsByTagName('scrm-relate-edit')[0];


        await waitUntil(() => field.getElementsByTagName('tag-input').item(0));

        const tagInput = field.getElementsByTagName('tag-input').item(0);

        expect(field).toBeTruthy();

        expect(tagInput).toBeTruthy();

        const tag = tagInput.getElementsByTagName('tag').item(0);

        expect(tag).toBeTruthy();

        const tagText = tag.getElementsByClassName('tag__text').item(0);

        expect(tagText.textContent).toContain('Related Account');

        const deleteIcon = tagInput.getElementsByTagName('delete-icon').item(0);

        expect(deleteIcon).toBeTruthy();
    });

    it('should remove value', async () => {
        expect(testHostComponent).toBeTruthy();

        testHostFixture.detectChanges();

        const field = testHostFixture.nativeElement.getElementsByTagName('scrm-relate-edit')[0];

        await testHostFixture.whenStable();
        await waitUntil(() => field.getElementsByTagName('tag-input').item(0));

        const tagInput = field.getElementsByTagName('tag-input').item(0);

        expect(field).toBeTruthy();

        expect(tagInput).toBeTruthy();

        let tag = tagInput.getElementsByTagName('tag').item(0);

        expect(tag).toBeTruthy();

        const tagText = tag.getElementsByClassName('tag__text').item(0);

        expect(tagText.textContent).toContain('Related Account');

        const deleteIcon = tagInput.getElementsByTagName('delete-icon').item(0);

        expect(deleteIcon).toBeTruthy();

        deleteIcon.click();

        testHostFixture.detectChanges();

        await waitUntil(() => !(tagInput.getElementsByTagName('tag').item(0)));

        tag = tagInput.getElementsByTagName('tag').item(0);

        expect(tag).toBeFalsy();
    });


    it('should have select button', async () => {
        expect(testHostComponent).toBeTruthy();

        testHostFixture.detectChanges();

        await testHostFixture.whenRenderingDone();

        const field = testHostFixture.nativeElement.getElementsByTagName('scrm-relate-edit')[0];

        const select = field.getElementsByClassName('select-button').item(0);

        expect(select).toBeTruthy();
    });

});
