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

import {provideHttpClientTesting} from '@angular/common/http/testing';
import {Component} from '@angular/core';
import {ComponentFixture, TestBed} from '@angular/core/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {SubpanelModule} from './subpanel.module';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {RecordViewStore} from '../../../../views/record/store/record-view/record-view.store';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {recordviewStoreMock} from '../../../../views/record/store/record-view/record-view.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';

const store = recordviewStoreMock.getSubpanels().contacts;

@Component({
    selector: 'subpanel-test-host-component',
    template: '<scrm-subpanel [store]="store"></scrm-subpanel>'
})
class SubpanelComponentTestHostComponent {
    store = store;
}

describe('SubpanelComponent', () => {
    let testHostComponent: SubpanelComponentTestHostComponent;
    let testHostFixture: ComponentFixture<SubpanelComponentTestHostComponent>;

    beforeEach(() => {
        TestBed.configureTestingModule({
    declarations: [SubpanelComponentTestHostComponent],
    imports: [SubpanelModule,
        AngularSvgIconModule.forRoot(),
        ApolloTestingModule,
        RouterTestingModule],
    providers: [
        { provide: LanguageStore, useValue: languageStoreMock },
        { provide: RecordViewStore, useValue: recordviewStoreMock },
        { provide: MetadataStore, useValue: metadataStoreMock },
        provideHttpClient(withInterceptorsFromDi()),
        provideHttpClientTesting(),
    ]
})
            .compileComponents();
    });

    beforeEach(() => {
        testHostFixture = TestBed.createComponent(SubpanelComponentTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    });

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });
});
