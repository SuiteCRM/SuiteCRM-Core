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
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {MapDisplayComponent} from '../map-display/map-display.component';

interface QuickRadiusOptions {
    modules: { value: string; label: string }[];
    units: { value: string; label: string }[];
    distance: string;
    unitType: string;
    labels: { [key: string]: string };
}

/**
 * The Quick Radius Map of the maps module (menu), as the legacy quick_radius page: an
 * address, the module to display and a distance; Map shows the records of the module within the
 * distance of the address (MapDisplayComponent).
 *
 * Route maps/quick_radius.
 */
@Component({
    selector: 'scrm-quick-radius',
    standalone: true,
    imports: [CommonModule, FormsModule, MapDisplayComponent],
    template: `
        <div class="maps-page quick-radius">
            <h2 class="maps-page-title">{{ label('LBL_MAP_QUICK_RADIUS') }}</h2>
            <form *ngIf="options" class="maps-page-panel" (ngSubmit)="show()">
                <div class="maps-page-row">
                    <label for="quick-radius-address">{{ label('LBL_MAP_ADDRESS') }}</label>
                    <input id="quick-radius-address" name="address" class="form-control form-control-sm" maxlength="255" [(ngModel)]="address">
                    <label for="quick-radius-module">{{ label('LBL_MODULE_TYPE') }}</label>
                    <select id="quick-radius-module" name="module" class="custom-select custom-select-sm" [(ngModel)]="module">
                        <option *ngFor="let option of options.modules" [value]="option.value">{{ option.label }}</option>
                    </select>
                    <label for="quick-radius-distance">{{ label('LBL_DISTANCE') }}</label>
                    <span class="quick-radius-distance">
                        <input id="quick-radius-distance" name="distance" type="number" min="0" step="any" class="form-control form-control-sm"
                               [(ngModel)]="distance">
                        <select name="unit" class="custom-select custom-select-sm" [(ngModel)]="unit" [attr.aria-label]="label('LBL_DISTANCE')">
                            <option *ngFor="let option of options.units" [value]="option.value">{{ option.label }}</option>
                        </select>
                    </span>
                </div>
                <div class="maps-page-buttons">
                    <button type="submit" class="btn btn-sm btn-main" [disabled]="!address.trim()">{{ label('LBL_MAP') }}</button>
                </div>
            </form>
            <scrm-map-display *ngIf="params" [params]="params"></scrm-map-display>
        </div>
    `,
    styles: [`
        .quick-radius .maps-page-row { grid-template-columns: minmax(140px, 1fr) minmax(0, 3fr); margin-bottom: 4px; }
        .quick-radius .quick-radius-distance { display: flex; gap: 8px; max-width: 360px; }
        .quick-radius form { margin-bottom: 16px; }
    `]
})
export class QuickRadiusComponent implements OnInit, OnDestroy {

    options: QuickRadiusOptions = null;
    address = '';
    module = '';
    distance = '';
    unit = '';
    params: { [key: string]: string } = null;

    protected sub: Subscription;

    constructor(protected processService: ProcessService, protected language: LanguageStore, protected cdr: ChangeDetectorRef) {
    }

    ngOnInit(): void {
        this.sub = this.processService.submit('maps', {action: 'options'}).pipe(take(1)).subscribe((process: Process) => {
            this.options = process?.data as QuickRadiusOptions;
            this.module = this.options?.modules?.[0]?.value ?? '';
            this.distance = this.options?.distance ?? '';
            this.unit = this.options?.unitType ?? 'mi';
            this.cdr.detectChanges();
        });
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.options?.labels?.[key] || this.language.getAppString(key) || key;
    }

    show(): void {
        this.params = {quick_address: this.address.trim(), display_module: this.module, distance: `${this.distance}`, unit_type: this.unit};
    }
}
