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
import {Field} from '../../../common/record/field.model';
import {FieldManager} from '../../../services/record/field/field.manager';
import {LanguageStore} from '../../../store/language/language.store';
import {Record} from '../../../common/record/record.model';
import {ViewFieldDefinition} from '../../../common/metadata/metadata.model';
import {ReportColumn, ReportTable} from '../report.model';

/** A cell of a report table: a field of one of the records of its row. */
export interface ReportCell {
    field: Field;
    record: Record;
}

export interface ReportCells {
    rows: ReportCell[][];
    // Totals row (null cells: no total for the column); empty without totals.
    totals: (ReportCell | null)[];
}

/**
 * The cells of a report table (ReportDataTrait): each row has a record per table alias of
 * the report (report module, related modules), each column is a field of one of them, shown
 * with the SuiteCRM 8 field components as on the list views (user formats, list labels, links
 * to the records). Shared by the report page and the report dashlet.
 */
@Injectable({providedIn: 'root'})
export class ReportRowsService {

    constructor(protected fieldManager: FieldManager, protected language: LanguageStore) {
    }

    build(table: ReportTable | null): ReportCells {
        if (!table) {
            return {rows: [], totals: []};
        }

        const rows = (table.rows ?? []).map(row => {
            const records: { [alias: string]: Record } = {};

            return table.columns.map(column => {
                if (!records[column.record]) {
                    const data = row.records?.[column.record];
                    records[column.record] = {
                        id: data?.id ?? '',
                        module: data?.module || column.module,
                        attributes: {...(data?.attributes ?? {})},
                        fields: {}
                    } as Record;
                }

                // A linked column links to its record, when the row has one.
                const record = records[column.record];

                return this.cell(record, column, column.fieldDefinition?.type ?? 'varchar', column.link && !!record.id);
            });
        });

        const totalKeys = Object.keys(table.totals ?? {}).filter(key => key !== 'currency_id');
        let totals: (ReportCell | null)[] = [];
        if (totalKeys.length) {
            const record = {
                id: '',
                module: table.columns[0]?.module ?? '',
                attributes: {...table.totals},
                fields: {}
            } as Record;
            totals = table.columns.map(column => totalKeys.includes(column.key)
                ? this.cell(record, column, column.totalType || 'varchar', false)
                : null);
        }

        return {rows, totals};
    }

    protected cell(record: Record, column: ReportColumn, type: string, link: boolean): ReportCell {
        const viewField = {
            name: column.key,
            label: column.label,
            type,
            link,
            fieldDefinition: {...column.fieldDefinition, name: column.key, type}
        } as ViewFieldDefinition;

        return {field: this.fieldManager.addField(record, viewField, [], this.language), record};
    }
}
