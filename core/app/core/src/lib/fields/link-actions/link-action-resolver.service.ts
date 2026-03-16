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
import {Action} from '../../common/actions/action.model';
import {Field} from '../../common/record/field.model';
import {Record} from '../../common/record/record.model';
import {ViewMode} from '../../common/views/view.model';
import {ActiveFieldsChecker} from '../../services/condition-operators/active-fields-checker.service';
import {LinkActionConfig} from './link-action.model';
import {LinkActionHandler} from './link-action.handler';
import {LinkActionManager} from './link-action-manager.service';

export interface ResolvedLinkAction {
    handler: LinkActionHandler;
    action: Action;
}

@Injectable({
    providedIn: 'root'
})
export class LinkActionResolverService {

    constructor(
        protected activeFieldsChecker: ActiveFieldsChecker,
        protected linkActionManager: LinkActionManager
    ) {
    }

    resolve(field: Field, record: Record, mode: ViewMode): ResolvedLinkAction | null {
        const linkActions = this.getLinkActions(field);
        if (!linkActions.length) {
            return null;
        }

        let defaultEntry: LinkActionConfig | null = null;

        for (const config of linkActions) {
            if (config.default) {
                defaultEntry = config;
                continue;
            }

            if (!config.activeOnFields) {
                const resolved = this.resolveHandler(config, mode);
                if (resolved) {
                    return resolved;
                }
                continue;
            }

            const isActive = this.activeFieldsChecker.isActive(
                Object.keys(config.activeOnFields), record, config.activeOnFields, [], {}
            );
            if (isActive) {
                const resolved = this.resolveHandler(config, mode);
                if (resolved) {
                    return resolved;
                }
            }
        }

        if (defaultEntry) {
            return this.resolveHandler(defaultEntry, mode);
        }

        return null;
    }

    protected resolveHandler(config: LinkActionConfig, mode: ViewMode): ResolvedLinkAction | null {
        const action: Action = {
            key: config.key,
            asyncProcess: config.asyncProcess,
            params: config.params,
        };

        const handler = this.linkActionManager.getHandler(action, mode) as LinkActionHandler;
        if (!handler) {
            return null;
        }

        return {handler, action};
    }

    protected getLinkActions(field: Field): LinkActionConfig[] {
        if (field?.metadata?.linkActions) {
            return field?.metadata?.linkActions;
        }

        if (field?.definition?.linkActions) {
            return field?.definition?.linkActions;
        }

        return [];
    }
}
