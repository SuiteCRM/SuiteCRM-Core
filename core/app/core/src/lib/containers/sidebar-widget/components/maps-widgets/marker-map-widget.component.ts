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
import {NumberFormatter} from '../../../../services/formatters/number/number-formatter.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {Record} from '../../../../common/record/record.model';
import {RecordViewStore} from '../../../../views/record/store/record-view/record-view.store';
import {ViewMode} from '../../../../common/views/view.model';
import {MapsConfig, loadGoogleMaps} from './google-maps.loader';

/**
 * The map of a map marker (record view, bottom widget `marker-map`), as the legacy
 * marker map pages (marker_detail_map, marker_edit_map), with the Google Maps API and the settings
 * of the `maps` process:
 *  - record view: the marker (its image) at its latitude and longitude, else the default center;
 *  - edit and create views: the marker is dragged to set the latitude and longitude of the form,
 *    and its closest address goes to the description, as the legacy edit map.
 * The status, position and closest address of the marker are shown next to the map.
 */
@Component({
    selector: 'scrm-marker-map-widget',
    standalone: true,
    imports: [CommonModule],
    template: `
        <div class="map-view-widget">
            <p *ngIf="message" class="map-view-message">{{ message }}</p>
            <div class="map-view-layout" [hidden]="!!message">
                <div #canvas class="map-view-canvas"></div>
                <dl class="map-view-info">
                    <ng-container *ngIf="editing">
                        <dt>{{ label('LBL_MAPS_VIEW_MARKER_STATUS') }}</dt>
                        <dd>{{ status || label('LBL_MAPS_VIEW_DRAG_HELP') }}</dd>
                    </ng-container>
                    <dt>{{ label('LBL_MAPS_VIEW_MARKER_POSITION') }}</dt>
                    <dd>{{ position }}</dd>
                    <dt>{{ label('LBL_MAPS_VIEW_MARKER_ADDRESS') }}</dt>
                    <dd>{{ address }}</dd>
                </dl>
            </div>
        </div>
    `,
    styles: [`
        .map-view-widget { padding: 8px 0; }
        .map-view-layout { display: flex; flex-wrap: wrap; gap: 16px; }
        .map-view-canvas { flex: 1 1 520px; height: 480px; min-width: 280px; }
        .map-view-info { flex: 0 1 320px; margin: 0; font-size: 13px; }
        .map-view-info dt { font-weight: 600; margin-top: 8px; }
        .map-view-info dd { margin: 0; word-break: break-word; }
        .map-view-message { margin: 0; font-style: italic; }
    `]
})
export class MarkerMapWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'maps';

    @ViewChild('canvas', {static: true}) canvas: ElementRef<HTMLDivElement>;

    mapsConfig: MapsConfig = null;
    message = '';
    status = '';
    position = '';
    address = '';
    editing = false;

    protected google: any = null;
    protected map: any = null;
    protected marker: any = null;
    protected geocoder: any = null;
    protected subs: Subscription[] = [];

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected zone: NgZone,
        protected cdr: ChangeDetectorRef,
        protected numberFormatter: NumberFormatter,
        @Optional() protected store: RecordViewStore
    ) {
        super();
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.subs.push(this.processService.submit(MarkerMapWidgetComponent.PROCESS, {action: 'config'}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.mapsConfig = process?.data as MapsConfig;
                // With OpenStreetMap, no map to place the marker or draw the area on.
                if (this.mapsConfig?.provider === 'osm') {
                    this.message = this.label('LBL_MAPS_VIEW_OSM_MARKER');
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
        return this.mapsConfig?.labels?.[key] || this.language.getFieldLabel(key, 'markers') || key;
    }

    /**
     * Draws the marker of the record, again when the record or the mode changes.
     */
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

        const attributes = record.attributes ?? {};
        const lat = Number(attributes.jjwg_maps_lat);
        const lng = Number(attributes.jjwg_maps_lng);
        // No position (empty or 0, as the legacy pages test it): the default center of the settings.
        const hasPosition = Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0);
        const latLng = new google.maps.LatLng(
            hasPosition ? lat : this.mapsConfig.center.lat,
            hasPosition ? lng : this.mapsConfig.center.lng
        );

        this.zone.runOutsideAngular(() => {
            if (!this.map) {
                this.map = new google.maps.Map(this.canvas.nativeElement, {zoom: 4, center: latLng, mapTypeId: google.maps.MapTypeId.ROADMAP});
                this.geocoder = new google.maps.Geocoder();
            } else {
                this.map.setCenter(latLng);
            }

            this.marker?.setMap(null);
            const image = attributes.marker_image;
            this.marker = new google.maps.Marker({
                position: latLng,
                title: attributes.name ?? '',
                map: this.map,
                draggable: this.editing,
                icon: image ? {
                    url: this.mapsConfig.markerImages + encodeURIComponent(image) + '.png',
                    size: new google.maps.Size(32, 37),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(16, 37)
                } : undefined
            });

            if (this.editing) {
                this.marker.addListener('dragstart', () => this.zone.run(() => this.address = this.label('LBL_MAPS_VIEW_DRAGGING')));
                this.marker.addListener('drag', () => this.zone.run(() => {
                    this.status = this.label('LBL_MAPS_VIEW_DRAGGING');
                    this.position = this.text(this.marker.getPosition());
                }));
                this.marker.addListener('dragend', () => this.zone.run(() => {
                    this.status = this.label('LBL_MAPS_VIEW_DRAG_ENDED');
                    this.setPosition(this.marker.getPosition());
                    this.geocode(this.marker.getPosition(), true);
                }));
            }
        });

        this.status = '';
        this.position = this.text(latLng);
        this.geocode(latLng, false);
        this.cdr.detectChanges();
    }

    /**
     * The closest address of a position (legacy geocodePosition); dragged, it also goes to the
     * description of the marker, as the legacy edit map.
     */
    protected geocode(latLng: any, toDescription: boolean): void {
        this.geocoder?.geocode({location: latLng}, (responses: any[], status: string) => this.zone.run(() => {
            this.address = (status === 'OK' && responses?.length) ? responses[0].formatted_address : this.label('LBL_MAPS_VIEW_NO_ADDRESS');
            if (toDescription) {
                this.setField('description', this.address);
            }
            this.cdr.detectChanges();
        }));
    }

    /**
     * The latitude and longitude of the form, with 8 decimals as the legacy edit map.
     */
    protected setPosition(latLng: any): void {
        this.position = this.text(latLng);
        const decimals = this.numberFormatter.getDecimalsSymbol() || '.';
        [['jjwg_maps_lat', latLng.lat()], ['jjwg_maps_lng', latLng.lng()]].forEach(([name, value]) => {
            const coordinate = this.coordinate(value as number);
            // The form shows the coordinate in the user format (CoordinateEditFieldComponent).
            this.setField(name as string, coordinate, coordinate.replace('.', decimals));
        });
    }

    protected setField(name: string, value: string, formValue: string = value): void {
        const field: Field = this.store?.recordStore?.getStaging()?.fields?.[name];
        if (!field) {
            return;
        }
        field.formControl?.setValue(formValue);
        field.value = value;
        field.formControl?.markAsDirty();
    }

    protected coordinate(value: number): string {
        return value.toFixed(8).replace(/\.?0+$/, '');
    }

    protected text(latLng: any): string {
        return latLng ? this.coordinate(latLng.lat()) + ', ' + this.coordinate(latLng.lng()) : '';
    }
}
