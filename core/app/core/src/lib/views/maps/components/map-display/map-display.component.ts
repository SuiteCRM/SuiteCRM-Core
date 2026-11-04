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

import {ChangeDetectorRef, Component, ElementRef, Input, NgZone, OnChanges, OnDestroy, ViewChild} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {MapsConfig, loadGoogleMaps} from '../../../../containers/sidebar-widget/components/maps-widgets/google-maps.loader';

export interface MapMarker {
    id: string;
    name: string;
    module?: string;
    address?: string;
    lat: string | number;
    lng: string | number;
    phone?: string;
    account_name?: string;
    account_id?: string;
    assigned_user_name?: string;
    image?: string;
    group?: string;
}

interface MapArea {
    id: string;
    name: string;
    points: { lat: number; lng: number }[];
}

interface MapMarkersData {
    error?: string;
    center: MapMarker;
    markers: MapMarker[];
    groups: string[];
    customMarkers: MapMarker[];
    customAreas: MapArea[];
    displayModule: string;
    targetLists: { id: string; name: string }[];
    moduleLabels: { [module: string]: string };
    labels: { [key: string]: string };
}

/**
 * A map of the maps module (legacy jjwg_Maps map_markers page), with the Google Maps API
 * and the `maps` process, which runs the legacy page logic: the center record or address, the
 * records of the display module within the distance (or of a target list), grouped (group icons and
 * legend, a group is hidden by its legend checkbox), the custom markers and areas of a map record,
 * and the table of the records: their link, address, phone, group and user; the selected ones are
 * added to a target list (accounts, contacts, leads, targets, users), as the legacy page. Without
 * Google Maps API key, the table only.
 *
 * Used by the map widget of the map records and by the quick radius page.
 */
@Component({
    selector: 'scrm-map-display',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './map-display.component.html'
})
export class MapDisplayComponent implements OnChanges, OnDestroy {

    static readonly PROCESS = 'maps';

    /** The options of the `markers` action (record | relate_module, relate_id | quick_address...). */
    @Input() params: { [key: string]: string } = null;

    @ViewChild('canvas', {static: true}) canvas: ElementRef<HTMLDivElement>;

    data: MapMarkersData = null;
    mapsConfig: MapsConfig = null;
    message = '';
    loading = false;
    hiddenGroups: { [group: string]: boolean } = {};
    selected: { [id: string]: boolean } = {};
    targetList = '';

