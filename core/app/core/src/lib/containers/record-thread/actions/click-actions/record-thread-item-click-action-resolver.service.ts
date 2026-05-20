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
import {Action} from '../../../../common/actions/action.model';
import {Record} from '../../../../common/record/record.model';
import {ViewMode} from '../../../../common/views/view.model';
import {ActiveFieldsChecker} from '../../../../services/condition-operators/active-fields-checker.service';
import {RecordThreadItemClickActionConfig} from './record-thread-item-click-action.model';
import {RecordThreadItemClickActionManager} from './record-thread-item-click-action-manager.service';
import {RecordThreadItemClickActionHandler} from './record-thread-item-click.action';

export interface ResolvedClickAction {
    handler: RecordThreadItemClickActionHandler;
    action: Action;
}

@Injectable({
    providedIn: 'root'
})
export class RecordThreadItemClickActionResolverService {

    constructor(
        protected activeFieldsChecker: ActiveFieldsChecker,
        protected clickActionManager: RecordThreadItemClickActionManager
    ) {
    }

    resolve(
        clickActions: RecordThreadItemClickActionConfig[],
        record: Record,
        mode: ViewMode
    ): ResolvedClickAction | null {
        if (!clickActions || !clickActions.length) {
            return null;
        }

        let defaultEntry: RecordThreadItemClickActionConfig | null = null;

        for (const config of clickActions) {
            if (!this.isModeMatch(config, mode)) {
                continue;
            }

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

    protected resolveHandler(config: RecordThreadItemClickActionConfig, mode: ViewMode): ResolvedClickAction | null {
        const action: Action = {
            key: config.key,
            asyncProcess: config.asyncProcess,
            params: config.params,
            acl: config.acl,
            modes: config.modes,
        };

        const handler = this.clickActionManager.getHandler(action, mode) as RecordThreadItemClickActionHandler;
        if (!handler) {
            return null;
        }

        return {handler, action};
    }

    protected isModeMatch(config: RecordThreadItemClickActionConfig, mode: ViewMode): boolean {
        if (!config.modes || !config.modes.length) {
            return true;
        }

        return config.modes.includes(mode);
    }
}
