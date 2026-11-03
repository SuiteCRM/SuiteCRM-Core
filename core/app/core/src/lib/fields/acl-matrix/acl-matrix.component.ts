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

import {ChangeDetectorRef, Component, OnDestroy, OnInit} from '@angular/core';
import {Observable, Subscription, throwError} from 'rxjs';
import {catchError, map, shareReplay, take} from 'rxjs/operators';
import {BaseFieldComponent} from '../base/base-field.component';
import {DataTypeFormatter} from '../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../store/language/language.store';
import {Process, ProcessService} from '../../services/process/process.service';

export interface AclMatrixOption {
    value: number;
    label: string;
}

export interface AclMatrixAction {
    name: string;
    label: string;
    options: AclMatrixOption[];
}

export interface AclMatrixModule {
    name: string;
    label: string;
    actions: string[];
}

export interface AclMatrixStructure {
    modules: AclMatrixModule[];
    actions: AclMatrixAction[];
    levels: AclMatrixOption[];
    disabled: number;
}

export type AclMatrix = { [module: string]: { [action: string]: number } };

/**
 * Style of each access level, as the legacy matrix (aclAll, aclOwner... of the suite8 theme).
 */
const LEVEL_CLASSES: { [level: string]: string } = {
    '90': 'all', '89': 'enabled', '80': 'group', '75': 'owner', '0': 'default', '-98': 'disabled', '-99': 'none',
};

/**
 * The structure is the same for every role: loaded once.
 */
let structure$: Observable<AclMatrixStructure> = null;

/**
 * Access matrix of a role (legacy ACLRoles detail view): the access level of each
 * action (columns) of each module (rows), shown as the legacy matrix, and chosen in the edit and
 * create views, one by one or for all the modules shown at once (legacy header selects). The
 * value is {module: {action: level}} (AclRoleMatrixEntityMapper, saved by
 * AclRoleMatrixSaveHandler); the modules, actions and levels come from the `acl-role-matrix`
 * process. A level not set in the value is "Not Set" (new role).
 *
 * Field type `acl-matrix`, detail and edit modes (the access matrix of the roles,
 * AclRoleFieldDefinitionMapper).
 */
@Component({
    selector: 'scrm-acl-matrix-field',
    templateUrl: './acl-matrix.component.html',
    styleUrls: []
})
export class AclMatrixFieldComponent extends BaseFieldComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'acl-role-matrix';

    structure: AclMatrixStructure = null;
    matrix: AclMatrix = {};
    filter = '';
    error = false;

    protected levelLabels: { [level: string]: string } = {};
    protected shownFilter: string = null;
    protected shown: AclMatrixModule[] = [];
    protected structure$sub: Subscription = null;

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected language: LanguageStore,
        protected processService: ProcessService,
        protected cdr: ChangeDetectorRef
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    get editing(): boolean {
        return this.mode !== 'detail' && this.mode !== 'list';
    }

    ngOnInit(): void {
        super.ngOnInit();
        const value = this.field?.valueObject ?? this.record?.attributes?.[this.field?.name] ?? {};
        this.matrix = this.copy(value && typeof value === 'object' ? value : {});

        this.structure$sub = this.getStructure().pipe(take(1)).subscribe({
            next: (structure: AclMatrixStructure) => {
                this.structure = structure;
                this.levelLabels = {};
                (structure.levels ?? []).forEach(level => this.levelLabels[`${level.value}`] = level.label);
                this.shownFilter = null;
                this.cdr.markForCheck();
            },
            error: () => {
                this.error = true;
                this.cdr.markForCheck();
            }
        });
    }

    ngOnDestroy(): void {
        this.structure$sub?.unsubscribe();
        super.ngOnDestroy();
    }

    label(key: string): string {
        return this.language.getFieldLabel(key, this.record?.module || 'acl-roles') || key;
    }

    appString(key: string): string {
        return this.language.getAppString(key) || key;
    }

    /**
     * The modules shown: all, or those of the filter (name or label).
     */
    rows(): AclMatrixModule[] {
        const filter = this.filter.trim().toLowerCase();
        if (this.shownFilter !== filter) {
            this.shownFilter = filter;
            this.shown = (this.structure?.modules ?? []).filter(module => !filter
                || module.label.toLowerCase().includes(filter) || module.name.toLowerCase().includes(filter));
        }

        return this.shown;
    }

    hasAction(module: AclMatrixModule, action: string): boolean {
        return module.actions.includes(action);
    }

    level(module: string, action: string): number {
        return this.matrix[module]?.[action] ?? 0;
    }

    levelLabel(module: string, action: string): string {
        return this.levelLabels[`${this.level(module, action)}`] ?? `${this.level(module, action)}`;
    }

    levelClass(module: string, action: string): string {
        return `acl-matrix-level-${LEVEL_CLASSES[`${this.level(module, action)}`] ?? 'admin'}`;
    }

    /**
     * The other actions of a module disabled are greyed, as the legacy matrix.
     */
    isDisabled(module: AclMatrixModule, action: string): boolean {
        return action !== 'access' && this.level(module.name, 'access') === this.structure?.disabled;
    }

    set(module: string, action: string, level: number): void {
        this.matrix[module] = {...(this.matrix[module] ?? {}), [action]: Number(level)};
        this.update();
    }

    /**
     * Sets a level of an action for all the modules shown (legacy header selects).
     */
    setAll(action: AclMatrixAction, select: HTMLSelectElement): void {
        const value = select?.value ?? '';
        if (value !== '') {
            this.rows().filter(module => this.hasAction(module, action.name)).forEach(module => {
                this.matrix[module.name] = {...(this.matrix[module.name] ?? {}), [action.name]: Number(value)};
            });
            this.update();
        }
        if (select) {
            select.value = '';
        }
    }

    trackName(index: number, item: { name: string }): string {
        return item.name;
    }

    trackValue(index: number, item: AclMatrixOption): number {
        return item.value;
    }

    /**
     * The matrix of the editor, set on the field.
     */
    protected update(): void {
        const value = this.copy(this.matrix);
        this.field.valueObject = value;
        this.field.value = value as any;
        this.field.formControl?.setValue(value);
        this.field.formControl?.markAsDirty();
    }

    protected copy(matrix: AclMatrix): AclMatrix {
        const copy: AclMatrix = {};
        Object.keys(matrix ?? {}).forEach(module => copy[module] = {...(matrix[module] ?? {})});

        return copy;
    }

    protected getStructure(): Observable<AclMatrixStructure> {
        if (!structure$) {
            structure$ = this.processService.submit(AclMatrixFieldComponent.PROCESS, {}).pipe(
                map((process: Process) => {
                    const data = process?.data as AclMatrixStructure;
                    if (process?.status !== 'success' || !data?.modules) {
                        throw new Error('Access matrix not loaded');
                    }

                    return data;
                }),
                catchError(error => {
                    structure$ = null;

                    return throwError(() => error);
                }),
                shareReplay(1)
            );
        }

        return structure$;
    }
}
