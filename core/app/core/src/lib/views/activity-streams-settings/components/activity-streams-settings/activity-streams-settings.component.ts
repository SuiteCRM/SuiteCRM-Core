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
import {Process, ProcessService} from '../../../../services/process/process.service';
import {MessageService} from '../../../../services/message/message.service';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';

export interface ActivityStreamsModule {
    module: string;
    label: string;
    enabled: boolean;
}

export interface ActivityStreamsSettingsData {
    enabled: boolean;
    modules: ActivityStreamsModule[];
    userFeed: boolean;
    labels: { [key: string]: string };
}

/**
 * Activity Streams settings view of the administration (legacy SugarFeed AdminSettings): the
 * feed on or off, the modules whose changes are posted and the user feed (disabled while the
 * feed is off; turning it on turns the user feed on, as the legacy page), saved together (Save
 * goes back to the administration), and the deletion of all the feed entries.
 *
 * Route sugar-feed/AdminSettings (admin panel link). Data from the `activity-streams-settings`
 * process (ActivityStreamsSettingsHandler).
 */
@Component({
    selector: 'scrm-activity-streams-settings',
    templateUrl: './activity-streams-settings.component.html',
    styleUrls: [],
})
export class ActivityStreamsSettingsComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'activity-streams-settings';

    data: ActivityStreamsSettingsData = null;
    enabled = false;
    modules: { [module: string]: boolean } = {};
    userFeed = false;
    loading = false;

    protected sub: Subscription;

    constructor(
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
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
     * A label of the page (SugarFeed strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || (this.data ? key : '');
    }

    /**
     * Turning the feed on turns the user feed on, as the legacy page.
     */
    toggleFeed(enabled: boolean): void {
        this.enabled = enabled;
        if (enabled) {
            this.userFeed = true;
        }
    }

    save(): void {
        this.run({action: 'save', enabled: this.enabled, modules: this.modules, userFeed: this.userFeed}, true);
    }

    deleteEntries(): void {
        this.confirmation.showModal(['LBL_CONFIRM_DELETE_RECORDS'], () => {
            this.run({action: 'delete'});
        }, () => {}, {}, {module: 'sugar-feed'});
    }

    trackModule(index: number, item: ActivityStreamsModule): string {
        return item.module;
    }

    protected run(options: { [key: string]: any }, leave = false): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(ActivityStreamsSettingsComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as ActivityStreamsSettingsData;
                this.loading = false;
                if (data?.modules) {
                    this.data = data;
                    // The options being changed are kept when the feed entries are deleted.
                    if (options.action !== 'delete') {
                        this.enabled = data.enabled;
                        this.userFeed = data.userFeed;
                        this.modules = {};
                        data.modules.forEach(module => this.modules[module.module] = module.enabled);
                    }
                }
                if (process?.status !== 'success') {
                    this.message.addDangerMessageByKey('LBL_ACTION_ERROR');
                } else if (options.action === 'delete') {
                    this.message.addSuccessMessage(this.label('LBL_RECORDS_DELETED'));
                } else if (leave) {
                    this.router.navigate(['/administration']);
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessageByKey('LBL_ACTION_ERROR');
                this.cdr.detectChanges();
            }
        });
    }
}
