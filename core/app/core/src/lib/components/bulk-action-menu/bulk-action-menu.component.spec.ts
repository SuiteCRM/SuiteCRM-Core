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
import {Component} from '@angular/core';
import {BehaviorSubject, Observable, of} from 'rxjs';
import {BulkActionDataSource, BulkActionMenuComponent} from './bulk-action-menu.component';
import {BulkActionsMap} from '../../common/actions/bulk-action.model';
import {SelectionStatus} from '../../common/views/list/record-selection.model';
import {SelectionDataSource} from '../../common/views/list/selection.model';
import {By} from '@angular/platform-browser';
import {shareReplay, take} from 'rxjs/operators';
import {NgbDropdownModule} from '@ng-bootstrap/ng-bootstrap';
import {DropdownButtonModule} from '../dropdown-button/dropdown-button.module';
import {ButtonModule} from '../button/button.module';
import {LanguageStore} from '../../store/language/language.store';
import {languageMockData} from '../../store/language/language.store.spec.mock';


const selectionSubject = new BehaviorSubject<SelectionStatus>(SelectionStatus.NONE);
const countSubject = new BehaviorSubject<number>(0);
let lastStatus = SelectionStatus.NONE;
const selectionState: SelectionDataSource = {
    getSelectionStatus: (): Observable<SelectionStatus> => selectionSubject.asObservable(),
    getSelectedCount: (): Observable<number> => countSubject.asObservable(),
    updateSelection: (state: SelectionStatus) => {
        selectionSubject.next(state);
        lastStatus = state;
    }
} as SelectionDataSource;

let lastAction = '';
const actionSource: BulkActionDataSource = {
    getBulkActions: (): Observable<BulkActionsMap> => of({
        delete: {
            key: 'delete',
            labelKey: 'LBL_DELETE',
            params: {
                min: 1,
                max: 5
            },
            acl: [
                'delete'
            ]
        },
        export: {
            key: 'export',
            labelKey: 'LBL_EXPORT',
            params: {
                min: 1,
                max: 5
            },
            acl: [
                'export'
            ]
        },
        merge: {
            key: 'merge',
            labelKey: 'LBL_MERGE_DUPLICATES',
            params: {
                min: 1,
                max: 5
            },
            acl: [
                'edit',
                'delete'
            ]
        },
        massupdate: {
            key: 'massupdate',
            labelKey: 'LBL_MASS_UPDATE',
            params: {
                min: 1,
                max: 5
            },
            acl: [
                'massupdate'
            ]
        }
    } as BulkActionsMap).pipe(shareReplay(1)),
    executeBulkAction: (action: string): void => {
        lastAction = action;
    }
};

@Component({
    selector: 'bulk-action-menu-test-host-component',
    template: '<scrm-bulk-action-menu [selectionSource]="state" [actionSource]="actionSource"></scrm-bulk-action-menu>'
})
class BulkActionMenuTestHostComponent {
    state = selectionState;
    actionSource = actionSource;
}

