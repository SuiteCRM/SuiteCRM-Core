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

import {AfterViewInit, Component, ElementRef, Input, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {IframeResizeHandlerHandler} from "../../../../views/classic/services/iframe-resize-handler.service";
import {IframePageChangeObserver} from "../../../../views/classic/services/iframe-page-change-observer.service";
import {NgbActiveModal} from "@ng-bootstrap/ng-bootstrap";
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {ModalCloseFeedBack} from '../../../../common/components/modal/modal.model';
import {animate, transition, trigger} from "@angular/animations";
import {LanguageStore} from "../../../../store/language/language.store";


@Component({
    selector: 'scrm-classic-modal',
    templateUrl: './classic-modal.component.html',
    styleUrls: [],
    animations: [
        trigger('modalFade', [
            transition('void <=> *', [
                animate('800ms')
            ]),
        ]),
    ]
})
export class ClassicModalComponent implements OnInit, OnDestroy, AfterViewInit {
    @Input() url: string = '';
    @Input() titleKey: string = '';
    @Input() asyncActionCallback: Function = null;
    @ViewChild('dataContainer', {static: true}) dataContainer: ElementRef;
    closeButton: ButtonInterface;

    public wrapper: any;
    protected iframe = null;
    private iframePageChangeHandler: IframePageChangeObserver;
    private iframeResizeHandler: IframeResizeHandlerHandler;

    constructor(
        public languageStore: LanguageStore,
        protected activeModal: NgbActiveModal,
    ) {
    }

    ngOnInit(): void {
        this.closeButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: (): void => {
                this.activeModal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;
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

    protected onIFrameLoad(): void {
        // Do not show scroll at any time, to avoid flickering
        this.iframe.contentWindow.document.body.style.overflow = 'hidden';

        // callback function to execute custom task
        // as required by the caller
        if (this.asyncActionCallback !== null) {
            this.asyncActionCallback(this.iframe);
        }

        // Init resize handler
        this.iframeResizeHandler.init(this.iframe);
    }

    protected onIFrameUnload(): void {
        // hide iframe, while being re-directed
        this.iframe.style.display = 'none';
        this.iframeResizeHandler.destroy();
    }

    protected buildIframePageChangeObserver(): IframePageChangeObserver {
        return new IframePageChangeObserver(
            this.iframe,
            null,
            this.onIFrameLoad.bind(this),
            this.onIFrameUnload.bind(this),
        );
    }

    protected buildIframeResizeHandlerHandler(): IframeResizeHandlerHandler {
        return new IframeResizeHandlerHandler();
    }

}
