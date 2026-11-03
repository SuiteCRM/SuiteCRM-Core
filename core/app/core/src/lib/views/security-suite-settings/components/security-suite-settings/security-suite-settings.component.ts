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

export interface SecuritySuiteDefaultGroup {
    id: string;
    group: string;
    module: string;
    moduleLabel: string;
}

export interface SecuritySuiteSettingsData {
    settings: { [setting: string]: boolean };
    defaults: SecuritySuiteDefaultGroup[];
    groups: { id: string; name: string }[];
    modules: { value: string; label: string }[];
    labels: { [key: string]: string };
}

/**
 * Security Suite settings view of the administration (legacy SecurityGroups config action):
 * the rights and group inheritance options, saved together (Save goes back to the
 * administration, as the legacy page), and the default groups of new records, added or
 * removed at once.
 *
 * Route security-groups/config (admin panel link). Data from the `security-suite-settings`
 * process (SecuritySuiteSettingsHandler).
 */
@Component({
    selector: 'scrm-security-suite-settings',
    templateUrl: './security-suite-settings.component.html',
    styleUrls: [],
})
export class SecuritySuiteSettingsComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'security-suite-settings';

    readonly rights = [
        ['securitysuite_additive', 'LBL_ADDITIVE'],
        ['securitysuite_user_role_precedence', 'LBL_USER_ROLE_PRECEDENCE'],
        ['securitysuite_strict_rights', 'LBL_STRICT_RIGHTS'],
        ['securitysuite_filter_user_list', 'LBL_FILTER_USER_LIST'],
        ['securitysuite_user_popup', 'LBL_USER_POPUP'],
        ['securitysuite_popup_select', 'LBL_POPUP_SELECT'],
    ];
    readonly inheritance = [
        ['securitysuite_inherit_creator', 'LBL_INHERIT_CREATOR'],
        ['securitysuite_inherit_parent', 'LBL_INHERIT_PARENT'],
        ['securitysuite_inherit_assigned', 'LBL_INHERIT_ASSIGNED'],
        ['securitysuite_inbound_email', 'LBL_INBOUND_EMAIL'],
    ];

    data: SecuritySuiteSettingsData = null;
    settings: { [setting: string]: boolean } = {};
    group = '';
    module = 'All';
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
     * A label of the page (SecurityGroups strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || (this.data ? key : '');
    }

    /**
     * The options of the selects are kept from a load to the next: recreated, they would lose
     * the selection of their select.
     */
    trackOption(index: number, item: { id?: string; value?: string }): string {
        return item.id ?? item.value ?? `${index}`;
    }

    save(): void {
        this.run({action: 'save', settings: this.settings}, true);
    }

    addDefault(): void {
        if (this.group) {
            this.run({action: 'add', group: this.group, module: this.module});
        }
    }

    removeDefault(id: string): void {
        this.run({action: 'remove', default: id});
    }

    protected run(options: { [key: string]: any }, leave = false): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(SecuritySuiteSettingsComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as SecuritySuiteSettingsData;
                this.loading = false;
                if (data?.settings) {
                    // The options being changed are kept when a default group is added or removed.
                    const changed = options.action === 'add' || options.action === 'remove' ? this.settings : {};
                    this.data = data;
                    this.settings = {...data.settings, ...changed};
                }
                if (process?.status !== 'success') {
                    this.message.addDangerMessageByKey('LBL_ACTION_ERROR');
                } else if (options.action === 'add') {
                    this.group = '';
                    this.module = 'All';
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
