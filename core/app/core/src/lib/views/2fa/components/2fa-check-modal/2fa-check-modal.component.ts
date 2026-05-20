/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
import {Component, HostListener, OnInit} from "@angular/core";
import {NgbActiveModal} from "@ng-bootstrap/ng-bootstrap";
import {LanguageStore} from "../../../../store/language/language.store";
import {CheckTwoFactorCode} from "../../../../services/process/processes/check-two-factor-code/check-two-factor-code";
import {TwoFactorCheckModalResult} from "./2fa-check-modal.model";
import {MessageService} from "../../../../services/message/message.service";
import {ButtonCallback, ButtonInterface} from "../../../../common/components/button/button.model";

@Component({
    selector: 'scrm-2fa-modal',
    templateUrl: './2fa-check-modal.component.html',
    styleUrls: [],
})
export class TwoFactorCheckModalComponent implements OnInit{

    authCode: string;

    checkCodeButtonConfig: ButtonInterface;

    @HostListener('keyup.control.enter')
    onEnterKey() {
        this.checkCode();
    }

    constructor(
        public activeModal: NgbActiveModal,
        protected language: LanguageStore,
        protected message: MessageService,
        protected checkTwoFactorCode: CheckTwoFactorCode
    ) {
    }

    ngOnInit() {
        this.checkCodeButtonConfig = {
            klass: 'btn btn-sm btn-main',
            onClick: ((): void => {
                this.checkCode()
            }) as ButtonCallback,
            labelKey: 'LBL_VERIFY_2FA',
            titleKey: ''
        } as ButtonInterface;
    }

    public checkCode() {
        const authCode = this.authCode;

        this.checkTwoFactorCode.checkCode(authCode).subscribe({
            next: (response) => {
                this.message.removeMessages();
                this.closeModal(response.data.two_factor_complete)
            },
            error: () => {
                this.message.addDangerMessageByKey('LBL_FACTOR_AUTH_FAIL')
            }
        });
    }

    public closeModal(authComplete: boolean) {
        this.activeModal.close({
            two_factor_complete: authComplete
        } as TwoFactorCheckModalResult);
    }

}
