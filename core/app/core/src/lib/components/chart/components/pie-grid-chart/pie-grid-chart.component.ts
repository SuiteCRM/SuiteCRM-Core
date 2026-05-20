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

import {Component, ElementRef, OnDestroy, OnInit} from '@angular/core';
import {SeriesResult, SingleSeries} from '../../../../common/containers/chart/chart.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {BaseChartComponent} from '../base-chart/base-chart.component';
import {debounceTime} from "rxjs/operators";
import {ScreenSizeObserverService} from "../../../../services/ui/screen-size-observer/screen-size-observer.service";

@Component({
    selector: 'scrm-pie-grid-chart',
    templateUrl: './pie-grid-chart.component.html',
    styleUrls: []
})
export class PieGridChartComponent extends BaseChartComponent implements OnInit, OnDestroy {
    results: SingleSeries;
    height = 700;
    minWidth = 100;

    constructor(
        public language: LanguageStore,
        protected elementRef: ElementRef,
        protected screenSize: ScreenSizeObserverService
    ) {
        super(elementRef, screenSize);
    }

    ngOnInit(): void {
        if (this.dataSource.options.height) {
            this.height = this.dataSource.options.height;
        }

        if (this?.dataSource?.options?.minWidth) {
            this.minWidth = this.dataSource.options.minWidth;
        }

        this.initResizeListener();

        this.subs.push(this.dataSource.getResults().pipe(debounceTime(500)).subscribe(value => {
            this.parseResults(value);
            this.calculateHeightBasedOnResults();
            this.calculateView();

        }));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    get scheme(): string {
        return this.dataSource.options.scheme || 'picnic';
    }

    get label(): string {
        return this.dataSource.options.label || '';
    }

    onResize(): void {
        this.calculateHeightBasedOnResults();
        this.calculateView();
    }

    protected calculateHeightBasedOnResults(): void {
        if (this.results && this.results.length) {
            const perRow = Math.floor(this.view()[0] / 170);
            this.height = (Math.floor(this.results.length / perRow) * 200);
        } else {
            this.height = 50;
        }
    }

    protected parseResults(value: SeriesResult): void {
        this.results = [];

        if (value.singleSeries && value.singleSeries.length) {

            value.singleSeries.forEach(entry => {
                const parsedValue = parseFloat('' + entry.value);
                if (!parsedValue) {
                    this.results.push(entry);
                    return;
                }
                this.results.push({
                    name: entry.name,
                    value: parsedValue
                });
            });
        }
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

        if (width > 239) {
            this.view.set([width, this.height]);
            return;
        }
        this.view.set([width, 800]);
    }

}