    protected google: any = null;
    protected map: any = null;
    protected overlays: any[] = [];
    protected groupMarkers: { [group: string]: any[] } = {};
    protected infoWindow: any = null;
    protected subs: Subscription[] = [];

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected messages: MessageService,
        protected moduleNameMapper: ModuleNameMapper,
        protected zone: NgZone,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(): void {
        if (!this.params) {
            return;
        }
        this.load();
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    label(key: string): string {
        return this.data?.labels?.[key] || this.mapsConfig?.labels?.[key] || this.language.getAppString(key) || key;
    }

    groupLabel(group: string): string {
        return group ? group : '{' + this.label('LBL_MAP_NULL_GROUP_NAME') + '}';
    }

    /** The icon of a group (legacy group icons, the folder chosen by the number of groups). */
    groupIcon(group: string): string {
        return this.iconsDir() + 'marker_' + ((this.data?.groups?.indexOf(group) ?? -1) + 1) + '.png';
    }

    centerIcon(): string {
        return this.iconsDir() + 'marker_0.png';
    }

    /** The SuiteCRM 8 record route of a marker. */
    recordLink(module: string, id: string): string {
        return '#/' + this.moduleNameMapper.toFrontend(module) + '/record/' + id;
    }

    canAddToTargetList(): boolean {
        return ['Accounts', 'Contacts', 'Leads', 'Prospects', 'Users'].includes(this.data?.displayModule || this.data?.markers?.[0]?.module);
    }

    selectAll(checked: boolean): void {
        this.selected = {};
        if (checked) {
            this.data?.markers?.forEach(marker => this.selected[marker.id] = true);
        }
    }

    selectedIds(): string[] {
        return Object.keys(this.selected).filter(id => this.selected[id]);
    }

    toggleGroup(group: string, visible: boolean): void {
        this.hiddenGroups[group] = !visible;
        this.zone.runOutsideAngular(() => (this.groupMarkers[group] ?? []).forEach(marker => marker.setMap(visible ? this.map : null)));
    }

    /** Opens the info window of a marker of the table on the map. */
    show(marker: MapMarker): void {
        const shown = this.overlays.find(overlay => overlay.markerId === marker.id);
        if (shown && this.map) {
            this.zone.runOutsideAngular(() => {
                this.map.panTo(shown.getPosition());
                this.openInfo(shown, marker);
            });
        }
    }

    addToTargetList(): void {
        const ids = this.selectedIds();
        if (!ids.length || !this.targetList) {
            return;
        }
        const module = this.data.displayModule || this.data.markers?.[0]?.module;
        this.subs.push(this.processService.submit(MapDisplayComponent.PROCESS, {action: 'addToTargetList', listId: this.targetList, module, ids})
            .pipe(take(1)).subscribe((process: Process) => {
                if (process?.status === 'success') {
                    this.messages.addSuccessMessage(this.label('LBL_MAPS_VIEW_MAP_ADDED') + ': ' + (process.data as any)?.list + ' (' + ids.length + ')');
                    this.selected = {};
                } else {
                    this.messages.addDangerMessage(this.language.getAppString('LBL_ACTION_ERROR') || 'Error');
                }
                this.cdr.detectChanges();
            }));
    }

    protected load(): void {
        this.loading = true;
        this.message = '';
        this.selected = {};
        this.hiddenGroups = {};
        this.cdr.detectChanges();
        this.subs.push(this.processService.submit(MapDisplayComponent.PROCESS, {action: 'config'}).pipe(take(1)).subscribe(config => {
            this.mapsConfig = config?.data as MapsConfig;
            this.subs.push(this.processService.submit(MapDisplayComponent.PROCESS, {...this.params, action: 'markers'})
                .pipe(take(1)).subscribe((process: Process) => {
                    this.loading = false;
                    // An error (address not found...) comes without the lists.
                    this.data = process?.data ? {
                        center: null, markers: [], groups: [], customMarkers: [], customAreas: [], targetLists: [],
                        moduleLabels: {}, labels: {}, displayModule: '', ...(process.data as MapMarkersData)
                    } : null;
                    if (process?.status !== 'success' || !this.data) {
                        this.message = this.language.getAppString('LBL_ACTION_ERROR') || 'Error';
                    } else if (this.data.error) {
                        this.message = this.data.error;
                    } else if (!this.mapsConfig?.apiKey) {
                        this.message = this.label('LBL_MAPS_VIEW_NO_API_KEY');
                    } else {
                        loadGoogleMaps(this.mapsConfig.apiKey).then(google => {
                            this.google = google;
                            this.draw();
                        }).catch(() => {
                            this.message = this.label('LBL_MAPS_VIEW_MAP_LOAD_ERROR');
                            this.cdr.detectChanges();
                        });
                    }
                    this.cdr.detectChanges();
                }));
        }));
    }

    protected draw(): void {
        const google = this.google;
        const data = this.data;
        this.zone.runOutsideAngular(() => {
            const center = data.center && this.valid(data.center)
                ? new google.maps.LatLng(Number(data.center.lat), Number(data.center.lng))
                : new google.maps.LatLng(this.mapsConfig.center.lat, this.mapsConfig.center.lng);
            if (!this.map) {
                this.map = new google.maps.Map(this.canvas.nativeElement, {zoom: 4, center, mapTypeId: google.maps.MapTypeId.ROADMAP});
                this.infoWindow = new google.maps.InfoWindow();
            }
            this.overlays.forEach(overlay => overlay.setMap(null));
            this.overlays = [];
            this.groupMarkers = {};
            const bounds = new google.maps.LatLngBounds();

            if (data.center && this.valid(data.center)) {
                this.addMarker(data.center, this.centerIcon(), bounds);
            }
            data.markers.forEach(marker => {
                if (!this.valid(marker)) {
                    return;
                }
                const overlay = this.addMarker(marker, this.groupIcon(marker.group ?? ''), bounds);
                (this.groupMarkers[marker.group ?? ''] = this.groupMarkers[marker.group ?? ''] ?? []).push(overlay);
            });
            data.customMarkers.forEach(marker => {
                if (this.valid(marker)) {
                    const image = marker.image && marker.image !== 'None' ? this.mapsConfig.markerImages + encodeURIComponent(marker.image) + '.png' : undefined;
                    this.addMarker(marker, image, bounds);
                }
            });
            data.customAreas.forEach(area => {
                const polygon = new google.maps.Polygon({
                    paths: area.points.map(point => new google.maps.LatLng(point.lat, point.lng)),
                    strokeColor: '#000099',
                    strokeOpacity: 0.8,
                    strokeWeight: 1,
                    fillColor: '#000099',
                    fillOpacity: 0.15,
                    map: this.map
                });
                area.points.forEach(point => bounds.extend(new google.maps.LatLng(point.lat, point.lng)));
                this.overlays.push(polygon);
            });

            if (!bounds.isEmpty()) {
                this.map.fitBounds(bounds);
            } else {
                this.map.setCenter(center);
            }
        });
        this.cdr.detectChanges();
    }

    protected addMarker(marker: MapMarker, icon: string, bounds: any): any {
        const google = this.google;
        const position = new google.maps.LatLng(Number(marker.lat), Number(marker.lng));
        const overlay = new google.maps.Marker({position, title: marker.name, map: this.map, icon});
        overlay.markerId = marker.id;
        overlay.addListener('click', () => this.openInfo(overlay, marker));
        bounds.extend(position);
        this.overlays.push(overlay);

        return overlay;
    }

    /** The info window of a marker: its name (record link), address, phone, account and user. */
    protected openInfo(overlay: any, marker: MapMarker): void {
        const content = document.createElement('div');
        const title = document.createElement(marker.module ? 'a' : 'strong');
        title.textContent = marker.name;
        if (marker.module && marker.id) {
            (title as HTMLAnchorElement).href = this.recordLink(marker.module, marker.id);
        }
        content.appendChild(title);
        [marker.address, marker.phone, marker.account_name, marker.assigned_user_name].filter(Boolean).forEach(text => {
            const line = document.createElement('div');
            line.textContent = text;
            content.appendChild(line);
        });
        this.infoWindow.setContent(content);
        this.infoWindow.open(this.map, overlay);
    }

    protected valid(marker: MapMarker): boolean {
        const lat = Number(marker?.lat);
        const lng = Number(marker?.lng);

        return Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0);
    }

    protected iconsDir(): string {
        const count = this.data?.groups?.length ?? 0;
        const dir = count <= 10 ? '0-10' : count <= 25 ? '0-25' : count <= 100 ? '0-100' : '0-216';

        return 'legacy/themes/default/images/jjwg_Maps/' + dir + '/';
    }
}
