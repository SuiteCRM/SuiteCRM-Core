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
import {Observable, of} from 'rxjs';
import {catchError, map, shareReplay, take} from 'rxjs/operators';
import {Action} from '../../../common/actions/action.model';
import {ViewMode} from '../../../common/views/view.model';
import {FieldLogicActionData, FieldLogicActionHandler} from '../field-logic.action';
import {LanguageStore} from '../../../store/language/language.store';
import {Process, ProcessService} from '../../../services/process/process.service';

export interface PdfVariableGroup {
    label: string;
    variables: { name: string; label: string }[];
}

/**
 * Field logic `pdf-template-variables` of the HTML editors of a PDF template
 * (body, header, footer: PdfTemplateFieldDefinitionMapper): the "Insert variable" menu of the
 * editor lists the variables of the template type, as the legacy editor, and follows the type.
 *
 * The variables come from the `pdf-template-editor` process, by group (the module, each related
 * record, the line items), with labels translated by the backend. The variables menu of the
 * editor translates a variable label with the strings of the module key of its group: each group
 * gets its own strings (pseudo module pdf-variables-<n>), the label of each variable by name.
 */
@Injectable({providedIn: 'root'})
export class PdfTemplateVariablesAction extends FieldLogicActionHandler {

    static readonly PROCESS = 'pdf-template-editor';
    static readonly STRINGS = 'pdf-variables-';

    key = 'pdf-template-variables';
    modes = ['edit', 'create'] as ViewMode[];

    protected groups$: { [type: string]: Observable<PdfVariableGroup[]> } = {};

    constructor(protected processService: ProcessService, protected language: LanguageStore) {
        super();
    }

    run(data: FieldLogicActionData, action: Action): void {
        const field = data.field;
        const record = data.record;
        if (!field || !record) {
            return;
        }

        // Loaded once by type: the field keeps them from a view to the next.
        const type = `${record.fields?.type?.value ?? record.attributes?.type ?? ''}`;
        field.metadata = field.metadata ?? {};
        field.metadata.squire = field.metadata.squire ?? {};
        if (field.metadata.squire.variablesType === type) {
            return;
        }

        field.metadata.squire.variablesType = type;
        field.loading.set(true);
        this.getGroups(type).pipe(take(1)).subscribe(groups => {
            field.metadata.squire.variables = this.toMenu(groups);
            field.loading.set(false);
            // The editor rebuilds its toolbar before showing it again: measured without width,
            // its buttons fold into the "more" menu. It measures it again on a window resize.
            setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
        });
    }

    getTriggeringStatus(): string[] {
        return ['onDependencyChange', 'onFieldInitialize'];
    }


    /**
     * The variables of a type, loaded once (the three editors ask for the same type).
     */
    protected getGroups(type: string): Observable<PdfVariableGroup[]> {
        if (!type) {
            return of([]);
        }

        if (!this.groups$[type]) {
            this.groups$[type] = this.processService.submit(PdfTemplateVariablesAction.PROCESS, {action: 'variables', type}).pipe(
                map((process: Process) => {
                    if (process?.status !== 'success') {
                        throw new Error('PDF template variables not loaded');
                    }

                    return (process?.data?.groups ?? []) as PdfVariableGroup[];
                }),
                catchError(() => {
                    delete this.groups$[type];
                    return of([]);
                }),
                shareReplay(1)
            );
        }

        return this.groups$[type];
    }

    /**
     * Variables metadata of the HTML editor: menu group by key, the variables of each group
     * by group label (unique), and the strings of the variable labels.
     */
    protected toMenu(groups: PdfVariableGroup[]): { modules: { [key: string]: string }, fieldDefs: { [label: string]: { name: string, value: string }[] } } {
        const modules: { [key: string]: string } = {};
        const fieldDefs: { [label: string]: { name: string, value: string }[] } = {};
        const modStrings = this.language.getLanguageStrings()?.modStrings ?? null;

        groups.forEach((group, index) => {
            const key = PdfTemplateVariablesAction.STRINGS + index;
            let label = group.label;
            for (let copy = 2; fieldDefs[label]; copy++) {
                label = `${group.label} (${copy})`;
            }

            const strings: { [name: string]: string } = {};
            group.variables.forEach(variable => strings[variable.name] = variable.label);
            if (modStrings) {
                modStrings[key] = strings;
            }

            modules[key] = label;
            fieldDefs[label] = group.variables.map(variable => ({name: variable.name, value: variable.name}));
        });

        return {modules, fieldDefs};
    }
}
