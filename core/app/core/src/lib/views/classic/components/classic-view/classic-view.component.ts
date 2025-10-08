/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

import {AfterViewInit, Component, ElementRef, HostListener, NgZone, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {DomSanitizer} from '@angular/platform-browser';
import {IframeResizeHandlerHandler} from '../../services/iframe-resize-handler.service';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {AuthService} from '../../../../services/auth/auth.service';
import {AppStateStore} from '../../../../store/app-state/app-state.store';
import {RouteConverter, RouteInfo} from '../../../../services/navigation/route-converter/route-converter.service';
import {IframePageChangeObserver} from '../../services/iframe-page-change-observer.service';
import {take} from "rxjs/operators";
import {RecordModalOptions} from "../../../../services/modals/record-modal.model";
import {isString} from "lodash-es";

interface RoutingExclusions {
    [key: string]: string[];
}

@Component({
    selector: 'scrm-classic-view-ui',
    templateUrl: './classic-view.component.html',
    styleUrls: []
})
export class ClassicViewUiComponent implements OnInit, OnDestroy, AfterViewInit {

    @ViewChild('dataContainer', {static: true}) dataContainer: ElementRef;

    @HostListener('window:message', ['$event'])
    onMessage(event) {

        if (isString(event.data) && event.data === 'cache-reload') {
            this.auth.clearBackendCacheable();
            this.router.navigateByUrl(this.router.url).then();
            return;
        }

        if (isString(event.data)) {
            return;
        }

        const options = JSON.parse(event.data);

        if (options.type === 'record-modal'){
            const modalOptions = options.params as RecordModalOptions;
            this.loadModal(modalOptions);
        }
    }

    public wrapper: any;
    public url: string;
    protected iframe = null;
    private iframePageChangeHandler: IframePageChangeObserver;
    private iframeResizeHandler: IframeResizeHandlerHandler;

    constructor(
        private route: ActivatedRoute,
        private router: Router,
        private sanitizer: DomSanitizer,
        private routeConverter: RouteConverter,
        private auth: AuthService,
        private ngZone: NgZone,
        private systemConfigs: SystemConfigStore,
        protected appStateStore: AppStateStore,
    ) {
    }

    ngOnInit(): void {
        this.url = this.route.snapshot.data.legacyUrl;
    }

    ngAfterViewInit(): void {
        this.initIframe();
    }

    ngOnDestroy(): void {
        this.cleanObservers();

        this.iframe = null;
        const placeholder = this.wrapper;
        if (this.wrapper.firstChild) {
            placeholder.removeChild(placeholder.firstChild);
        }
        placeholder.innerHTML = '<iframe></iframe>';
        this.wrapper = null;
    }

    cleanObservers(): void {
        if (this.iframeResizeHandler) {
            this.iframeResizeHandler.destroy();
            this.iframeResizeHandler = null;

        }
        if (this.iframePageChangeHandler) {
            this.iframePageChangeHandler.destroy();
            this.iframePageChangeHandler = null;
        }
    }

    initIframe(): void {
        this.wrapper = this.dataContainer.nativeElement;

        if (this.wrapper.firstChild) {
            this.wrapper.removeChild(this.wrapper.firstChild);
        }
        const iframe = document.createElement('iframe');
        iframe.src = this.url;

        this.wrapper.appendChild(iframe);

        this.iframe = iframe;

        this.iframe.style.display = 'block';

        this.initObservers();
    }

    initObservers(): void {
        this.iframePageChangeHandler = this.buildIframePageChangeObserver();
        this.iframeResizeHandler = this.buildIframeResizeHandlerHandler();

        if (this.iframePageChangeHandler) {
            this.iframePageChangeHandler.init();
        }
    }

    protected onPageChange(newLocation): void {

        if (this.shouldRedirect(newLocation) === false) {
            this.iframe.style.display = 'block';
            this.cleanObservers();
            this.initObservers();
            return;
        }

        const location = this.routeConverter.toFrontEndRoute(newLocation);

        if (location === '/users/login') {
            this.auth.logout('LBL_SESSION_EXPIRED');
            return;
        }

        this.ngZone.run(() => this.router.navigateByUrl(location).then()).then();
    }

    protected onIFrameLoad(): void {
        // Do not show scroll at any time, to avoid flickering
        this.iframe.contentWindow.document.body.style.overflow = 'hidden';

        // Init resize handler
        this.iframeResizeHandler.init(this.iframe);

        this.forceCacheRebuildAfterRepairAndRebuild();
    }

    // Temporary solution. Force a cache rebuild after quick repair and rebuild.
    // Can be removed after Repair and Rebuild page is re-done with to Suite8 views.
    protected forceCacheRebuildAfterRepairAndRebuild(): void {
        const iframeUrl = this.iframe.contentWindow.location.href;

        const url = new URL(iframeUrl);
        const params = new URLSearchParams(url.search);

        if (params.get('module') === 'Administration' && params.get('action') === 'repair') {
            this.auth.fetchSessionStatus().pipe(take(1)).subscribe();
        }
    }

    protected onIFrameUnload(): void {
        // hide iframe, while being re-directed
        this.iframe.style.display = 'none';
        this.iframeResizeHandler.destroy();
    }

    protected buildIframePageChangeObserver(): IframePageChangeObserver {
        return new IframePageChangeObserver(
            this.iframe,
            this.onPageChange.bind(this),
            this.onIFrameLoad.bind(this),
            this.onIFrameUnload.bind(this),
        );
    }

    protected loadModal(options: RecordModalOptions): void {
        this.appStateStore.recordModalOpenEventEmitter.emit(options);
    }

    protected buildIframeResizeHandlerHandler(): IframeResizeHandlerHandler {
        return new IframeResizeHandlerHandler();
    }

    /**
     * Check if should re-direct to link or if it was excluded
     *
     * @param {string} legacyLink to check
     * @returns {boolean} should redirect
     */
    protected shouldRedirect(legacyLink: string): boolean {

        if (legacyLink && legacyLink.includes('/#/')) {
            return true;
        }

        const routeInfo = this.routeConverter.parse(legacyLink);

        // if no route or no module, don't re-direct
        if (!routeInfo || !routeInfo.module) {
            return false;
        }

        const reuse = this.routeConverter.matchesActiveRoute(this.route, routeInfo);

        if (reuse === true) {
            return false;
        }

        if (!routeInfo.action) {
            return true;
        }

        return this.toExclude(routeInfo);
    }

    /**
     * Check if given route is to exclude from redirection
     *
     * @param {object} routeInfo to check
     * @returns {boolean} is to exclude
     */
    protected toExclude(routeInfo: RouteInfo): boolean {
        const exclusions: RoutingExclusions = this.systemConfigs.getConfigValue('classicview_routing_exclusions');

        if (!exclusions || Object.keys(exclusions).length === 0) {
            return true;
        }

        // if action is excluded for any module, don't re-direct
        if (exclusions.any && exclusions.any.includes(routeInfo.action)) {
            return false;
        }

        if (!exclusions[routeInfo.module]) {
            return true;
        }

        // if module action is excluded, don't re-direct
        const moduleExclusions = exclusions[routeInfo.module];
        return !(moduleExclusions && moduleExclusions.includes(routeInfo.action));
    }
}
