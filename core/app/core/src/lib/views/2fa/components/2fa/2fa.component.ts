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

import {Component, HostListener, OnInit, signal, WritableSignal} from "@angular/core";
import {AuthService} from "../../../../services/auth/auth.service";
import {Router} from "@angular/router";
import {MessageService} from "../../../../services/message/message.service";
import {isTrue} from '../../../../common/utils/value-utils';
import {LanguageStore} from "../../../../store/language/language.store";
import {ButtonCallback, ButtonInterface} from "../../../../common/components/button/button.model";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";
import {Clipboard} from '@angular/cdk/clipboard';
import {GenerateBackupCodes} from "../../../../services/process/processes/generate-backup-codes/generate-backup-codes";
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {TwoFactorCheckModalComponent} from "../2fa-check-modal/2fa-check-modal.component";


@Component({
    selector: 'scrm-2fa',
    templateUrl: './2fa.component.html',
    styleUrls: [],
})
export class TwoFactorComponent implements OnInit {

    qrCodeUrl: string;
    qrCodeSvg: string;
    secret: string;
    backupCodes: any;
    authCode: string;
    isAppMethodEnabled: WritableSignal<boolean> = signal(false);
    areRecoveryCodesGenerated: WritableSignal<boolean> = signal(false);
    isQrCodeGenerated: WritableSignal<boolean> = signal(false);
    showSecret: WritableSignal<boolean> = signal(false);

    title: string = '';
    appMethodHeaderLabel: string = '';
    enableAppMethodButtonConfig: ButtonInterface;
    disableAppMethodButtonConfig: ButtonInterface;
    cancelAppMethodButtonConfig: ButtonInterface;
    regenerateBackupCodesButtonConfig: ButtonInterface;
    verifyCodeButtonConfig: ButtonInterface;
    copyBackupButtonConfig: ButtonInterface;
    copySecretButtonConfig: ButtonInterface;
    recoveryCodesHeaderLabel: string = '';

    @HostListener('keyup.control.enter')
    onEnterKey() {
        this.finalize2fa();
    }

    constructor(
        protected authService: AuthService,
        protected router: Router,
        protected message: MessageService,
        protected language: LanguageStore,
        protected userPreference: UserPreferenceStore,
        protected modalService: NgbModal,
        protected clipboard: Clipboard,
        protected generateBackupCodesService: GenerateBackupCodes,
    ) {
    }

    ngOnInit() {
        this.title = this.language.getAppString('LBL_TWO_FACTOR_AUTH');
        this.appMethodHeaderLabel = this.language.getAppString('LBL_TWO_FACTOR_AUTH_APP_METHOD');
        this.recoveryCodesHeaderLabel = this.language.getAppString('LBL_BACKUP_CODES');

        const isEnabled = this.userPreference.getUserPreference('is_two_factor_enabled') ?? false;

        this.isAppMethodEnabled.set(isEnabled);
        this.areRecoveryCodesGenerated.set(isEnabled);
        this.showSecret.set(false);
        this.initButtons();
    }

    public enable2fa(): void {
        this.authService.enable2fa().subscribe({
            next: (response) => {
                this.qrCodeUrl = response?.url;
                this.qrCodeSvg = response?.svg;
                this.secret = response.secret;
                this.areRecoveryCodesGenerated.set(true);
                this.isQrCodeGenerated.set(true);
            },
            error: () => {
                this.isAppMethodEnabled.set(false);
                this.areRecoveryCodesGenerated.set(false);
            }
        });
    }

    public disable2FactorAuth(): void {
        const modal = this.modalService.open(TwoFactorCheckModalComponent, {size: 'lg'});

        modal.result.then((result) => {
            if (!result.two_factor_complete) {
                this.message.addDangerMessageByKey('LBL_FACTOR_AUTH_FAIL');
                return;
            }
            this.message.removeMessages();
            this.disable2fa();

            return;
        }).catch();
    }

    public cancel2fa(): void {
        this.disable2fa();
    }

    public disable2fa(): void {
        this.authService.disable2fa().subscribe({
            next: (response) => {
                if (isTrue(response?.two_factor_disabled)) {

                    this.isAppMethodEnabled.set(false);
                    this.areRecoveryCodesGenerated.set(false);
                    this.isQrCodeGenerated.set(false);

                    this.message.addSuccessMessageByKey('LBL_FACTOR_AUTH_DISABLE');
                }
            },
            error: () => {
                this.isAppMethodEnabled.set(true);
                this.areRecoveryCodesGenerated.set(true);
            }
        });
    }

