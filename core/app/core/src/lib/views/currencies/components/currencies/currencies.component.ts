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
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface Currency {
    id: string;
    name: string;
    iso4217: string;
    symbol: string;
    conversionRate: number;
    status: string;
    system: boolean;
}

interface CurrenciesData {
    currencies: Currency[];
    statuses: { value: string; label: string }[];
    iso: { [code: string]: { name: string; symbol: string } };
    labels: { [key: string]: string };
}

interface CurrencyForm {
    id: string;
    name: string;
    iso4217: string;
    symbol: string;
    conversion_rate: number;
    status: string;
}

/**
 * The Currencies view of the administration, as the legacy page: the list of the
 * currencies (the system currency first, read-only), the edit form of a new or chosen currency
 * (a known ISO 4217 code fills the empty name and symbol), Delete, and Merge (the checked currencies
 * merged into a chosen one: their opportunities move to it and they are deleted).
 *
 * Route currencies/index (admin panel link). Data from the `currencies` process (CurrenciesProcessHandler).
 */
@Component({
    selector: 'scrm-currencies',
    templateUrl: './currencies.component.html',
    styleUrls: []
})
export class CurrenciesComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'currencies';

    data: CurrenciesData = null;
    form: CurrencyForm = this.emptyForm();
    merging = false;
    checked: { [id: string]: boolean } = {};
    mergeTo = '';
    loading = false;

    protected sub: Subscription;

    constructor(
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
     * A label of the page (Currencies strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || (this.data ? key : '');
    }

    status(value: string): string {
        return this.data?.statuses?.find(status => status.value === value)?.label ?? value;
    }

    edit(currency: Currency): void {
        if (currency.system) {
            return;
        }
        this.form = {
            id: currency.id,
            name: currency.name,
            iso4217: currency.iso4217,
            symbol: currency.symbol,
            conversion_rate: currency.conversionRate,
            status: currency.status
        };
    }

    newCurrency(): void {
        this.form = this.emptyForm();
    }

    /**
     * A known ISO 4217 code fills the empty name and symbol (legacy EditView.js).
     */
    isoChanged(code: string): void {
        this.form.iso4217 = (code ?? '').toUpperCase();
        const iso = this.data?.iso?.[this.form.iso4217];
        if (!iso) {
            return;
        }
        if (!this.form.name) {
            this.form.name = iso.name;
        }
        if (!this.form.symbol) {
            this.form.symbol = iso.symbol;
        }
    }

    save(): void {
        const form = this.form;
        if (!form.name?.trim() || !form.iso4217?.trim() || !form.symbol?.trim() || form.conversion_rate === null) {
            this.message.addWarningMessage(this.label('LBL_CURRENCY_REQUIRED'));
            return;
        }
        if (!(Number(form.conversion_rate) >= 0.000001)) {
            this.message.addWarningMessage(this.label('LBL_BELOW_MIN'));
            return;
        }
        this.run({action: 'save', ...form}, () => this.newCurrency());
    }

    delete(currency: Currency): void {
        // The modal takes label keys: the Currencies one (records using the currency get the system
        // currency), else the application one.
        this.confirmation.showModal(['NTC_DELETE_CONFIRMATION'], () => {
            this.run({action: 'delete', id: currency.id}, () => {
                if (this.form.id === currency.id) {
                    this.newCurrency();
                }
            });
        }, () => {}, {}, {module: 'currencies'});
    }

    startMerge(): void {
        this.merging = true;
        this.checked = {};
        this.mergeTo = this.data?.currencies?.[0]?.id ?? '';
    }

    cancelMerge(): void {
        this.merging = false;
        this.checked = {};
    }

    merge(): void {
        const ids = Object.keys(this.checked).filter(id => this.checked[id]);
        if (!ids.length || !this.mergeTo) {
            return;
        }
        this.run({action: 'merge', ids, mergeTo: this.mergeTo}, () => this.cancelMerge());
    }

    trackCurrency(index: number, currency: Currency): string {
        return currency.id;
    }

    protected emptyForm(): CurrencyForm {
        return {id: '', name: '', iso4217: '', symbol: '', conversion_rate: null, status: 'Active'};
    }

    protected run(options: { [key: string]: any }, done: () => void = null): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();

        this.sub = this.processService.submit(CurrenciesComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                const data = process?.data as CurrenciesData;
                if (process?.status !== 'success') {
                    const key = process?.messages?.[0] || 'LBL_ACTION_ERROR';
                    this.message.addDangerMessage(this.label(key) || key);
                } else if (data?.currencies) {
                    this.data = data;
                    if (options.action !== 'load') {
                        this.message.addSuccessMessageByKey(options.action === 'save' ? 'LBL_SAVED' : 'LBL_RECORD_DELETE_SUCCESS');
                    }
                    done?.();
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
