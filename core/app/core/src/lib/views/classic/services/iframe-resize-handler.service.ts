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

import {MutationObserverFactory} from '@angular/cdk/observers';

interface ResizeFeedack {
    resized: boolean;
    height?: number;
}

export class IframeResizeHandlerHandler {
    private iframe: any;
    private observer: MutationObserver;

    constructor() {
    }

    /**
     * Public Api
     */

    public init(iframe): void {
        this.iframe = iframe;
        this.initialSizeAdjustment();
        this.addBodyResizeListener();
    }

    public destroy(): void {

        if (this.observer){
            this.observer.disconnect();
        }

        this.iframe = null;
        this.observer = null;
    }

    /**
     * Protected Api
     */

    protected initialSizeAdjustment(): void {
        setTimeout(this.onResize.bind(this), 200);
    }

    protected addBodyResizeListener(): void {
        const watchedData = {
            document: this.iframe.contentWindow.document,
            element: this.iframe.contentWindow.document.body,
            height: this.iframe.contentWindow.document.body.scrollHeight,
            watched: [
                {
                    getHeight: (): number => {
                        const modals = watchedData.document.getElementsByClassName('modal in');
                        const modal = modals && modals[0];
                        const modalDialog = modal && modal.firstElementChild;
                        return (modalDialog && (modalDialog.scrollHeight + 200)) || -1;
                    }
                },
                {
                    getHeight: (): number => {
                        const container = watchedData.document.getElementById('bootstrap-container');
                        const containerHeight = (container && container.scrollHeight + 150) || -1;

                        if (containerHeight > 0) {
                            return containerHeight;
                        }

                        return watchedData.element.scrollHeight + 50;
                    }
                }
            ]
        };

        this.observer = (new MutationObserverFactory()).create(() => {
            const heights = [];
            watchedData.watched.forEach((watched) => {
                const elHeight = watched.getHeight();

                if (elHeight > 0) {
                    heights.push(elHeight);
                }
            });

            heights.sort((a, b) => a - b);
            // consider the Height of the biggest
            const elementHeight = heights.pop();

            const feedback = this.calculateResizing(watchedData.height, elementHeight);

            if (feedback.resized){
                watchedData.height = feedback.height;
            }
        });

        this.observer.observe(this.iframe.contentWindow.document.body, {
            attributes: true,
            childList: true,
            characterData: true,
            subtree: true
        });
    }

    protected calculateResizing(currentHeight: number, elementHeight: number): ResizeFeedack {
        const availableWindowHeight = window.innerHeight - 50;

        // if window size is bigger, take that size
        if (elementHeight < availableWindowHeight){
            elementHeight = availableWindowHeight;
        }

        if (currentHeight === elementHeight) {
            return {
                resized: false
            };
        }

        if (currentHeight < (elementHeight - 20)) {
            this.resizeIFrame(elementHeight);
            return {
                resized: true,
                height: elementHeight
            };
        }

        if (this.iframe.scrollHeight > elementHeight) {
            this.resizeIFrame(elementHeight);
            return {
                resized: true,
                height: elementHeight
            };
        }

        return {
            resized: false
        };
    }

    protected onResize(): void {
        const elementHeight = this.iframe.contentWindow.document.body.scrollHeight + 120;
        const currentHeight = this.iframe.offsetHeight;
        this.calculateResizing(currentHeight, elementHeight);
    }

    protected resizeIFrame(size: number): void {
        const adjustedValue = size + 1;
        this.iframe.style.height = adjustedValue + 'px';
    }
}
