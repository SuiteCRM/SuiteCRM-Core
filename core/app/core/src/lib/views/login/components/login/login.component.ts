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

import {Component, OnInit, signal, WritableSignal} from '@angular/core';
import {Router} from '@angular/router';
import {combineLatestWith, Observable} from 'rxjs';
import {map, tap} from 'rxjs/operators';
import {transition, trigger, useAnimation} from '@angular/animations';
import {fadeIn} from 'ng-animate';
import {RecoverPasswordService} from '../../../../services/process/processes/recover-password/recover-password';
import {SystemConfigMap, SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {AuthService} from '../../../../services/auth/auth.service';
import {LanguageStore, LanguageStringMap} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process} from '../../../../services/process/process.service';
import {StringMap} from '../../../../common/types/string-map';
import {HttpErrorResponse} from '@angular/common/http';
import {AppStateStore} from "../../../../store/app-state/app-state.store";


@Component({
    selector: 'scrm-login-ui',
    templateUrl: './login.component.html',
    styleUrls: [],
    animations: [
        trigger('fade', [
            transition(':enter', useAnimation(fadeIn, {
                params: {timing: 0.5, delay: 0}
            })),
        ])
    ]
})
export class LoginUiComponent implements OnInit {
    hidden = true;
    loading = false;
    error = '';
    uname = '';
    passw = '';
    email = '';

    cardState: WritableSignal<string> = signal('front');

    systemConfigs$: Observable<SystemConfigMap> = this.configs.configs$;
    appStrings$: Observable<LanguageStringMap> = this.languageStore.appStrings$;

    language: string = null;

    vm$ = this.systemConfigs$.pipe(
        combineLatestWith(this.appStrings$),
        map(([systemConfigs, appStrings]: [SystemConfigMap, LanguageStringMap]) => {
            let showLanguages = false;
            let showForgotPassword = false;

            if (systemConfigs.languages && systemConfigs.languages.items && systemConfigs.login_language.value) {
                showLanguages = Object.keys(systemConfigs.languages.items).length > 1;
            }

            if (systemConfigs.passwordsetting && systemConfigs.passwordsetting.items) {
                const forgotPasswordProperty = systemConfigs.passwordsetting.items.forgotpasswordON;
                showForgotPassword = [true, '1', 'true'].includes(forgotPasswordProperty);
            }

            return {
                systemConfigs,
                appStrings,
                showLanguages,
                showForgotPassword
            };
        })
    );

    constructor(
        protected router: Router,
        protected auth: AuthService,
        protected message: MessageService,
        protected configs: SystemConfigStore,
        protected languageStore: LanguageStore,
        protected recoverPasswordService: RecoverPasswordService,
        protected appState: AppStateStore
    ) {
        this.loading = false;
        this.hidden = false;
        this.language = null;
    }

    ngOnInit() {
        this.setCurrentLanguage();
        this.appState.removeAllPrevRoutes();
    }

    onLanguageSelect(language: string): void {
        if (!language) {
            return;
        }

        if (language === this.language) {
            return;
        }
        this.changeLanguage(language);
    }

    changeLanguage(language: string): void {
        this.language = language;

        let languagesLoading = false;
        if (this?.appState?.updateLoading) {
            this.appState.updateLoading('change-language', true);
            languagesLoading = true;
        }

        this.languageStore.changeLanguage(language, true).pipe(
            tap(() => {
                if (languagesLoading) {
                    this.appState.updateLoading('change-language', false);
                }

            })
        ).subscribe();
    }

    getEnabledLanguages(): StringMap {
        return this.languageStore.getEnabledLanguages();
    }

    getEnabledLanguagesKeys(): string[] {
        return Object.keys(this.languageStore.getEnabledLanguages() ?? {}) ?? [];
    }

    flipCard(): void {
        if (this.cardState() === 'front') {
            this.cardState.set('back');
        } else {
            this.cardState.set('front');
        }
    }

    returnToLogin(): void {
        this.cardState.set('front');
        this.auth.isUserLoggedIn.next(false);
        this.auth.handleInvalidSession('LBL_2FA_LOGIN_CANCEL');
        return;
    }

    doLogin(): void {
        this.loading = true;
        this.auth.doLogin(this.uname, this.passw, this.onLoginSuccess.bind(this), this.onLoginError.bind(this), this.onTwoFactor.bind(this));
    }

    recoverPassword(): void {
        this.recoverPasswordService
            .run(this.uname, this.email)
            .subscribe(
                (process: Process) => {
                    this.message.log('Recover Password Status: ' + process.status);

                    let handler = 'addSuccessMessageByKey';
                    if (process.status === 'error') {
                        handler = 'addDangerMessageByKey';
                    }

                    if (process.messages) {
                        process.messages.forEach(message => {
                            this.message[handler](message);
                        });
                    }
                },
                () => {
                    this.message.log('Recover Password failed');
                    this.message.addDangerMessageByKey('ERR_AJAX_LOAD_FAILURE');
                }
            );
    }

    onLoginSuccess(result: any): void {
        this.loading = false;
        this.message.log('Login success');
        this.message.removeMessages();

        this.auth.setLanguage(result);
        return;
    }

    onLoginError(httpError: HttpErrorResponse): void {
        this.loading = false;
        this.message.log('Login failed');

        const defaultMessage = 'Login credentials incorrect, please try again.';
        const defaultTooManyFailedMessage = 'Too many failed login attempts, please try again later.';
        let message = this.languageStore.getFieldLabel('LOGIN_INCORRECT');

        const errorMessage = httpError?.error?.error ?? '';

        if (typeof errorMessage === 'string' && errorMessage.includes('Too many failed login attempts, please try again in')) {
            message = this.getTooManyFailedMessage(defaultTooManyFailedMessage);
        }

        if (!message) {
            message = defaultMessage
        }
        this.message.addDangerMessage(message);
    }

    onTwoFactor(result: any): void {
        this.cardState.set('2fa');
    }

    protected getTooManyFailedMessage(defaultTooManyFailedMessage: string): string {
        let tooManyFailedMessage = this.languageStore.getFieldLabel('LOGIN_TOO_MANY_FAILED');

        if (!tooManyFailedMessage) {
            tooManyFailedMessage = defaultTooManyFailedMessage;
        }
        return tooManyFailedMessage;
    }

    protected setCurrentLanguage(): void {
        let currentLanguage = this.languageStore.getSelectedLanguage() ?? '';
        const activeLanguage = this.languageStore.getActiveLanguage();

        if (!currentLanguage) {
            currentLanguage = activeLanguage;
        }

        if (!this.languageStore.isLanguageEnabled(currentLanguage)) {
            currentLanguage = '';
        }

        if (this.language && currentLanguage === this.language) {
            return;
        }

        const defaultLanguage = this.configs.getConfigValue('default_language') ?? 'en_us';

        if (!currentLanguage) {
            currentLanguage = defaultLanguage;
        }

        if (!this.languageStore.isLanguageEnabled(currentLanguage)) {
            currentLanguage = this.languageStore.getFirstLanguage();
        }

        this.language = currentLanguage;
        this.changeLanguage(currentLanguage);
    }
}
