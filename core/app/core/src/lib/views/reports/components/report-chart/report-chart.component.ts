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

import {Component, ElementRef, Input} from '@angular/core';
import {CommonModule} from '@angular/common';
import {NgxChartsModule} from '@swimlane/ngx-charts';
import {LanguageStore} from '../../../../store/language/language.store';
import {ReportChart} from '../../report.model';

/**
 * A chart of the report page, drawn with the charts of SuiteCRM 8 (ngx-charts,
 * "picnic" colours) from the figures of the legacy chart (backend ReportViewerHandler): bars,
 * line, pie, radar, rose (a pie), grouped bars and stacked bars, with the name of the chart.
 * toImage() gives it as a PNG image for the PDF of the report, as the legacy page sends its
 * chart images.
 */
@Component({
    selector: 'scrm-report-chart',
    standalone: true,
    imports: [CommonModule, NgxChartsModule],
    template: `
        <figure class="report-chart">
            <figcaption *ngIf="chart.name" class="report-chart-name">{{ chart.name }}</figcaption>
            <p *ngIf="!chart.items.length" class="report-message">{{ label('LBL_REPORT_VIEWER_NO_DATA') }}</p>
            <!-- ngx-charts sizes the chart on this box: no padding on it. -->
            <div *ngIf="chart.items.length" class="report-chart-box" [style.height.px]="height">
                <ngx-charts-bar-vertical *ngIf="chart.type === 'bar'"
                                         [results]="chart.items" [scheme]="scheme" [animations]="false"
                                         [xAxis]="true" [yAxis]="true" [trimXAxisTicks]="true" [maxXAxisTickLength]="20"
                                         [showDataLabel]="true" [noBarWhenZero]="true">
                </ngx-charts-bar-vertical>
                <ngx-charts-line-chart *ngIf="chart.type === 'line'"
                                       [results]="series" [scheme]="scheme" [animations]="false"
                                       [xAxis]="true" [yAxis]="true" [trimXAxisTicks]="true" [maxXAxisTickLength]="20">
                </ngx-charts-line-chart>
                <ngx-charts-pie-chart *ngIf="chart.type === 'pie' || chart.type === 'rose'"
                                      [results]="chart.items" [scheme]="scheme" [animations]="false"
                                      [labels]="true" [trimLabels]="true" [maxLabelLength]="20">
                </ngx-charts-pie-chart>
                <ngx-charts-polar-chart *ngIf="chart.type === 'radar'"
                                        [results]="series" [scheme]="scheme" [animations]="false"
                                        [xAxis]="true" [yAxis]="true" [trimYAxisTicks]="true" [maxYAxisTickLength]="20">
                </ngx-charts-polar-chart>
                <ngx-charts-bar-vertical-2d *ngIf="chart.type === 'grouped_bar'"
                                            [results]="chart.items" [scheme]="scheme" [animations]="false"
                                            [xAxis]="true" [yAxis]="true" [legend]="true" [legendTitle]="''"
                                            [legendPosition]="legendBelow" [trimXAxisTicks]="true" [maxXAxisTickLength]="20"
                                            [noBarWhenZero]="true">
                </ngx-charts-bar-vertical-2d>
                <ngx-charts-bar-vertical-stacked *ngIf="chart.type === 'stacked_bar'"
                                                 [results]="chart.items" [scheme]="scheme" [animations]="false"
                                                 [xAxis]="true" [yAxis]="true" [legend]="true" [legendTitle]="''"
                                                 [legendPosition]="legendBelow" [trimXAxisTicks]="true" [maxXAxisTickLength]="20"
                                                 [noBarWhenZero]="true">
                </ngx-charts-bar-vertical-stacked>
            </div>
        </figure>
    `
})
export class ReportChartComponent {

    @Input() chart: ReportChart;

    readonly scheme = 'picnic';
    readonly height = 360;
    readonly legendBelow = 'below' as any;

    constructor(protected language: LanguageStore, protected host: ElementRef<HTMLElement>) {
    }

    /** One series (line and radar charts): the Y axis values by X axis value. */
    get series(): { name: string; series: { name: string; value: number }[] }[] {
        return [{name: this.chart.yLabel, series: this.chart.items.map(item => ({name: item.name, value: item.value ?? 0}))}];
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, 'reports') || key;
    }

    /**
     * The chart as a PNG data URL (white background, with its name), null when it has no
     * figures.
     */
    async toImage(): Promise<string | null> {
        const svg = this.host.nativeElement.querySelector('.report-chart-box svg.ngx-charts') as SVGSVGElement;
        if (!svg) {
            return null;
        }

        const box = svg.getBoundingClientRect();
        const width = Math.max(1, Math.round(box.width));
        const height = Math.max(1, Math.round(box.height));
        const titleHeight = this.chart.name ? 32 : 0;
        const copy = svg.cloneNode(true) as SVGSVGElement;
        copy.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        copy.setAttribute('width', String(width));
        copy.setAttribute('height', String(height));
        this.inlineStyles(svg, copy);

        const url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(copy)], {type: 'image/svg+xml;charset=utf-8'}));
        try {
            const image = await new Promise<HTMLImageElement>((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = url;
            });
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height + titleHeight;
            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            if (titleHeight) {
                context.fillStyle = '#2f2c3a';
                context.font = 'bold 14px sans-serif';
                context.textAlign = 'center';
                context.fillText(this.chart.name, width / 2, 20);
            }
            context.drawImage(image, 0, titleHeight, width, height);

            return canvas.toDataURL('image/png');
        } catch (e) {
            return null;
        } finally {
            URL.revokeObjectURL(url);
        }
    }

    /**
     * The styles the page gives to the chart (colours, fonts), set on the copy: the image of an
     * svg does not read the style sheets of the page.
     */
    protected inlineStyles(source: Element, copy: Element): void {
        const style = window.getComputedStyle(source);
        // The pie labels are placed by a CSS transform.
        const properties = ['fill', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-opacity', 'opacity', 'font-family', 'font-size', 'font-weight', 'text-anchor', 'dominant-baseline', 'display', 'transform'];
        (copy as HTMLElement).setAttribute('style', properties.map(name => `${name}:${style.getPropertyValue(name)}`).join(';'));

        for (let index = 0; index < source.children.length; index++) {
            this.inlineStyles(source.children[index], copy.children[index]);
        }
    }
}