describe('BulkActionMenuComponent', () => {
    let testHostComponent: BulkActionMenuTestHostComponent;
    let testHostFixture: ComponentFixture<BulkActionMenuTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [
                BulkActionMenuTestHostComponent,
                BulkActionMenuComponent,
            ],
            imports: [
                DropdownButtonModule,
                ButtonModule,
                NgbDropdownModule
            ],
            providers: [
                {
                    provide: LanguageStore, useValue: {
                        appListStrings$: of(languageMockData.appListStrings).pipe(take(1)),
                        appStrings$: of(languageMockData.appStrings).pipe(take(1))
                    }
                },
            ],
        }).compileComponents();

        testHostFixture = TestBed.createComponent(BulkActionMenuTestHostComponent);
        testHostComponent = testHostFixture.componentInstance;
        testHostFixture.detectChanges();
    }));

    it('should create', () => {
        expect(testHostComponent).toBeTruthy();
    });

    it('should have selection group', () => {
        expect(testHostFixture.debugElement.query(By.css('.select-action-group'))).toBeTruthy();
    });

    it('should have number of selected items', () => {
        expect(testHostFixture.debugElement.query(By.css('.bulk-action-selected-number'))).toBeTruthy();
    });

    it('should have selection dropdown button', () => {
        const el = testHostFixture.debugElement;

        expect(el.query(By.css('.select-action-group .bulk-action-button'))).toBeTruthy();
        expect(el.query(By.css('.select-action-group .bulk-action-button .checkbox-container'))).toBeTruthy();
        expect(el.query(By.css('.select-action-group .dropdown-menu'))).toBeTruthy();

        const selectAll = el.query(By.css('.select-action-group .dropdown-menu .select-all'));
        const selectPage = el.query(By.css('.select-action-group .dropdown-menu .select-page'));
        const deSelectAll = el.query(By.css('.select-action-group .dropdown-menu .deselect-all'));

        expect(selectAll).toBeTruthy();
        expect(selectAll.nativeElement.text).toContain('Select All');
        expect(selectPage).toBeTruthy();
        expect(selectPage.nativeElement.text).toContain('Select This page');
        expect(deSelectAll).toBeTruthy();
        expect(deSelectAll.nativeElement.text).toContain('Deselect All');
    });

    it('should select all', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = SelectionStatus.NONE;

        const link = el.query(By.css('.select-action-group .dropdown-menu .select-all'));

        link.nativeElement.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(SelectionStatus.ALL);

            const checkbox = el.query(By.css('.select-action-group .checkbox-container input'));

            expect(checkbox).toBeTruthy();
            expect(checkbox.nativeElement.checked).toEqual(true);
            expect(checkbox.nativeElement.indeterminate).toEqual(false);
        });
    }));

    it('should select page', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = SelectionStatus.NONE;

        const link = el.query(By.css('.select-action-group .dropdown-menu .select-page'));

        link.nativeElement.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(SelectionStatus.PAGE);

            const checkbox = el.query(By.css('.select-action-group .checkbox-container input'));

            expect(checkbox).toBeTruthy();
            expect(checkbox.nativeElement.checked).toEqual(false);
            expect(checkbox.nativeElement.indeterminate).toEqual(true);
        });
    }));

    it('should deselect all', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = SelectionStatus.ALL;

        const link = el.query(By.css('.select-action-group .dropdown-menu .deselect-all'));

        link.nativeElement.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(SelectionStatus.NONE);

            const checkbox = el.query(By.css('.select-action-group .checkbox-container input'));

            expect(checkbox).toBeTruthy();
            expect(checkbox.nativeElement.checked).toEqual(false);
            expect(checkbox.nativeElement.indeterminate).toEqual(false);
        });
    }));

    it('should toggle select all on checkbox click', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        lastStatus = SelectionStatus.NONE;
        selectionSubject.next(SelectionStatus.NONE);
        const checkbox = el.query(By.css('.select-action-group .checkbox-container input'));
        checkbox.nativeElement.checked = false;
        checkbox.nativeElement.indeterminate = false;
        testHostFixture.detectChanges();

        checkbox.nativeElement.click();
        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {
            expect(lastStatus).toEqual(SelectionStatus.ALL);

            expect(checkbox).toBeTruthy();
            expect(checkbox.nativeElement.checked).toEqual(true);
            expect(checkbox.nativeElement.indeterminate).toEqual(false);

            const updatedCheckbox = el.query(By.css('.select-action-group .checkbox-container input'));

            updatedCheckbox.nativeElement.click();
            testHostFixture.detectChanges();
            testHostFixture.whenStable().then(() => {
                expect(lastStatus).toEqual(SelectionStatus.NONE);

                expect(updatedCheckbox).toBeTruthy();
                expect(updatedCheckbox.nativeElement.checked).toEqual(false);
                expect(updatedCheckbox.nativeElement.indeterminate).toEqual(false);
            });
        });
    }));


    it('should have delete action', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        const deleteAction = el.query(By.css('.delete-bulk-action'));

        expect(deleteAction.nativeElement).toBeTruthy();
        expect(deleteAction.nativeElement.textContent).toContain('Delete');

        deleteAction.nativeElement.click();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(lastAction).toEqual('delete');
        });
    }));

    it('should have export action', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        const exportAction = el.query(By.css('.export-bulk-action'));

        expect(exportAction.nativeElement).toBeTruthy();
        expect(exportAction.nativeElement.textContent).toContain('Export');

        exportAction.nativeElement.click();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(lastAction).toEqual('export');
        });
    }));

    it('should have merge action', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        const mergeAction = el.query(By.css('.merge-bulk-action'));

        expect(mergeAction.nativeElement).toBeTruthy();
        expect(mergeAction.nativeElement.textContent).toContain('Merge');

        mergeAction.nativeElement.click();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(lastAction).toEqual('merge');
        });
    }));

    it('should have mass update action', waitForAsync(() => {
        const el = testHostFixture.debugElement;

        const massUpdateAction = el.query(By.css('.massupdate-bulk-action'));

        expect(massUpdateAction.nativeElement).toBeTruthy();
        expect(massUpdateAction.nativeElement.textContent).toContain('Mass Update');
        massUpdateAction.nativeElement.click();

        testHostFixture.detectChanges();
        testHostFixture.whenStable().then(() => {

            expect(lastAction).toEqual('massupdate');
        });
    }));

});
