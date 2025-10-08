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

import {Component, ElementRef, Input, signal} from '@angular/core';
import {fromEvent, Subscription} from "rxjs";
import {debounceTime, tap} from "rxjs/operators";
import {ChartDataSource} from '../../../../common/containers/chart/chart.model';
import {ScreenSizeObserverService} from "../../../../services/ui/screen-size-observer/screen-size-observer.service";

@Component({template: ''})
export class BaseChartComponent {
    @Input() dataSource: ChartDataSource;

    height = 300;
    view = signal([300, this.height]);
    protected subs: Subscription[] = [];

    constructor(protected elementRef: ElementRef, protected screenSize: ScreenSizeObserverService) {
    }

    onResize(): void {
        this.calculateView();
    }

    protected initResizeListener(): void {
        const resize$ = fromEvent(window, 'resize').pipe(
            tap(() => this.view.set([])),
            debounceTime(300),
        );

        this.view.set([])
        this.subs.push(resize$.pipe(debounceTime(50)).subscribe(() => {
            this.calculateView();
        }));
    }

    protected calculateView(): void {
        let width;
        const el = (this.elementRef && this.elementRef.nativeElement) || {} as HTMLElement;
        const parentEl = (el.parentElement && el.parentElement.parentElement) || {} as HTMLElement;
        const parentWidth = (parentEl && parentEl.offsetWidth) || 0;

        if (parentWidth > 0) {
            width = parentWidth;
        } else {
            width = window.innerWidth * 0.7;

            if (window.innerWidth > 990) {
                width = window.innerWidth * 0.23;
            }
        }
        this.view.set([width, this.height]);
    }

}
