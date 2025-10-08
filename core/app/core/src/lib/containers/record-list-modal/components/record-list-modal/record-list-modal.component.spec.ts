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

import {RecordListModalComponent} from './record-list-modal.component';
import {Component, OnInit} from '@angular/core';
import {NgbModalRef} from '@ng-bootstrap/ng-bootstrap/modal/modal-ref';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {interval} from 'rxjs';
import {take} from 'rxjs/operators';
import {RouterTestingModule} from '@angular/router/testing';
import {Router} from '@angular/router';
import {themeImagesStoreMock} from '../../../../store/theme-images/theme-images.store.spec.mock';
import {
    mockModuleNavigation,
    mockRouter
} from '../../../../services/navigation/module-navigation/module-navigation.service.spec.mock';
import {RecordListModalStoreFactory} from '../../store/record-list-modal/record-list-modal.store.factory';
import {recordlistModalStoreFactoryMock} from '../../store/record-list-modal/record-list-modal.store.spec.mock';
import {RecordListModalModule} from './record-list-modal.module';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';

@Component({
    selector: 'record-list-modal-test-host-component',
    template: '<div></div>'
})
class RecordListModalTestHostComponent implements OnInit {
    modal: NgbModalRef;

    constructor(public modalService: NgbModal) {
    }

    ngOnInit(): void {
        this.modal = this.modalService.open(RecordListModalComponent, {size: 'xl', scrollable: true});

        this.modal.componentInstance.module = 'accounts';
    }
}

describe('RecordListModalComponent', () => {
    let component: RecordListModalTestHostComponent;
    let fixture: ComponentFixture<RecordListModalTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [RecordListModalTestHostComponent],
            imports: [
                RecordListModalModule,
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: RecordListModalStoreFactory, useValue: recordlistModalStoreFactoryMock},
                {provide: ThemeImagesStore, useValue: themeImagesStoreMock},
                {provide: ModuleNavigation, useValue: mockModuleNavigation},
                {provide: Router, useValue: mockRouter},
                RouterTestingModule
            ],
        })
            .compileComponents();

        fixture = TestBed.createComponent(RecordListModalTestHostComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    }));

    it('should have a table', async () => {
        expect(component).toBeTruthy();

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(1000).pipe(take(1)).toPromise();

        const table = document.getElementsByTagName('scrm-table');

        expect(table).toBeTruthy();
        expect(table.length).toBeTruthy();

        component.modal.close();

        await interval(1000).pipe(take(1)).toPromise();
    });

    it('should have a filter panel', async () => {
        expect(component).toBeTruthy();

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(1500).pipe(take(1)).toPromise();

        const table = document.getElementsByTagName('scrm-list-filter');

        expect(table).toBeTruthy();
        expect(table.length).toBeTruthy();

        component.modal.close();

        await interval(1500).pipe(take(1)).toPromise();
    });

});
