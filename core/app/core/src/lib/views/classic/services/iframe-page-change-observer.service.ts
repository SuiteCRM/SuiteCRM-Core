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

export class IframePageChangeObserver {
    private iframe: any;
    private lastDispatched: string;
    private changeCallback: Function = null;
    private loadCallback: Function = null;
    private unLoadCallback: Function = null;
    private unloadListener: Function = null;
    private loadListener: Function = null;

    constructor(
        iframe,
        changeCallback: Function = null,
        loadCallback: Function = null,
        unLoadCallback: Function = null,
    ) {
        this.iframe = iframe;
        this.changeCallback = changeCallback;
        this.loadCallback = loadCallback;
        this.unLoadCallback = unLoadCallback;
    }

    /**
     * Public Api
     */

    public init(): void {
        this.loadListener = this.loadHandler.bind(this);
        this.unloadListener = this.unloadHandler.bind(this);
        this.iframe.contentWindow.addEventListener('load', this.loadListener);
        this.iframe.contentWindow.removeEventListener('unload', this.unloadListener);
    }

    public destroy(): void {

        const contentWindow = this.iframe && this.iframe.contentWindow;

        if (contentWindow) {
            contentWindow.removeEventListener('unload', this.unloadListener);
            contentWindow.removeEventListener('load', this.loadListener);
        }
        this.iframe = null;
        this.lastDispatched = null;
        this.changeCallback = null;
        this.loadCallback = null;
        this.unLoadCallback = null;
        this.loadListener = null;
        this.unloadListener = null;
    }


    /**
     * Internal API
     */

    protected loadHandler(): void {
        this.loadCallback();
        this.bindUnload();
    }

    protected bindUnload(): void {
        this.iframe.contentWindow.removeEventListener('unload', this.unloadListener);
        this.unloadListener = this.unloadHandler.bind(this);
        this.iframe.contentWindow.addEventListener('unload', this.unloadListener);
    }

    protected unloadHandler(): void {
        this.unLoadCallback();

        // Timeout needed because the URL changes immediately after
        // the `unload` event is dispatched.
        setTimeout(this.triggerPageChange.bind(this), 0);
    }

    protected triggerPageChange(): void {
        const newHref = this.iframe && this.iframe.contentWindow && this.iframe.contentWindow.location.href;

        if (newHref && newHref !== this.lastDispatched) {
            this.lastDispatched = newHref;
            this.changeCallback(newHref);
        }
    }

}
