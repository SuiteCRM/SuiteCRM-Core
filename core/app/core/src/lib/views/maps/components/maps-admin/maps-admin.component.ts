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
    /** The geocoding requests of a run of the page, and the note that tells it. */
    requestsLimit?: number;
    limitNote?: string;
    headings: { key: string; label: string }[];
    modules: GeocodedModule[];
    lastStatus: string;
    exportModules: string[];
    cronUrl: string;
    labels: { [key: string]: string };
}

/**
 * The addresses geocoded by a request of the page: an answer within seconds, as OpenStreetMap
 * takes one request per second (and up to three requests for an address it does not find).
 */
const STEP = 5;

/**
 * The pause between two steps with OpenStreetMap, in milliseconds: one request per second at
 * most, between the last address of a step and the first one of the next step too.
 */
const STEP_PAUSE = 1100;

/**
 * The geocoding statuses that end a run.
 */
const STOP_STATUSES = ['OVER_QUERY_LIMIT', 'REQUEST_DENIED', 'UNKNOWN_ERROR'];

/**
 * The geocoding pages of the Google Maps administration, as the legacy jjwg_Maps pages,
 * with the `maps-admin` process (MapsAdmin):
 *  - counts (route maps/geocoded_counts): the geocoded counts of the modules by geocoding answer,
 *    Geocode (the addresses of a module, within the geocoding limits) and Reset of a module, the cron
 *    URL, the export of the addresses to geocode, and the deletion of the address cache;
 *  - geocode (route maps/geocode_addresses): the counts, then geocodes the addresses of all the
 *    modules, as the legacy link;
 *  - test (route maps/geocoding_test): the Google answer for an address.
 *
 * A geocoding run is made of steps of a few addresses, the counts shown after each of them, and
 * can be stopped: with OpenStreetMap a run of 100 addresses lasts two minutes at least, more than
 * a request of the page can.
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
    /** The geocoding run: the module geocoded, its step and the steps of the run. */
    progress: { label: string; step: number; steps: number } = null;
    /** The run ends after its step (Stop, or the page left). */
    stopped = false;
    /** The last geocoding status of the run. */
    lastStatus = '';

    protected subs: Subscription[] = [];
    protected timer: ReturnType<typeof setTimeout>;

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
        // The counts first, with the labels of the page (Process It needs no other answer), then
        // the geocoding of the legacy link.
        this.run({action: 'counts'}, data => {
            this.labels = data.labels;
            if (this.mode !== 'test') {
                this.data = data;
            }
            if (this.mode === 'geocode') {
                this.geocode('');
            }
        });
    }

    ngOnDestroy(): void {
        this.stopped = true;
        clearTimeout(this.timer);
        this.subs.forEach(sub => sub.unsubscribe());
    }

    /** Whether the labels of the page are loaded. */
    get ready(): boolean {
        return !!this.data || Object.keys(this.labels).length > 0;
    }

    label(key: string): string {
        return this.data?.labels?.[key] || this.labels[key] || this.language.getAppString(key) || key;
    }

    /** Whether the Geocode button of a module is shown (with OpenStreetMap, the modules geocoded with it). */
    canGeocode(module: string): boolean {
        return !this.data?.geocodeModules || this.data.geocodeModules.includes(module);
    }

    /**
     * Geocodes the addresses of a module, or of the modules of the Geocode buttons one after the
     * other, by steps: at most the geocoding requests limit of the settings, until Stop or a
     * geocoding error.
     */
    geocode(module: string): void {
        this.stopped = false;
        this.lastStatus = '';
        this.geocodeStep(module ? [module] : (this.data?.geocodeModules ?? []), 1);
    }

    stop(): void {
        this.stopped = true;
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

    /**
     * A step of the geocoding of the first of the modules, then its next step while addresses get
     * geocoded, else the first step of the next module.
     */
    protected geocodeStep(modules: string[], step: number): void {
        const [module, ...others] = modules;
        const left = (this.data?.requestsLimit ?? 0) - (step - 1) * STEP;
        if (!module || left <= 0 || this.stopped) {
            this.progress = null;
            this.cdr.detectChanges();
            return;
        }
        const pending = this.pending(module);
        if (!pending) {
            this.geocodeStep(others, step);
            return;
        }

        this.progress = {label: this.moduleLabel(module), step, steps: Math.ceil(this.data.requestsLimit / STEP)};
        this.run({action: 'geocode', module, limit: Math.min(STEP, left)}, data => {
            this.data = data;
            this.lastStatus = data.lastStatus || this.lastStatus;
            if (STOP_STATUSES.includes(data.lastStatus)) {
                this.progress = null;
                return;
            }
            const next = this.pending(module) < pending ? modules : others;
            this.timer = setTimeout(() => this.geocodeStep(next, step + 1), data.provider === 'osm' ? STEP_PAUSE : 0);
        }, () => this.progress = null);
    }

    /** The addresses of a module that are not geocoded yet. */
    protected pending(module: string): number {
        return this.data?.modules.find(item => item.module === module)?.counts['N/A'] ?? 0;
    }

    protected moduleLabel(module: string): string {
        return this.data?.modules.find(item => item.module === module)?.label ?? module;
    }

    protected run(options: { [key: string]: any }, done: (data: GeocodedCounts) => void = null, failed: () => void = null): void {
        this.loading = true;
        this.cdr.detectChanges();
        this.subs.push(this.processService.submit(MapsAdminComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                const data = process?.data as GeocodedCounts;
                if (process?.status !== 'success' || !data) {
                    this.message.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                    failed?.();
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
                failed?.();
                this.cdr.detectChanges();
            }
        }));
    }
}
