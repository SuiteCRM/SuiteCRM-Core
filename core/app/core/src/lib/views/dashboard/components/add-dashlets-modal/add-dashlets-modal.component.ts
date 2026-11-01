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

import {ChangeDetectorRef, Component, Input, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {LanguageStore} from '../../../../store/language/language.store';
import {DashboardEditService} from '../../services/dashboard-edit.service';
import {DashletCatalog, DashletCatalogItem} from '../../dashboard.model';

/**
 * "Add dashlets": the dashlets of the legacy "Add Dashlets" dialog
 * (module access checked by the legacy dashboard), by category, with a search.
 * The chosen dashlet goes at the top of the first column of the tab; the modal
 * closes with its id.
 */
@Component({
    selector: 'scrm-add-dashlets-modal',
    standalone: true,
    imports: [CommonModule, FormsModule],
    template: `
        <div class="modal-header">
            <h4 class="modal-title">{{ label('LBL_DASHBOARD_ADD_DASHLETS') }}</h4>
            <button type="button" class="btn-close close" [attr.aria-label]="appLabel('LBL_CLOSE_BUTTON_TITLE')"
                    (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <p *ngIf="loading" class="dashboard-modal-message">{{ label('LBL_DASHBOARD_LOADING') }}</p>
            <p *ngIf="error" class="dashboard-modal-message dashboard-modal-error" role="alert">{{ error }}</p>
            <ng-container *ngIf="catalog">
                <div class="dashboard-catalog-head">
                    <div class="dashboard-catalog-tabs" role="tablist">
                        <button *ngFor="let category of catalog.categories" type="button" role="tab" class="dashboard-tab"
                                [class.is-active]="category.key === active" [attr.aria-selected]="category.key === active"
                                (click)="active = category.key; search = ''">
                            {{ category.label }} <span class="dashboard-count">{{ category.dashlets.length }}</span>
                        </button>
                    </div>
                    <input type="search" class="form-control dashboard-catalog-search" [placeholder]="label('LBL_DASHBOARD_SEARCH_DASHLETS')"
                           [attr.aria-label]="label('LBL_DASHBOARD_SEARCH_DASHLETS')" [(ngModel)]="search">
                </div>
                <ul class="dashboard-catalog" role="list">
                    <li *ngFor="let item of shown()">
                        <button type="button" class="dashboard-catalog-item" [disabled]="adding" (click)="add(item)">
                            <span class="dashboard-catalog-title">{{ item.title }}</span>
                            <span *ngIf="item.description && item.description !== item.title" class="dashboard-catalog-description">{{ item.description }}</span>
                        </button>
                    </li>
                </ul>
                <p *ngIf="!shown().length" class="dashboard-modal-message">{{ label('LBL_DASHBOARD_NO_DASHLET_FOUND') }}</p>
            </ng-container>
        </div>
    `
})
export class AddDashletsModalComponent implements OnInit {

    @Input() page = 0;

    catalog: DashletCatalog | null = null;
    active = '';
    search = '';
    loading = true;
    adding = false;
    error = '';

    constructor(
        public modal: NgbActiveModal,
        protected edit: DashboardEditService,
        protected languageStore: LanguageStore,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.loadCatalog(true);
    }

    label(key: string): string {
        return this.languageStore.getFieldLabel(key, 'home') || key;
    }

    protected loadCatalog(retry: boolean): void {
        this.edit.run<DashletCatalog & { retry?: boolean }>({action: 'catalog'}).subscribe(catalog => {
            // The server rebuilt its dashlets cache: once more (see DashboardEditHandler::buildDashletsCache()).
            if (catalog?.retry && retry) {
                this.loadCatalog(false);
                return;
            }
            this.loading = false;
            this.catalog = catalog?.categories ? catalog : null;
            this.active = this.catalog?.categories?.[0]?.key ?? '';
            this.error = this.catalog ? '' : this.label('LBL_DASHBOARD_ERROR');
            this.cdr.detectChanges();
        });
    }

    appLabel(key: string): string {
        return this.languageStore.getAppString(key) || key;
    }

    /** The dashlets of the open category, or of all of them when searching. */
    shown(): DashletCatalogItem[] {
        const search = this.search.trim().toLowerCase();
        const categories = this.catalog?.categories ?? [];
        if (!search) {
            return categories.find(category => category.key === this.active)?.dashlets ?? [];
        }

        return categories
            .reduce((items, category) => items.concat(category.dashlets), [] as DashletCatalogItem[])
            .filter(item => `${item.title} ${item.description}`.toLowerCase().includes(search));
    }

    add(item: DashletCatalogItem, retry = true): void {
        this.adding = true;
        this.error = '';
        this.edit.run<{ id?: string, error?: string }>({action: 'add', className: item.className, page: this.page}).subscribe(result => {
            // The server rebuilt its dashlets cache: once more (see DashboardEditHandler::buildDashletsCache()).
            if (result?.error === 'retry' && retry) {
                this.add(item, false);
                return;
            }
            this.adding = false;
            if (result?.id) {
                this.modal.close(result.id);
                return;
            }
            this.error = result?.error === 'max'
                ? this.label('LBL_DASHBOARD_MAX').replace('%s', String(this.catalog?.max ?? ''))
                : this.label('LBL_DASHBOARD_SAVE_ERROR');
            this.cdr.detectChanges();
        });
    }
}
