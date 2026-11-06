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
import {ActivatedRoute} from '@angular/router';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface GeocodedModule {
    module: string;
    label: string;
    counts: { [heading: string]: number };
    total: number;
}

interface GeocodedCounts {
    /** The map provider, the modules a Geocode button geocodes, the OpenStreetMap scheduler. */
    provider?: 'google' | 'osm';
    geocodeModules?: string[];
    osmScheduler?: string;
    headings: { key: string; label: string }[];
    modules: GeocodedModule[];
    lastStatus: string;
    exportModules: string[];
    cronUrl: string;
    labels: { [key: string]: string };
}

/**
 * The geocoding pages of the Google Maps administration, as the legacy jjwg_Maps pages,
 * with the `maps-admin` process (MapsAdmin):
 *  - counts (route maps/geocoded_counts): the geocoded counts of the modules by geocoding answer,
 *    Geocode (the addresses of a module, within the geocoding limits) and Reset of a module, the cron
 *    URL, the export of the addresses to geocode, and the deletion of the address cache;
 *  - geocode (route maps/geocode_addresses): geocodes the addresses of all the modules, then the
 *    counts with the last status, as the legacy link;
 *  - test (route maps/geocoding_test): the Google answer for an address.
 */
@Component({
    selector: 'scrm-maps-admin',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './maps-admin.component.html'
})
export class MapsAdminComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'maps-admin';

    mode: 'counts' | 'geocode' | 'test' = 'counts';
    data: GeocodedCounts = null;
    labels: { [key: string]: string } = {};
    loading = false;
    address = '';
    result: string = null;

    protected subs: Subscription[] = [];

    constructor(
        protected route: ActivatedRoute,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected confirmation: ConfirmationModalService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.mode = this.route.snapshot.data?.mapsAdminPage ?? 'counts';
        if (this.mode === 'test') {
            // The labels of the page come with the first answer; Process It needs none.
            this.run({action: 'counts'}, data => this.labels = data.labels);
        } else {
            this.run({action: this.mode === 'geocode' ? 'geocode' : 'counts', module: ''});
        }
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    label(key: string): string {
        return this.data?.labels?.[key] || this.labels[key] || this.language.getAppString(key) || key;
    }

    /** Whether the Geocode button of a module is shown (with OpenStreetMap, the modules geocoded with it). */
    canGeocode(module: string): boolean {
        return !this.data?.geocodeModules || this.data.geocodeModules.includes(module);
    }

    geocode(module: string): void {
        this.run({action: 'geocode', module});
    }

    reset(module: GeocodedModule): void {
        this.confirmation.showModal([this.label('LBL_MAPS_VIEW_RESET_CONFIRM')], () => this.run({action: 'reset', module: module.module}));
    }

    deleteCache(): void {
        this.confirmation.showModal([this.label('LBL_MAPS_VIEW_DELETE_CACHE_CONFIRM')], () => this.run({action: 'deleteCache'}));
    }

    /** Downloads the CSV of the addresses of a module to geocode (legacy export URL). */
    export(module: string): void {
        this.subs.push(this.processService.submit(MapsAdminComponent.PROCESS, {action: 'export', module}).pipe(take(1))
            .subscribe((process: Process) => {
                const data = process?.data as { csv: string; filename: string };
                if (process?.status !== 'success' || !data) {
                    this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                    return;
                }
                const url = URL.createObjectURL(new Blob([data.csv], {type: 'text/csv'}));
                const link = document.createElement('a');
                link.href = url;
                link.download = data.filename;
                link.click();
                URL.revokeObjectURL(url);
            }));
    }

    test(): void {
        if (!this.address.trim()) {
            return;
        }
        this.run({action: 'test', address: this.address}, data => {
            this.result = (data as any).result;
            this.labels = (data as any).labels ?? this.labels;
        });
    }

    protected run(options: { [key: string]: any }, done: (data: GeocodedCounts) => void = null): void {
        this.loading = true;
        this.cdr.detectChanges();
        this.subs.push(this.processService.submit(MapsAdminComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                const data = process?.data as GeocodedCounts;
                if (process?.status !== 'success' || !data) {
                    this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                } else if (done) {
                    done(data);
                } else {
                    this.data = data;
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                this.cdr.detectChanges();
            }
        }));
    }
}
