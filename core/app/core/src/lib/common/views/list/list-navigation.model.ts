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

export enum SortDirection {
    NONE = 'NONE',
    ASC = 'ASC',
    DESC = 'DESC'
}

export interface SortingSelection {
    orderBy?: string;
    sortOrder?: SortDirection;
}

export interface Pagination {
    pageSize: number;
    current: number;
    previous: number;
    next: number;
    last: number;
    pageFirst: number;
    pageLast: number;
    total: number;
}

export interface PaginationCount {
    pageFirst: number;
    pageLast: number;
    total: number;
}

export enum PageSelection {
    FIRST = 'FIRST',
    PREVIOUS = 'PREVIOUS',
    NEXT = 'NEXT',
    LAST = 'LAST',
}

export enum PaginationType {
    PAGINATION = 'pagination',
    LOAD_MORE = 'load-more'
}
