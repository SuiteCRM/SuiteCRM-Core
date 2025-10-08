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

import {Component, Input, OnInit} from '@angular/core';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {ModalCloseFeedBack} from '../../common/components/modal/modal.model';
import {isEmptyString} from '../../common/utils/value-utils';
import {ButtonInterface} from '../../common/components/button/button.model';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {LanguageStore} from '../../store/language/language.store';

export const alertTypes = {
    success: {
        alertClass: 'alert-success',
        alertIcon: 'info-circle'
    },
    error: {
        alertClass: 'alert-danger',
        alertIcon: 'exclamation-circle'
    },
    warning: {
        alertClass: 'alert-warning',
        alertIcon: 'exclamation-triangle'
    },
    info: {
        alertClass: 'alert-info',
        alertIcon: 'info-circle'
    },
    light: {
        alertClass: 'alert-light',
        alertIcon: 'exclamation-circle'
    }
};

@Component({
    selector: 'scrm-install-error-modal',
    templateUrl: './install-error-modal.component.html'
})

export class InstallErrorModalComponent implements OnInit {
    @Input() errors: [];

    titleKey = 'LBL_CHECKSYS_TITLE';
    closeButtonIcon: ButtonInterface;
    closeButton: ButtonInterface;
    saveButton: ButtonInterface;

    constructor(
        protected appState: AppStateStore,
        protected languageStore: LanguageStore,
        public modal: NgbActiveModal) {
    }

    getHeaderLabel(): string {
        return this.languageStore.getFieldLabel('LBL_CHECKSYS_TITLE');
    }

    getColumnLabel(label: string): string {
        const langLabel = this.languageStore.getFieldLabel(label, this.appState.getModule());
        return !isEmptyString(langLabel) ? langLabel : label;
    }

    ngOnInit(): void {
        this.closeButtonIcon = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: (): void => {
                this.modal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

        this.closeButton = {
            klass: ['btn', 'modal-button-cancel'],
            labelKey: 'LBL_COLUMN_SELECTOR_CLOSE_BUTTON',
            onClick: (): void => {
                this.modal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

    }

    getAlertType(alert: any): string{

        if(alert.status === 'error'){
            return alertTypes[alert.type]['alertClass']
        }

        return alertTypes[alert.status]['alertClass'];
    }

    getAlertIcon(alert: any): string{

        if(alert.status === 'error'){
            return alertTypes[alert.type]['alertIcon']
        }

        return alertTypes[alert.status]['alertIcon'];
    }

    returnZero() {
        return 0;
    }
}
