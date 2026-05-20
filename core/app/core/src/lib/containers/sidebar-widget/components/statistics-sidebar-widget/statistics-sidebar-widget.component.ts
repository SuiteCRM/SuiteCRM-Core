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

import {Component, OnDestroy, OnInit} from '@angular/core';
import {ViewContext} from '../../../../common/views/view.model';
import {StatisticWidgetOptions, WidgetMetadata} from '../../../../common/metadata/widget.metadata';
import {Subscription} from 'rxjs';
import {LanguageStore} from '../../../../store/language/language.store';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {GridWidgetConfig, StatisticsQueryArgs} from '../../../../components/grid-widget/grid-widget.component';
import {PanelCollapseMode} from "../../../../components/panel/panel.component";

@Component({
    selector: 'scrm-statistics-sidebar-widget',
    templateUrl: './statistics-sidebar-widget.component.html',
    styles: []
})
export class StatisticsSidebarWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    options: StatisticWidgetOptions;

    protected subs: Subscription[] = [];

    constructor(protected language: LanguageStore
    ) {
        super();
    }

    ngOnInit(): void {

        super.ngOnInit();

        const options = this.config.options || {};
        this.options = options.sidebarStatistic || null;

        if (this.context$) {
            this.subs.push(this.context$.subscribe((context: ViewContext) => {
                this.context = context;
            }));
        }
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    getHeaderLabel(): string {
        return this.getLabel(this.config.labelKey) || '';
    }

    getLabel(key: string): string {
        const context = this.context || {} as ViewContext;
        const module = context.module || '';

        return this.language.getFieldLabel(key, module);
    }

    getGridConfig(): GridWidgetConfig {
        return {
            rowClass: 'statistics-sidebar-widget-row',
            columnClass: 'statistics-sidebar-widget-col',
            layout: this.options,
            widgetConfig: {reload$: this.config.reload$} as WidgetMetadata,
            queryArgs: {
                module: this.context.module,
                context: this.context,
                params: {},
            } as StatisticsQueryArgs,
        } as GridWidgetConfig;
    }

}
