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

import {ChangeDetectorRef, Component, OnDestroy, OnInit} from '@angular/core';
import {Router} from '@angular/router';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface HistorySubpanelModule {
    module: string;
    label: string;
    enabled: boolean;
}

interface HistorySubpanelData {
    modules: HistorySubpanelModule[];
    labels: { [key: string]: string };
}

/**
 * The History Subpanel settings of the administration, as a SuiteCRM 8 view: the
 * modules whose History subpanel shows the emails of their related contacts, saved together
 * (Save goes back to the administration, as the legacy page).
 *
 * Route configurator/historyContactsEmails (admin panel link). Data from the
 * `history-subpanel-settings` process (HistorySubpanelSettingsProcessHandler).
 */
@Component({
    selector: 'scrm-history-subpanel-settings',
    templateUrl: './history-subpanel-settings.component.html',
    styleUrls: []
})
export class HistorySubpanelSettingsComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'history-subpanel-settings';

    data: HistorySubpanelData = null;
    modules: { [module: string]: boolean } = {};
    loading = false;

    protected sub: Subscription;

    constructor(
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.run({action: 'load'});
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    /**
     * A label of the page (Configurator strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || (this.data ? key : '');
    }

    save(): void {
        this.run({action: 'save', modules: this.modules});
    }

    trackModule(index: number, item: HistorySubpanelModule): string {
        return item.module;
    }

    protected run(options: { [key: string]: any }): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(HistorySubpanelSettingsComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as HistorySubpanelData;
                this.loading = false;
                if (data?.modules) {
                    this.data = data;
                    this.modules = {};
                    data.modules.forEach(module => this.modules[module.module] = module.enabled);
                }
                if (process?.status !== 'success') {
                    this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                } else if (options.action === 'save') {
                    this.router.navigate(['/administration']);
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                this.cdr.detectChanges();
            }
        });
    }
}
