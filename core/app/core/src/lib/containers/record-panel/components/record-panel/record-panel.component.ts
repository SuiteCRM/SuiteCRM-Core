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

import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {ScreenSizeMap} from '../../../../common/services/ui/resize.model';
import {isVoid} from '../../../../common/utils/value-utils';
import {BehaviorSubject, Observable, of} from 'rxjs';
import {shareReplay} from 'rxjs/operators';
import {RecordGridConfig} from '../../../../components/record-grid/record-grid.model';
import {RecordPanelConfig} from './record-panel.model';

@Component({
    selector: 'scrm-record-panel',
    templateUrl: './record-panel.component.html',
    styleUrls: [],
})
export class RecordPanelComponent implements OnInit, OnDestroy {

    @Input() config: RecordPanelConfig;
    closeButton: ButtonInterface;
    panelMode: 'collapsible' | 'closable' | 'none' = 'closable';
    isCollapsed$: Observable<boolean>;

    vm$: Observable<any>;

    protected collapse: BehaviorSubject<boolean>;

    constructor() {
    }

    ngOnInit(): void {
        this.vm$ = this.config.store.vm$;

        this.initCloseButton();

        if (this.config.panelMode) {
            this.panelMode = this.config.panelMode;
        }

        this.collapse = new BehaviorSubject<boolean>(false);
        this.isCollapsed$ = this.collapse.asObservable();
        if (!isVoid(this.config.isCollapsed)) {
            this.collapse.next(this.config.isCollapsed);
        }

    }

    ngOnDestroy(): void {
    }

    getGridConfig(): RecordGridConfig {

        return {
            record$: this.config.store.stagingRecord$,
            mode$: this.config.store.mode$,
            fields$: this.config.store.getViewFieldsKeys$(),
            actions: this.config.actions,
            klass: 'mt-2 rounded',
            buttonClass: 'btn btn-outline-danger btn-sm',
            maxColumns$: of(4).pipe(shareReplay(1)),
            sizeMap$: of({
                handset: 1,
                tablet: 2,
                web: 3,
                wide: 4
            } as ScreenSizeMap).pipe(shareReplay(1)),
        } as RecordGridConfig;
    }

    /**
     * Init close button
     */
    protected initCloseButton(): void {

        this.closeButton = {
            onClick: (): void => {
                this.config.onClose();
            }
        } as ButtonInterface;
    }

}