    getTitle(): string {
        return this.title;
    }

    public finalize2fa(): void {
        this.authService.finalize2fa(this.authCode).subscribe(response => {
            const verified = response?.two_factor_setup_complete ?? false;

            if (isTrue(verified)) {
                this.message.removeMessages();
                this.generateCodes();
                this.message.addSuccessMessageByKey('LBL_FACTOR_AUTH_SUCCESS');

                this.isAppMethodEnabled.set(true);
                this.isQrCodeGenerated.set(false);
                this.authCode = '';

                return;
            }

            this.message.addDangerMessageByKey('LBL_FACTOR_AUTH_FAIL');
        })
    }

    public copyBackupCodes(): void {
        this.clipboard.copy(this.backupCodes);
    }

    public copySecret(): void {
        this.clipboard.copy(this.secret);
    }

    public generateCodes(): void {
        this.backupCodes = null;
        this.generateBackupCodesService.generate().subscribe({
            next: (response) => {
                this.backupCodes = response?.data.backupCodes;
                this.areRecoveryCodesGenerated.set(true)
            },
            error: () => {
                this.areRecoveryCodesGenerated.set(false)
            }
        });
        return;
    }


    public generateBackupCodes(): void {
        const modal = this.modalService.open(TwoFactorCheckModalComponent, {size: 'lg'});

        modal.result.then((result) => {
            if (!result.two_factor_complete) {
                this.message.addDangerMessageByKey('LBL_FACTOR_AUTH_FAIL');
                return;
            }

            this.message.removeMessages();
            this.areRecoveryCodesGenerated.set(false)
            this.generateCodes()
            this.message.addSuccessMessageByKey('LBL_REGENERATED_BACKUP_CODES');
        }).catch();
    }

    setShowSecret(value): void {
        this.showSecret.set(value);
        return;
    }

    protected initButtons() {
        this.enableAppMethodButtonConfig = {
            klass: 'btn btn-sm btn-main',
            onClick: ((): void => {
                this.enable2fa()
            }) as ButtonCallback,
            labelKey: 'LBL_ENABLE',
            titleKey: ''
        } as ButtonInterface;

        this.disableAppMethodButtonConfig = {
            klass: 'btn btn-sm btn-main',
            onClick: ((): void => {
                this.disable2FactorAuth()
            }) as ButtonCallback,
            labelKey: 'LBL_DISABLE',
            titleKey: ''
        } as ButtonInterface;

        this.cancelAppMethodButtonConfig = {
            klass: 'btn btn-sm btn-main',
            onClick: ((): void => {
                this.cancel2fa()
            }) as ButtonCallback,
            labelKey: 'LBL_CANCEL',
            titleKey: ''
        } as ButtonInterface;

        this.regenerateBackupCodesButtonConfig = {
            klass: 'btn btn-sm btn-main',
            onClick: ((): void => {
                this.generateBackupCodes();
            }) as ButtonCallback,
            labelKey: 'LBL_REGENERATE_CODES',
            titleKey: ''
        } as ButtonInterface;

        this.verifyCodeButtonConfig = {
            klass: 'btn btn-sm btn-main mb-2',
            onClick: ((): void => {
                this.finalize2fa()
            }) as ButtonCallback,
            labelKey: 'LBL_VERIFY_2FA',
            titleKey: ''
        } as ButtonInterface;

        this.copyBackupButtonConfig = {
            klass: 'btn btn-sm btn-main copy-button',
            onClick: ((): void => {
                this.copyBackupCodes()
            }) as ButtonCallback,
            labelKey: 'LBL_COPY',
            titleKey: '',
            icon: 'clipboard'
        } as ButtonInterface;

        this.copySecretButtonConfig = {
            klass: 'btn btn-sm btn-main ml-0',
            onClick: ((): void => {
                this.copySecret()
            }) as ButtonCallback,
            labelKey: 'LBL_COPY',
            titleKey: '',
            icon: 'clipboard'
        } as ButtonInterface;
    }
}
