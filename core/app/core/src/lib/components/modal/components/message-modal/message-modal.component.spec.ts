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
import {MessageModalComponent} from './message-modal.component';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {Component, OnInit} from '@angular/core';
import {ModalButtonInterface} from '../../../../common/components/modal/modal.model';
import {NgbModalRef} from '@ng-bootstrap/ng-bootstrap/modal/modal-ref';
import {NoopAnimationsModule} from '@angular/platform-browser/animations';
import {interval} from 'rxjs';
import {take} from 'rxjs/operators';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {MessageModalModule} from './message-modal.module';
import {languageStoreMock} from '../../../../store/language/language.store.spec.mock';
import {LanguageStore} from '../../../../store/language/language.store';
import {systemConfigStoreMock} from '../../../../store/system-config/system-config.store.spec.mock';

@Component({
    selector: 'messages-modal-test-host-component',
    template: '<div></div>'
})
class MessageModalTestHostComponent implements OnInit {
    modal: NgbModalRef;
    cancelClicked = 0;
    okClicked = 0;

    constructor(public modalService: NgbModal) {
    }

    ngOnInit(): void {
        this.modal = this.modalService.open(MessageModalComponent);

        this.modal.componentInstance.textKey = 'WARN_UNSAVED_CHANGES';
        this.modal.componentInstance.buttons = [
            {
                labelKey: 'LBL_CANCEL',
                klass: ['btn-secondary'],
                onClick: activeModal => {
                    this.cancelClicked++;
                    activeModal.dismiss();
                }
            } as ModalButtonInterface,
            {
                labelKey: 'LBL_OK',
                klass: ['btn-main'],
                onClick: activeModal => {
                    this.okClicked++;
                    activeModal.close();
                }
            } as ModalButtonInterface,
        ];
    }
}

describe('MessageModalComponent', () => {
    let component: MessageModalTestHostComponent;
    let fixture: ComponentFixture<MessageModalTestHostComponent>;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [MessageModalTestHostComponent],
            imports: [
                MessageModalModule,
                NoopAnimationsModule
            ],
            providers: [
                {provide: LanguageStore, useValue: languageStoreMock},
                {provide: SystemConfigStore, useValue: systemConfigStoreMock}
            ],
        })
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(MessageModalTestHostComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should have modal', () => {
        expect(component).toBeTruthy();

        const modal = document.getElementsByClassName('message-modal');

        expect(modal).toBeTruthy();
        expect(modal.length).toBeTruthy();
        component.modal.close();
    });

    it('should have message', async () => {
        expect(component).toBeTruthy();

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(1000).pipe(take(1)).toPromise();

        const modal = document.getElementsByClassName('message-modal');

        expect(modal).toBeTruthy();
        expect(modal.length).toBeTruthy();

        const body = modal.item(0).getElementsByClassName('modal-body');

        expect(body).toBeTruthy();
        expect(body.length).toBeTruthy();

        expect(body.item(0).textContent).toContain('Are you sure you want to navigate away from this record?');

        component.modal.close();
    });

    it('should have a clickable cancel button', async () => {
        expect(component).toBeTruthy();

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(1000).pipe(take(1)).toPromise();

        const modal = document.getElementsByClassName('message-modal');

        expect(modal).toBeTruthy();
        expect(modal.length).toBeTruthy();

        const footer = modal.item(0).getElementsByClassName('modal-footer');

        expect(footer).toBeTruthy();
        expect(footer.length).toBeTruthy();

        const buttons = footer.item(0).getElementsByTagName('button');

        expect(buttons).toBeTruthy();
        expect(buttons.length).toEqual(2);

        const cancelButton = buttons.item(0);

        expect(cancelButton.textContent).toContain('Cancel');
        expect(cancelButton.className).toContain('btn-secondary');

        cancelButton.click();

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(1000).pipe(take(1)).toPromise();

        expect(component.cancelClicked).toEqual(1);
    });

    it('should have a clickable ok button', async () => {
        expect(component).toBeTruthy();

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(1000).pipe(take(1)).toPromise();

        const modal = document.getElementsByClassName('message-modal');

        expect(modal).toBeTruthy();
        expect(modal.length).toBeTruthy();

        const footer = modal.item(0).getElementsByClassName('modal-footer');

        expect(footer).toBeTruthy();
        expect(footer.length).toBeTruthy();

        const buttons = footer.item(0).getElementsByTagName('button');

        expect(buttons).toBeTruthy();
        expect(buttons.length).toEqual(2);

        const okButton = buttons.item(1);

        expect(okButton.textContent).toContain('OK');
        expect(okButton.className).toContain('btn-main');

        okButton.click();

        expect(component.okClicked).toEqual(1);

        fixture.detectChanges();
        await fixture.whenRenderingDone();

        await interval(1000).pipe(take(1)).toPromise();
    });

});
