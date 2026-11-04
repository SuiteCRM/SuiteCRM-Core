/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {take} from 'rxjs/operators';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {ViewMode} from '../../../../common/views/view.model';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

/**
 * Edit of the record view of a mail account, external OAuth provider or connection, or calendar
 * account (modules/<module>/metadata/detailviewdefs.php): the account editor (routes <module>/edit,
 * see app-initializer.ts), as the legacy edit view.
 */
@Injectable()
abstract class MailAccountEditAction extends RecordActionHandler {
    modes = ['detail'] as ViewMode[];

    protected abstract module: string;

    constructor(protected router: Router) {
        super();
    }

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }

    run(data: RecordActionData): void {
        const id = data?.store?.recordStore?.getBaseRecord()?.id ?? '';
        if (id) {
            this.router.navigate([`/${this.module}/edit`, id]).then();
        }
    }
}

/**
 * Set as default of a personal mail account, for its owner (button of the
 * legacy detail view, action_SetDefault() of the module controller): action setDefault of the
 * process of the module.
 */
@Injectable()
abstract class MailAccountSetDefaultAction extends RecordActionHandler {
    modes = ['detail'] as ViewMode[];

    protected abstract process: string;
    // The type of a personal account.
    protected abstract personalType: string;

    constructor(
        protected processService: ProcessService,
        protected appState: AppStateStore,
        protected messages: MessageService,
        protected language: LanguageStore
    ) {
        super();
    }

    shouldDisplay(data: RecordActionData): boolean {
        const attributes = data?.store?.recordStore?.getBaseRecord()?.attributes ?? {};

        return attributes.type === this.personalType && attributes.created_by === this.appState.getCurrentUser()?.id;
    }

    run(data: RecordActionData): void {
        const id = data?.store?.recordStore?.getBaseRecord()?.id ?? '';
        this.processService.submit(this.process, {action: 'setDefault', id}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                if (process?.status !== 'success') {
                    this.messages.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR'));
                    return;
                }
                data.store.load(false).pipe(take(1)).subscribe();
            },
            error: () => this.messages.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR'))
        });
    }
}

@Injectable({providedIn: 'root'})
export class InboundEmailEditAction extends MailAccountEditAction {
    key = 'inbound-email-edit';
    protected module = 'inbound-email';
}

@Injectable({providedIn: 'root'})
export class InboundEmailSetDefaultAction extends MailAccountSetDefaultAction {
    key = 'inbound-email-set-default';
    protected process = 'inbound-email-editor';
    protected personalType = 'personal';
}

@Injectable({providedIn: 'root'})
export class OutboundEmailEditAction extends MailAccountEditAction {
    key = 'outbound-email-edit';
    protected module = 'outbound-email-accounts';
}

@Injectable({providedIn: 'root'})
export class OutboundEmailSetDefaultAction extends MailAccountSetDefaultAction {
    key = 'outbound-email-set-default';
    protected process = 'outbound-email-editor';
    protected personalType = 'user';
}

@Injectable({providedIn: 'root'})
export class OAuthProviderEditAction extends MailAccountEditAction {
    key = 'oauth-provider-edit';
    protected module = 'external-oauth-provider';
}

@Injectable({providedIn: 'root'})
export class OAuthConnectionEditAction extends MailAccountEditAction {
    key = 'oauth-connection-edit';
    protected module = 'external-oauth-connection';
}

@Injectable({providedIn: 'root'})
export class CalendarAccountEditAction extends MailAccountEditAction {
    key = 'calendar-account-edit';
    protected module = 'calendar-account';
}

/**
 * Sync Now of the calendar account record view (button of the legacy detail view,
 * CalendarAccountController::action_syncCalendarAccount()): after a confirmation, the meetings of
 * the account are synchronized (process `calendar-account-editor`, action sync), then the record
 * view shows the new status.
 */
@Injectable({providedIn: 'root'})
export class CalendarAccountSyncAction extends RecordActionHandler {
    key = 'calendar-account-sync';
    modes = ['detail'] as ViewMode[];

    constructor(
        protected processService: ProcessService,
        protected confirmation: ConfirmationModalService,
        protected messages: MessageService,
        protected language: LanguageStore
    ) {
        super();
    }

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }

    run(data: RecordActionData): void {
        const id = data?.store?.recordStore?.getBaseRecord()?.id ?? '';
        if (!id) {
            return;
        }
        // The labels of the module (context), as the legacy confirmation.
        this.confirmation.showModal(['LBL_SYNC_NOW_CONFIRM'], () => this.sync(id, data), () => {}, {}, {module: 'calendar-account'}, 'LBL_SYNC_NOW');
    }

    protected sync(id: string, data: RecordActionData): void {
        this.messages.addSuccessMessage(this.language.getFieldLabel('LBL_SYNC_IN_PROGRESS_MESSAGE', 'calendar-account'));
        this.processService.submit('calendar-account-editor', {action: 'sync', id}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const result = process?.data ?? {};
                if (process?.status === 'success' && result.success) {
                    this.messages.addSuccessMessage(result.message);
                } else {
                    this.messages.addDangerMessage(result.message || this.language.getAppString('LBL_ACTION_ERROR'));
                }
                data.store.load(false).pipe(take(1)).subscribe();
            },
            error: () => this.messages.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR'))
        });
    }
}
