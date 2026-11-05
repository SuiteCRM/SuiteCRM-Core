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

import {Component, ElementRef, Input, NgZone, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {CommonModule} from '@angular/common';
import {DomSanitizer, SafeResourceUrl} from '@angular/platform-browser';
import {Router} from '@angular/router';
import {IframePageChangeObserver} from '../../../classic/services/iframe-page-change-observer.service';
import {RouteConverter} from '../../../../services/navigation/route-converter/route-converter.service';

/**
 * A dashlet of the dashboard that is not rendered natively (reports,
 * tools...): the legacy page modules/Home/Dashlet.php shows it alone in a
 * frame, with its own scripts (pages, sort, charts).
 *
 * The frame takes the height of the dashlet. A link of the dashlet (a record
 * of a report, for example) opens in the app, as from the classic views,
 * instead of inside the frame.
 */
@Component({
    selector: 'scrm-legacy-dashlet',
    standalone: true,
    imports: [CommonModule],
    template: `<iframe #frame class="dashboard-frame" [title]="title" [src]="url" loading="lazy"></iframe>`
})
export class LegacyDashletComponent implements OnInit, OnDestroy {

    @Input() id: string;
    @Input() title = '';

    @ViewChild('frame', {static: true}) frame: ElementRef<HTMLIFrameElement>;

    url: SafeResourceUrl;

    protected static readonly MIN_HEIGHT = 80;

    protected pageObserver: IframePageChangeObserver | null = null;
    protected mutationObserver: MutationObserver | null = null;
    protected resizeObserver: ResizeObserver | null = null;

    constructor(
        protected router: Router,
        protected routeConverter: RouteConverter,
        protected ngZone: NgZone,
        protected sanitizer: DomSanitizer
    ) {
    }

    ngOnInit(): void {
        this.url = this.sanitizer.bypassSecurityTrustResourceUrl(this.legacyUrl());

        const iframe = this.frame.nativeElement;
        iframe.style.height = LegacyDashletComponent.MIN_HEIGHT + 'px';

        this.pageObserver = new IframePageChangeObserver(
            iframe,
            (location: string) => this.onPageChange(location),
            () => this.onLoad()
        );
        this.pageObserver.init();
    }

    ngOnDestroy(): void {
        this.pageObserver?.destroy();
        this.stopWatching();
    }

    /** Reloads the dashlet (refresh button of the dashlet). */
    refresh(): void {
        this.frame.nativeElement.contentWindow?.location.reload();
    }

    protected legacyUrl(): string {
        return './legacy/index.php?module=Home&action=Dashlet&id=' + encodeURIComponent(this.id);
    }

    /** Another page than the dashlet: open it in the app. */
    protected onPageChange(location: string): void {
        if (!location || location.includes('action=Dashlet')) {
            return;
        }

        const route = this.routeConverter.toFrontEndRoute(location);
        this.ngZone.run(() => this.router.navigateByUrl(route));
    }

    protected onLoad(): void {
        this.stopWatching();

        const iframe = this.frame.nativeElement;
        const frameWindow = iframe.contentWindow as any;
        const body = frameWindow?.document?.body;
        if (!body) {
            return;
        }

        // The frame grows with the dashlet; a wide report table scrolls sideways.
        body.style.overflowY = 'hidden';
        body.style.overflowX = 'auto';
        this.resize();

        // Charts and report pages are drawn after the load, by the dashlet scripts.
        this.mutationObserver = new frameWindow.MutationObserver(() => this.resize());
        this.mutationObserver.observe(body, {childList: true, subtree: true, attributes: true});
        if (frameWindow.ResizeObserver) {
            this.resizeObserver = new frameWindow.ResizeObserver(() => this.resize());
            this.resizeObserver.observe(body);
        }
    }

    protected resize(): void {
        const iframe = this.frame?.nativeElement;
        const document = iframe?.contentWindow?.document;
        const content = document?.getElementById('dashlet_' + this.id) ?? document?.body;
        if (!content) {
            return;
        }

        // Room for the horizontal scrollbar of a wide dashlet.
        const body = document.body;
        const scrollbar = body.scrollWidth > body.clientWidth ? body.offsetHeight - body.clientHeight || 17 : 0;
        // Bottom of the dashlet in the page: its height plus the page margins above it.
        const height = Math.max(
            LegacyDashletComponent.MIN_HEIGHT,
            Math.ceil(content.getBoundingClientRect().bottom + (iframe.contentWindow.scrollY || 0)) + 12 + scrollbar
        );
        if (iframe.style.height !== height + 'px') {
            iframe.style.height = height + 'px';
        }
    }

    protected stopWatching(): void {
        this.mutationObserver?.disconnect();
        this.resizeObserver?.disconnect();
        this.mutationObserver = null;
        this.resizeObserver = null;
    }
}
