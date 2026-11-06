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
import {ActivatedRoute, Router} from '@angular/router';
import {CdkDrag, CdkDragDrop, moveItemInArray} from '@angular/cdk/drag-drop';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {
    AdminSettingActionResult,
    AdminSettingCondition,
    AdminSettingField,
    AdminSettingItem,
    AdminSettingsPageData,
    AdminSettingValue
} from './admin-settings.model';

/**
 * A settings page of the administration, as a SuiteCRM 8 view: the panels of fields
 * of the legacy page (sent by the process, labels translated), its buttons (as a connection
 * test), saved together (Save goes back to the administration, or stays, as the legacy page).
 *
 * The page is the `adminSettingsPage` data of the route (admin panel link). Data from the
 * `admin-settings` process (AdminSettingsProcessHandler).
 */
@Component({
    selector: 'scrm-admin-settings',
    templateUrl: './admin-settings.component.html',
    styleUrls: []
})
export class AdminSettingsComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'admin-settings';

    page = '';
    data: AdminSettingsPageData = null;
    values: { [name: string]: AdminSettingValue } = {};
    results: { [section: number]: AdminSettingActionResult } = {};
    // The saved passwords to remove.
    cleared: { [name: string]: boolean } = {};
    loading = false;
    submitted = false;

    protected sub: Subscription;
    protected routeSub: Subscription;
    protected disablePredicates: { [field: string]: (drag: CdkDrag<AdminSettingItem>) => boolean } = {};

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        // From a settings page to another, the page of the route changes.
        this.routeSub = this.route.data.subscribe(data => {
            this.page = data?.adminSettingsPage ?? '';
            this.data = null;
            this.results = {};
            this.submit({action: 'load'});
        });
    }

    ngOnDestroy(): void {
        this.routeSub?.unsubscribe();
        this.sub?.unsubscribe();
    }

    /** The page Cancel and the save go back to. */
    back(): string {
        return '/' + (this.data?.back || 'administration');
    }

    appLabel(key: string): string {
        return this.language.getAppString(key) || key;
    }

    /**
     * Whether a field or a section is shown: always, or when the fields it depends on have one of
     * their values.
     */
    visible(item: { showIf?: AdminSettingCondition[] }): boolean {
        return (item.showIf ?? []).every(condition => {
            const value = this.values[condition.field];

            return condition.values.some(expected => typeof expected === 'boolean' ? !!value === expected : `${value ?? ''}` === expected);
        });
    }

    /**
     * The links of a field: those with its value only when it has one, its value in the URL.
     */
    links(field: AdminSettingField): { label: string; href: string }[] {
        const value = `${this.values[field.name] ?? ''}`;

        return (field.links ?? [])
            .filter(link => !link.withValue || value !== '')
            .map(link => ({label: link.label, href: link.href.replace('{value}', encodeURIComponent(value))}));
    }

    /**
     * Whether a field shown is not valid: a number with other characters than digits or above
     * its maximum, a required field empty (a saved password is kept).
     */
    invalid(field: AdminSettingField): boolean {
        if (!this.visible(field)) {
            return false;
        }
        const value = `${this.values[field.name] ?? ''}`.trim();
        if (field.type === 'number' && (!/^\d*$/.test(value) || (field.max && value !== '' && Number(value) > field.max))) {
            return true;
        }

        return !!field.required && value === '' && !(field.type === 'password' && field.set);
    }

    enabledItems(field: AdminSettingField): AdminSettingItem[] {
        const enabled = (this.values[field.name] as string[]) ?? [];

        return enabled.map(value => field.items.find(item => item.value === value)).filter(item => !!item);
    }

    disabledItems(field: AdminSettingField): AdminSettingItem[] {
        const enabled = (this.values[field.name] as string[]) ?? [];

        return field.items.filter(item => !enabled.includes(item.value));
    }

    /**
     * Whether an item of a lists field stays enabled: locked, or the value of the field it
     * depends on (as the default theme).
     */
    locked(field: AdminSettingField, item: AdminSettingItem): boolean {
        return !!item.locked || (!!field.lockedBy && this.values[field.lockedBy] === item.value);
    }

    enable(field: AdminSettingField, item: AdminSettingItem): void {
        this.values[field.name] = [...((this.values[field.name] as string[]) ?? []), item.value];
    }

    disable(field: AdminSettingField, item: AdminSettingItem): void {
        this.values[field.name] = ((this.values[field.name] as string[]) ?? []).filter(value => value !== item.value);
    }

    move(field: AdminSettingField, index: number, offset: number): void {
        const enabled = [...((this.values[field.name] as string[]) ?? [])];
        const target = index + offset;
        if (target < 0 || target >= enabled.length) {
            return;
        }
        [enabled[index], enabled[target]] = [enabled[target], enabled[index]];
        this.values[field.name] = enabled;
    }

    /**
     * An item dragged from a list to the other one, or within the enabled list (in order when
     * the list is ordered), as the drag and drop of the legacy pages. A locked item stays enabled.
     */
    drop(field: AdminSettingField, event: CdkDragDrop<string>): void {
        const item = event.item.data as AdminSettingItem;
        const enabled = [...((this.values[field.name] as string[]) ?? [])];

        if (event.previousContainer === event.container) {
            if (event.container.data !== 'enabled' || !field.ordered) {
                return;
            }
            moveItemInArray(enabled, event.previousIndex, event.currentIndex);
        } else if (event.container.data === 'enabled') {
            enabled.splice(field.ordered ? event.currentIndex : enabled.length, 0, item.value);
        } else if (!this.locked(field, item)) {
            enabled.splice(enabled.indexOf(item.value), 1);
        }

        this.values[field.name] = enabled;
    }

    /**
     * Enables the items locked by the value of another field (as the default theme chosen).
     */
    enableLocked(): void {
        this.data?.sections.forEach(section => section.fields
            .filter(field => field.type === 'lists' && field.lockedBy)
            .forEach(field => {
                const locked = `${this.values[field.lockedBy] ?? ''}`;
                const enabled = (this.values[field.name] as string[]) ?? [];
                if (field.items?.some(item => item.value === locked) && !enabled.includes(locked)) {
                    this.values[field.name] = [...enabled, locked];
                }
            }));
    }

    /**
     * Whether an item can be dropped in the disabled list (not a locked one), by field.
     */
    canDisable(field: AdminSettingField): (drag: CdkDrag<AdminSettingItem>) => boolean {
        if (!this.disablePredicates[field.name]) {
            this.disablePredicates[field.name] = (drag: CdkDrag<AdminSettingItem>) => !this.locked(field, drag.data);
        }

        return this.disablePredicates[field.name];
    }

    pairs(field: AdminSettingField): string[][] {
        return (this.values[field.name] as string[][]) ?? [];
    }

    addPair(field: AdminSettingField): void {
        const first = field.options?.[0]?.value ?? '';
        this.values[field.name] = [...this.pairs(field), [first, first]];
    }

    removePair(field: AdminSettingField, index: number): void {
        this.values[field.name] = this.pairs(field).filter((pair, position) => position !== index);
    }

    trackIndex(index: number): number {
        return index;
    }

    save(): void {
        this.submitted = true;
        const fields = this.data.sections.filter(section => this.visible(section))
            .reduce((all, section) => all.concat(section.fields), [] as AdminSettingField[]);
        if (fields.some(field => this.invalid(field))) {
            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
            this.cdr.detectChanges();
            return;
        }

        this.submit({action: 'save', values: this.payload()});
    }

    /**
     * Runs the action of a button of a section, with the values of the page, and shows its result.
     */
    runAction(index: number, name: string): void {
        const action = this.data?.sections[index]?.actions?.find(item => item.name === name);
        if (action?.confirm && !window.confirm(action.confirm)) {
            return;
        }
        this.loading = true;
        delete this.results[index];
        this.cdr.detectChanges();

        this.processService.submit(AdminSettingsComponent.PROCESS, {page: this.page, action: 'run', name, values: this.payload()})
            .pipe(take(1)).subscribe({
                next: (process: Process) => {
                    this.loading = false;
                    const result = process?.data?.result as AdminSettingActionResult;
                    if (process?.status !== 'success' || !result) {
                        this.message.addDangerMessage(this.appLabel('LBL_ACTION_ERROR'));
                    } else {
                        this.results[index] = result;
                        if (result.message) {
                            result.status === 'success' ? this.message.addSuccessMessage(result.message) : this.message.addDangerMessage(result.message);
                        }
                        Object.assign(this.values, result.values ?? {});
                        if (result.reload) {
                            this.submit({action: 'load'});
                        }
                    }
                    this.cdr.detectChanges();
                },
                error: () => {
                    this.loading = false;
                    this.message.addDangerMessage(this.appLabel('LBL_ACTION_ERROR'));
                    this.cdr.detectChanges();
                }
            });
    }

    /**
     * The values of the page to send: a saved password removed is false (empty keeps it).
     */
    protected payload(): { [name: string]: AdminSettingValue } {
        const values = {...this.values};
        Object.keys(this.cleared).filter(name => this.cleared[name]).forEach(name => values[name] = false);

        return values;
    }

    /**
     * A select whose value is not one of its options selects its first one, as the browser on
     * the legacy page (and saves it).
     */
    protected selectFirstOptions(): void {
        this.data.sections.forEach(section => section.fields
            .filter(field => field.type === 'select' && field.options?.length)
            .forEach(field => {
                if (!field.options.some(option => option.value === `${this.values[field.name] ?? ''}`)) {
                    this.values[field.name] = field.options[0].value;
                }
            }));
    }

    protected submit(options: { [key: string]: any }): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(AdminSettingsComponent.PROCESS, {page: this.page, ...options}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const data = process?.data as AdminSettingsPageData;
                this.loading = false;
                if (process?.status !== 'success') {
                    this.message.addDangerMessage(process?.messages?.[0] || this.appLabel('LBL_ACTION_ERROR'));
                } else if (options.action === 'save' && data?.leave) {
                    this.router.navigate([this.back()]);
                    return;
                } else if (options.action === 'save') {
                    this.message.addSuccessMessage(this.appLabel('LBL_SAVED'));
                }
                // The values being changed are kept when the save fails.
                if (data?.sections && (process?.status === 'success' || !this.data)) {
                    this.data = data;
                    this.values = JSON.parse(JSON.stringify(data.values ?? {}));
                    this.selectFirstOptions();
                    this.cleared = {};
                    this.submitted = false;
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.appLabel('LBL_ACTION_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }
}
