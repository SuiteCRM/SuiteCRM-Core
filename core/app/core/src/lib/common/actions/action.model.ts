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

import {Observable} from 'rxjs';
import {ViewMode} from '../views/view.model';
import {Record} from '../record/record.model';
import {SearchCriteria} from '../views/list/search-criteria.model';
import {StringMap} from '../types/string-map';
import {LogicDefinitions, AfterActionLogicDefinitions} from '../metadata/metadata.model';
import {ObjectMap} from "../types/object-map";
import {AsyncActionInput} from "../../services/process/processes/async-action/async-action";
import {BaseRecordContainerStoreInterface} from "../containers/record/record-container.store.model";
import {WritableSignal} from "@angular/core";

export interface ActionData {
    [key: string]: any;

    action?: Action;
    asyncData?: AsyncActionInput;
}

export interface RecordBasedActionData extends ActionData {
    store?: BaseRecordContainerStoreInterface;
}

export interface ActionHandlerMap<D extends ActionData> {
    [key: string]: ActionHandler<D>;
}

export abstract class ActionHandler<D extends ActionData> {
    abstract key: string;

    abstract modes: ViewMode[];

    getStatus(data: D): string {
        return '';
    }

    protected checkAccess(action: Action, acls: string[], defaultAcls?: string[]): boolean {
        let requiredAcls = defaultAcls || [];

        if (action && action.acl) {
            requiredAcls = action.acl;
        }

        if (!requiredAcls || !requiredAcls.length) {
            return true;
        }

        const aclsMap = {} as StringMap;
        acls.forEach(value => aclsMap[value] = value);

        return requiredAcls.every(value => aclsMap[value]);
    }

    abstract run(data: D, action?: Action): void;

    abstract shouldDisplay(data: D): boolean;
}

export interface ModeActions {
    [key: string]: Action[];
}

export interface Action {
    [key: string]: any;

    key: string;
    labelKey?: string;
    titleKey?: string;
    descriptionKey?: string;
    label?: string;
    icon?: string;
    klass?: string[];
    status?: string;
    modes?: string[];
    asyncProcess?: boolean;
    params?: { [key: string]: any };
    extraParams?: { [key: string]: any };
    acl?: string[];
    afterActionLogic?: AfterActionLogicDefinitions;
    displayLogic?: LogicDefinitions;
    isRunning?: WritableSignal<boolean>;
}

export interface ActionDataSource {
    collapseButtons?: boolean;

    getActions(context?: ActionContext): Observable<Action[]>;

    runAction(action: Action, context?: ActionContext): void;

    isActive(action: Action): boolean;
}

export type ActionDataSourceBuilderFunction = (options?: ObjectMap) => ActionDataSource;

export interface ActionManager<D extends ActionData> {

    run(action: Action, mode: ViewMode, data: D): void;

    getHandler(action: Action, mode: ViewMode): ActionHandler<D>;

    addHandler(action: Action, mode: ViewMode, handler: ActionHandler<D>): void;
}

export interface ActionContext {
    [key: string]: any;

    module?: string;
    record?: Record;
    ids?: string[];
    criteria?: SearchCriteria;
}

export interface ActiveLineAction {
    activeAction$: Observable<string>;
    getActiveAction(): string;
    setActiveAction(key: string): void;
    resetActiveAction(): void;
}
