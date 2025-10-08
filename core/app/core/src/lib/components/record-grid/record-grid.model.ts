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

import {ActionDataSource} from '../../common/actions/action.model';
import {Record} from '../../common/record/record.model';
import {ScreenSizeMap} from '../../common/services/ui/resize.model';
import {ViewMode} from '../../common/views/view.model';
import {Observable} from 'rxjs';
import {LabelDisplay} from '../field-grid/field-grid.model';

export interface RecordGridViewModel {
    record: Record;
    mode: string;
    fields: string[];
    maxColumns: number;
}

export interface RecordGridConfig {
    record$: Observable<Record>;
    mode$: Observable<ViewMode>;
    fields$: Observable<string[]>;
    maxColumns$: Observable<number>;
    sizeMap$: Observable<ScreenSizeMap>;
    actions?: ActionDataSource;
    appendActions?: boolean;
    klass?: string;
    buttonClass?: string;
    labelClass?: { [klass: string]: any };
    inputClass?: { [klass: string]: any };
    rowClass?: { [klass: string]: any };
    colClass?: { [klass: string]: any };
    labelDisplay?: LabelDisplay;
    colAlignItems?: string;
}
