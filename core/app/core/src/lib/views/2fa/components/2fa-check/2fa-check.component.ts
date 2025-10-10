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
import {AuthService} from "../../../../services/auth/auth.service";
import {NotificationStore} from "../../../../store/notification/notification.store";
import {Router} from "@angular/router";
import {MessageService} from "../../../../services/message/message.service";
import {AppStateStore} from "../../../../store/app-state/app-state.store";
import {isTrue} from '../../../../common/utils/value-utils';
import {LanguageStore} from "../../../../store/language/language.store";
import {ButtonCallback, ButtonInterface} from "../../../../common/components/button/button.model";
import {BaseRouteService} from "../../../../services/base-route/base-route.service";

@Component({
    selector: 'scrm-2fa-check',
    templateUrl: './2fa-check.component.html',
    styleUrls: [],
})
export class TwoFactorCheckComponent implements OnInit{

    authCode: string;
    submitCodeButtonConfig: ButtonInterface;

    @HostListener('keyup.control.enter')
    onEnterKey() {
        this.verifyCode();
    }

    constructor(protected authService: AuthService,
                protected message: MessageService,
                protected appState: AppStateStore,
                protected notificationStore: NotificationStore,
                protected router: Router,
                protected baseRoute: BaseRouteService,
                protected languageStore: LanguageStore
    ) {
    }

    ngOnInit() {
        this.submitCodeButtonConfig = {
            klass: 'submit-button login-button',
            onClick: ((): void => {
                this.verifyCode()
            }) as ButtonCallback,
            labelKey: 'LBL_VERIFY_2FA',
            titleKey: ''
        } as ButtonInterface;
    }

    public verifyCode() {
        const authCode = this.authCode;

        this.authService.check2fa(authCode).subscribe(response => {

            if (isTrue(response?.login_success) && isTrue(response?.two_factor_complete)) {
                this.message.removeMessages();
                this.message.addSuccessMessageByKey('LBL_FACTOR_AUTH_SUCCESS');

                if (this.baseRoute.isNativeAuth()) {
                    window.location.href = this.baseRoute.removeNativeAuth();
                }

                this.appState.updateInitialAppLoading(true);
                this.authService.setLanguage(response);
                this.authService.isUserLoggedIn.next(true);
                this.authService.setCurrentUser(response);
                this.notificationStore.enableNotifications();
                this.notificationStore.refreshNotifications();

                if (response?.redirect && response?.redirect?.route) {
                    this.router.navigate(
                        [response.redirect.route],
                        {
                            queryParams: response.redirect.queryParams ?? {}
                        }).then();
                    return;
                }


                return;
            }


            if (response?.error === '2fa_failed') {
                this.message.addDangerMessageByKey('LBL_FACTOR_AUTH_FAIL');
                return;
            }

            const defaultTooManyFailedMessage = 'Too many failed login attempts, please try again later.';
            const message = this.getTooManyFailedMessage(defaultTooManyFailedMessage);
            this.message.addDangerMessage(message);
        })
    }

    protected getTooManyFailedMessage(defaultTooManyFailedMessage: string): string {
        let tooManyFailedMessage = this.languageStore.getFieldLabel('LOGIN_TOO_MANY_FAILED');

        if (!tooManyFailedMessage) {
            tooManyFailedMessage = defaultTooManyFailedMessage;
        }
        return tooManyFailedMessage;
    }
}
