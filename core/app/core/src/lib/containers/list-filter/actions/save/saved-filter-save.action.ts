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

import {Injectable} from '@angular/core';
import {ViewMode} from '../../../../common/views/view.model';
import {take} from 'rxjs/operators';
import {MessageService} from '../../../../services/message/message.service';
import {SavedFilterActionData, SavedFilterActionHandler} from '../saved-filter.action';

@Injectable({
    providedIn: 'root'
})
export class SavedFilterSaveAction extends SavedFilterActionHandler {

    key = 'save';
    modes = ['edit' as ViewMode];

    constructor(protected message: MessageService) {
        super();
    }

    run(data: SavedFilterActionData): void {
        data.store.validate().pipe(take(1)).subscribe(valid => {
            if (valid) {
                data.store.save().pipe(take(1)).subscribe(savedFilter => {
                    data.listFilterStore.config.addSavedFilter(data.store.recordStore.extractBaseRecord(savedFilter));
                    data.listFilterStore.applyFilter();
                    this.message.addSuccessMessageByKey('LBL_SAVED_FILTER_SAVED')
                });
                return;
            }

            this.message.addWarningMessageByKey('LBL_VALIDATION_ERRORS');
        });
    }

    shouldDisplay(): boolean {
        return true;
    }
}
