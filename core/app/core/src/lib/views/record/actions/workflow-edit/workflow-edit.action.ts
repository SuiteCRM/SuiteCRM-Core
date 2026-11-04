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
import {Router} from '@angular/router';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {ViewMode} from '../../../../common/views/view.model';

/**
 * Edit and Duplicate of the workflow record view (modules/AOW_WorkFlow/metadata/
 * detailviewdefs.php): the workflow editor (routes workflow/edit, see extension.module.ts), which
 * edits the conditions and actions with the details, as the legacy edit view.
 */
abstract class WorkflowAction extends RecordActionHandler {
    modes = ['detail'] as ViewMode[];

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }

    protected recordId(data: RecordActionData): string {
        return data?.store?.recordStore?.getBaseRecord()?.id ?? '';
    }
}

@Injectable({providedIn: 'root'})
export class WorkflowEditAction extends WorkflowAction {
    key = 'workflow-edit';

    constructor(protected router: Router) {
        super();
    }

    run(data: RecordActionData): void {
        const id = this.recordId(data);
        if (id) {
            this.router.navigate(['/workflow/edit', id]).then();
        }
    }
}

@Injectable({providedIn: 'root'})
export class WorkflowDuplicateAction extends WorkflowAction {
    key = 'workflow-duplicate';

    constructor(protected router: Router) {
        super();
    }

    run(data: RecordActionData): void {
        const id = this.recordId(data);
        if (id) {
            this.router.navigate(['/workflow/edit'], {queryParams: {record: id, isDuplicate: 'true'}}).then();
        }
    }
}
