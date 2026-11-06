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

import {ChangeDetectorRef, Component, ElementRef, NgZone, OnDestroy, OnInit, Optional, ViewChild} from '@angular/core';
import {CommonModule} from '@angular/common';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {BaseWidgetComponent} from '../../../../containers/widgets/base-widget.model';
import {Field} from '../../../../common/record/field.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {Record} from '../../../../common/record/record.model';
import {RecordViewStore} from '../../../../views/record/store/record-view/record-view.store';
import {ViewMode} from '../../../../common/views/view.model';
import {MapsConfig, loadGoogleMaps} from './google-maps.loader';

interface AreaPoint {
    lat: number;
    lng: number;
}

/**
 * The map of a map area (record view, bottom widget `area-map`), as the legacy area
 * map pages (area_detail_map, area_edit_map), with the Google Maps API and the settings of the
 * `maps` process. The area is the polygon of its coordinates ("lng,lat,elv" points, as the
 * legacy jjwg_Areas::define_polygon reads them):
 *  - record view: the polygon, the map fitted to it (else the default center);
 *  - edit and create views: a click on the map adds a point, the points are dragged, a right click
 *    removes one; Reset clears the area and Use Area Coordinates writes the points to the
 *    coordinates of the form, as the legacy edit map.
 */
@Component({
    selector: 'scrm-area-map-widget',
    standalone: true,
    imports: [CommonModule],
    template: `
        <div class="map-view-widget">
            <p *ngIf="message" class="map-view-message">{{ message }}</p>
            <div class="map-view-layout" [hidden]="!!message">
                <div #canvas class="map-view-canvas"></div>
                <div *ngIf="editing" class="map-view-info">
                    <strong>{{ label('LBL_AREA_EDIT_TITLE') }}</strong>
                    <p>{{ label('LBL_AREA_EDIT_DESC_1') }}<br>{{ label('LBL_AREA_EDIT_DESC_2') }}</p>
                    <div class="map-view-buttons">
                        <button type="button" class="btn btn-sm btn-outline-secondary" (click)="reset()">{{ label('LBL_AREA_EDIT_RESET') }}</button>
                        <button type="button" class="btn btn-sm btn-main" (click)="useCoordinates()">{{ label('LBL_AREA_EDIT_USE_AREA_COORDINATES') }}</button>
                    </div>
                    <p *ngIf="results !== null"><strong>{{ label('LBL_AREA_EDIT_COORDINATE_RESULTS') }}</strong><br>{{ results }}</p>
                </div>
            </div>
        </div>
    `,
    styles: [`
        .map-view-widget { padding: 8px 0; }
        .map-view-layout { display: flex; flex-wrap: wrap; gap: 16px; }
        .map-view-canvas { flex: 1 1 520px; height: 480px; min-width: 280px; }
        .map-view-info { flex: 0 1 320px; font-size: 13px; word-break: break-word; }
        .map-view-info p { margin: 8px 0; }
        .map-view-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .map-view-message { margin: 0; font-style: italic; }
    `]
})
export class AreaMapWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'maps';

    @ViewChild('canvas', {static: true}) canvas: ElementRef<HTMLDivElement>;

    mapsConfig: MapsConfig = null;
    message = '';
    editing = false;
    results: string = null;

    protected google: any = null;
    protected map: any = null;
    protected polygon: any = null;
    protected clickListener: any = null;
    protected subs: Subscription[] = [];

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected zone: NgZone,
        protected cdr: ChangeDetectorRef,
        @Optional() protected store: RecordViewStore
    ) {
        super();
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.subs.push(this.processService.submit(AreaMapWidgetComponent.PROCESS, {action: 'config'}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.mapsConfig = process?.data as MapsConfig;
                // With OpenStreetMap, no map to place the marker or draw the area on.
                if (this.mapsConfig?.provider === 'osm') {
                    this.message = this.label('LBL_MAPS_VIEW_OSM_AREA');
                    this.cdr.detectChanges();
                    return;
                }
                if (!this.mapsConfig?.apiKey) {
                    this.message = this.label('LBL_MAPS_VIEW_NO_API_KEY');
                    this.cdr.detectChanges();
                    return;
                }
                loadGoogleMaps(this.mapsConfig.apiKey).then(google => {
                    this.google = google;
                    this.watchRecord();
                }).catch(() => {
                    this.message = this.label('LBL_MAPS_VIEW_MAP_LOAD_ERROR');
                    this.cdr.detectChanges();
                });
            },
            error: () => {
                this.message = this.label('LBL_MAPS_VIEW_MAP_LOAD_ERROR');
                this.cdr.detectChanges();
            }
        }));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    label(key: string): string {
        return this.mapsConfig?.labels?.[key] || this.language.getFieldLabel(key, 'areas') || key;
    }

    /**
     * Clears the area (legacy Reset).
     */
    reset(): void {
        this.zone.runOutsideAngular(() => this.polygon?.getPath().clear());
    }

    /**
     * Writes the points of the area to the coordinates of the form, "lng,lat,0" with 8 decimals
     * at most (legacy Use Area Coordinates).
     */
    useCoordinates(): void {
        const path = this.polygon?.getPath()?.getArray() ?? [];
        const coordinates = path.length > 1
            ? path.map((point: any) => this.decimals(point.lng()) + ',' + this.decimals(point.lat()) + ',0').join(' ')
            : '';
        this.results = coordinates;
        const field: Field = this.store?.recordStore?.getStaging()?.fields?.coordinates;
        if (field) {
            field.formControl?.setValue(coordinates);
            field.value = coordinates;
            field.formControl?.markAsDirty();
        }
        this.cdr.detectChanges();
    }

    protected watchRecord(): void {
        if (this.store) {
            this.subs.push(this.store.mode$.subscribe(mode => this.draw(mode, this.store.recordStore.getStaging())));
            this.subs.push(this.store.record$.subscribe(() => this.draw(this.store.getMode(), this.store.recordStore.getStaging())));
            return;
        }
        this.draw('detail', this.context?.record ?? null);
        this.subs.push(this.context$?.subscribe(context => this.draw('detail', context?.record ?? null)));
    }

    protected draw(mode: ViewMode, record: Record): void {
        if (!this.google || !record) {
            return;
        }
        const google = this.google;
        this.editing = mode === 'edit' || mode === 'create';
        this.results = null;
        const points = this.points(record.attributes?.coordinates);

        this.zone.runOutsideAngular(() => {
            if (!this.map) {
                this.map = new google.maps.Map(this.canvas.nativeElement, {
                    zoom: 4,
                    center: new google.maps.LatLng(this.mapsConfig.center.lat, this.mapsConfig.center.lng),
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });
            }

            this.polygon?.setMap(null);
            this.polygon = new google.maps.Polygon({
                paths: points.map(point => new google.maps.LatLng(point.lat, point.lng)),
                editable: this.editing,
                strokeColor: '#000099',
                strokeOpacity: 0.8,
                strokeWeight: 1,
                fillColor: '#000099',
                fillOpacity: 0.15,
                map: this.map
            });

            if (points.length) {
                const bounds = new google.maps.LatLngBounds();
                points.forEach(point => bounds.extend(new google.maps.LatLng(point.lat, point.lng)));
                this.map.fitBounds(bounds);
            } else {
                this.map.setCenter(new google.maps.LatLng(this.mapsConfig.center.lat, this.mapsConfig.center.lng));
                this.map.setZoom(4);
            }

            if (this.clickListener) {
                google.maps.event.removeListener(this.clickListener);
                this.clickListener = null;
            }
            if (this.editing) {
                this.clickListener = this.map.addListener('click', (event: any) => this.polygon.getPath().push(event.latLng));
                this.polygon.addListener('rightclick', (event: any) => {
                    if (event.vertex !== undefined && event.vertex !== null && this.polygon.getPath().getLength() > 2) {
                        this.polygon.getPath().removeAt(event.vertex);
                    }
                });
            }
        });

        this.cdr.detectChanges();
    }

    /**
     * The points of the coordinates: "lng,lat,elv" separated by spaces or new lines, the valid ones
     * only (jjwg_Areas::define_polygon).
     */
    protected points(coordinates: unknown): AreaPoint[] {
        return `${coordinates ?? ''}`.split(/[\n\r\s]+/).filter(Boolean).map(coordinate => {
            const parts = coordinate.split(/[\s()]*,[\s()]*/).filter(Boolean);
            return {lng: Number(parts[0]), lat: Number(parts[1])};
        }).filter(point => Number.isFinite(point.lat) && Number.isFinite(point.lng)
            && point.lat >= -90 && point.lat <= 90 && point.lng >= -180 && point.lng <= 180);
    }

    protected decimals(value: number): string {
        return value.toFixed(8).replace(/\.?0+$/, '');
    }
}
