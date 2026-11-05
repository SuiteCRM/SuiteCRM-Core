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

type SettingType = 'bool' | 'number' | 'text' | 'password' | 'select';

interface SettingField {
    name: string;
    label: string;
    type: SettingType;
    help?: string;
    required?: boolean;
}

interface SystemSettingsData {
    values: { [name: string]: string | boolean };
    proxyPasswordSet: boolean;
    options: { [name: string]: { value: string; label: string }[] };
    loggerVisible: boolean;
    logo: string;
    labels: { [key: string]: string };
}

/**
 * The System Settings of the administration, as a SuiteCRM 8 view: the user interface,
 * proxy, advanced and logger options, in the panels of the legacy page, with the company logo.
 * Saved together (Save goes back to the administration, as the legacy page).
 *
 * Route configurator/edit (admin panel link). Data from the `system-settings` process
 * (SystemSettingsProcessHandler).
 */
@Component({
    selector: 'scrm-system-settings',
    templateUrl: './system-settings.component.html',
    styleUrls: []
})
export class SystemSettingsComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'system-settings';

    readonly userInterface: SettingField[] = [
        {name: 'list_max_entries_per_page', label: 'LIST_ENTRIES_PER_LISTVIEW', type: 'number'},
        {name: 'listview_pagination_type', label: 'LBL_SET_LISTVIEW_PAGINATION_TYPE', type: 'select'},
        {name: 'list_max_entries_per_subpanel', label: 'LIST_ENTRIES_PER_SUBPANEL', type: 'number'},
        {name: 'subpanel_pagination_type', label: 'LBL_SET_SUBPANEL_PAGINATION_TYPE', type: 'select'},
        {name: 'list_max_entries_per_modal', label: 'LBL_LIST_ENTRIES_PER_MODAL', type: 'number'},
        {name: 'record_modal_pagination_type', label: 'LBL_SET_RECORD_MODAL_PAGINATION_TYPE', type: 'select'},
        {name: 'lock_homepage', label: 'LOCK_HOMEPAGE', type: 'bool'},
        {name: 'lock_subpanels', label: 'LOCK_SUBPANELS', type: 'bool'},
        {name: 'max_dashlets_homepage', label: 'MAX_DASHLETS', type: 'number'},
        {name: 'use_real_names', label: 'LBL_USE_REAL_NAMES', type: 'bool', help: 'LBL_USE_REAL_NAMES_DESC'},
        {name: 'calculate_response_time', label: 'DISPLAY_RESPONSE_TIME', type: 'bool'},
        {name: 'default_module_favicon', label: 'LBL_MODULE_FAVICON', type: 'bool', help: 'LBL_MODULE_FAVICON_HELP'},
        {name: 'system_name', label: 'SYSTEM_NAME', type: 'text'},
        {name: 'dashlet_auto_refresh_min', label: 'LBL_MIN_AUTO_REFRESH_INTERVAL', type: 'select', help: 'LBL_MIN_AUTO_REFRESH_INTERVAL_HELP'},
        {name: 'snooze_alert_timer', label: 'LBL_SNOOZE_TIMER', type: 'select'},
        {name: 'lead_conv_activity_opt', label: 'LBL_LEAD_CONV_OPTION', type: 'select', help: 'LEAD_CONV_OPT_HELP'},
        {name: 'disable_convert_lead', label: 'LBL_DISALBE_CONVERT_LEAD', type: 'bool', help: 'LBL_DISALBE_CONVERT_LEAD_DESC'},
        {name: 'enable_action_menu', label: 'LBL_ENABLE_ACTION_MENU', type: 'bool', help: 'LBL_ENABLE_ACTION_MENU_DESC'},
        {name: 'enable_line_editing_list', label: 'LBL_ENABLE_INLINE_EDITING_LIST', type: 'bool', help: 'LBL_ENABLE_INLINE_EDITING_LIST_DESC'},
        {name: 'enable_line_editing_detail', label: 'LBL_ENABLE_INLINE_EDITING_DETAIL', type: 'bool', help: 'LBL_ENABLE_INLINE_EDITING_DETAIL_DESC'},
        {name: 'hide_subpanels', label: 'LBL_HIDE_SUBPANELS', type: 'bool'},
    ];
    readonly proxyOn: SettingField = {name: 'proxy_on', label: 'LBL_PROXY_ON', type: 'bool', help: 'LBL_PROXY_ON_DESC'};
    readonly proxy: SettingField[] = [
        {name: 'proxy_host', label: 'LBL_PROXY_HOST', type: 'text', required: true},
        {name: 'proxy_port', label: 'LBL_PROXY_PORT', type: 'text', required: true},
        {name: 'proxy_auth', label: 'LBL_PROXY_AUTH', type: 'bool'},
    ];
    readonly proxyAuth: SettingField[] = [
        {name: 'proxy_username', label: 'LBL_PROXY_USERNAME', type: 'text', required: true},
        {name: 'proxy_password', label: 'LBL_PROXY_PASSWORD', type: 'password', required: true},
    ];
    readonly advanced: SettingField[] = [
        {name: 'verify_client_ip', label: 'VERIFY_CLIENT_IP', type: 'bool'},
        {name: 'log_memory_usage', label: 'LOG_MEMORY_USAGE', type: 'bool'},
        {name: 'dump_slow_queries', label: 'LOG_SLOW_QUERIES', type: 'bool'},
        {name: 'slow_query_time_msec', label: 'SLOW_QUERY_TIME_MSEC', type: 'number'},
        {name: 'upload_maxsize', label: 'UPLOAD_MAX_SIZE', type: 'number'},
        {name: 'stack_trace_errors', label: 'STACK_TRACE_ERRORS', type: 'bool'},
        {name: 'developerMode', label: 'DEVELOPER_MODE', type: 'bool'},
        {name: 'vcal_time', label: 'LBL_VCAL_PERIOD', type: 'number', help: 'vCAL_HELP'},
        {name: 'import_max_records_total_limit', label: 'LBL_IMPORT_MAX_RECORDS', type: 'number', help: 'LBL_IMPORT_MAX_RECORDS_HELP'},
    ];
    readonly logger: SettingField[] = [
        {name: 'logger_file_name', label: 'LBL_LOGGER_FILENAME', type: 'text', help: 'LBL_LOGGER_VALID_FILENAME_CHARACTERS'},
        {name: 'logger_file_ext', label: 'LBL_LOGGER_FILE_EXTENSION', type: 'text'},
        {name: 'logger_file_suffix', label: 'LBL_LOGGER_FILENAME_SUFFIX', type: 'select'},
        {name: 'logger_file_maxSize', label: 'LBL_LOGGER_MAX_LOG_SIZE', type: 'text'},
        {name: 'logger_file_dateFormat', label: 'LBL_LOGGER_DEFAULT_DATE_FORMAT', type: 'text'},
        {name: 'logger_level', label: 'LBL_LOGGER_LOG_LEVEL', type: 'select'},
        {name: 'stackTrace', label: 'LBL_STACK_TRACE', type: 'bool'},
        {name: 'logger_file_maxLogs', label: 'LBL_LOGGER_MAX_LOGS', type: 'text'},
    ];

    data: SystemSettingsData = null;
    values: { [name: string]: string | boolean } = {};
    logo: { url: string; content: string } = null;
    loading = false;
    submitted = false;

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

    /**
     * The label of a field, without the colon some legacy labels end with.
     */
    fieldLabel(key: string): string {
        return this.label(key).replace(/\s*:\s*$/, '');
    }

    /**
     * Whether a field is not valid: a number with other characters than digits, a required
     * proxy option missing.
     */
    invalid(name: string): boolean {
        const value = `${this.values[name] ?? ''}`.trim();
        const number = [...this.userInterface, ...this.advanced].some(field => field.name === name && field.type === 'number');
        if (number) {
            return !/^\d*$/.test(value);
        }

        if (!this.values.proxy_on) {
            return false;
        }
        if (name === 'proxy_host') {
            return value === '';
        }
        if (name === 'proxy_port') {
            return !/^\d+$/.test(value);
        }
        if (!this.values.proxy_auth) {
            return false;
        }
        if (name === 'proxy_username') {
            return value === '';
        }

        return name === 'proxy_password' && value === '' && !this.data?.proxyPasswordSet;
    }

    /**
     * The new logo, read to be sent with the save: an image (the process checks it is a JPEG or
     * PNG one), with the warning of the legacy page when its ratio is not between 1:1 and 10:1.
     */
    selectLogo(input: HTMLInputElement): void {
        const file = input.files?.[0];
        this.logo = null;
        if (!file) {
            return;
        }
        if (!file.type.startsWith('image/')) {
            input.value = '';
            this.message.addDangerMessage(this.label('LBL_ALERT_TYPE_IMAGE'));
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            const url = `${reader.result}`;
            const image = new Image();
            image.onload = () => {
                const ratio = image.naturalWidth / (image.naturalHeight || 1);
                if (ratio > 10 || ratio < 1) {
                    this.message.addWarningMessage(this.label('LBL_ALERT_SIZE_RATIO'));
                }
            };
            image.src = url;
            this.logo = {url, content: url.replace(/^data:[^,]*,/, '')};
            this.cdr.detectChanges();
        };
        reader.readAsDataURL(file);
    }

    save(): void {
        this.submitted = true;
        const names = [...this.userInterface, ...this.advanced].map(field => field.name)
            .concat(['proxy_host', 'proxy_port', 'proxy_username', 'proxy_password']);
        if (names.some(name => this.invalid(name))) {
            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
            this.cdr.detectChanges();
            return;
        }

        const options: { [key: string]: any } = {action: 'save', values: this.values};
        if (this.logo) {
            options.logo = {content: this.logo.content};
        }
        this.run(options);
    }

    protected run(options: { [key: string]: any }): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(SystemSettingsComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as SystemSettingsData;
                this.loading = false;
                if (data?.values && options.action === 'load') {
                    this.data = data;
                    this.values = {...data.values};
                }
                if (process?.status !== 'success') {
                    this.message.addDangerMessage(this.label(process?.messages?.[0] || 'LBL_ACTION_ERROR'));
                } else if (options.action === 'save') {
                    this.router.navigate(['/administration']);
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }
}
