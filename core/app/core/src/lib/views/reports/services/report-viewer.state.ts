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

import {Injectable} from '@angular/core';
import {ReportParameterValue} from '../report.model';

/**
 * The report shown on a report page: the parameter values it was run with, and
 * its charts as an image. Kept by the report viewer widget for the record actions of the page
 * (Export, Download PDF, Add To Target List), which use the same values as the report.
 */
export interface ShownReport {
    parameters: { [key: string]: ReportParameterValue };
    chartImages: () => Promise<string[]>;
}

@Injectable({providedIn: 'root'})
export class ReportViewerState {

    protected reports = new Map<string, ShownReport>();

    get(id: string): ShownReport | null {
        return this.reports.get(id) ?? null;
    }

    set(id: string, report: ShownReport): void {
        this.reports.set(id, report);
    }

    clear(id: string, report: ShownReport | null = null): void {
        // Not the report of another widget shown since (record opened again).
        if (!report || this.reports.get(id) === report) {
            this.reports.delete(id);
        }
    }
}
