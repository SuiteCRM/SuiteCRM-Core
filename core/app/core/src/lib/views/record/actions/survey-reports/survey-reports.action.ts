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
 * View Survey Reports of the survey record view (modules/Surveys/metadata/
 * detailviewdefs.php): the report page of the survey (route surveys/reports/:record, see
 * app-initializer.ts), as the button of the legacy detail view.
 */
@Injectable({providedIn: 'root'})
export class SurveyReportsAction extends RecordActionHandler {
    key = 'survey-reports';
    modes = ['detail'] as ViewMode[];

    constructor(protected router: Router) {
        super();
    }

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }

    run(data: RecordActionData): void {
        const id = data?.store?.recordStore?.getBaseRecord()?.id ?? '';
        if (id) {
            this.router.navigate(['/surveys/reports', id]).then();
        }
    }
}
