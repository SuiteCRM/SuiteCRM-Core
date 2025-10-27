/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

import {Injectable} from "@angular/core";
import {AppStateStore} from "../../../../store/app-state/app-state.store";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";
import {LocalStorageService} from "../../../../services/local-storage/local-storage.service";
import {BehaviorSubject, Subscription} from "rxjs";
import {ActivatedRoute} from "@angular/router";
import {toNumber} from "lodash-es";
import {RecordPaginationModel} from "./record-pagination.model";
import {Pagination, PaginationType} from "../../../../common/views/list/list-navigation.model";
import {ObjectMap} from "../../../../common/types/object-map";
import {Record} from "../../../../common/record/record.model";
import {emptyObject} from "../../../../common/utils/object-utils";

@Injectable({
    providedIn: 'root'
})
export class RecordPaginationService {

    private nextRecordSubject = new BehaviorSubject<boolean>(false);
    nextRecord$ = this.nextRecordSubject.asObservable();

    paginationType: string = PaginationType.PAGINATION;
    protected subs: Subscription[] = [];

    constructor(
        protected localStorageService: LocalStorageService,
        protected preferences: UserPreferenceStore,
        protected appStateStore: AppStateStore,
        protected route: ActivatedRoute,
    ) {
    }

    public triggerNextRecord(value: boolean): void {
        this.nextRecordSubject.next(value);
    }

    public updateRecordListLocalStorage(records: Record[], pagination: Pagination): void {
        const module = this.getModule();
        const recordPaginationObj: RecordPaginationModel = {
            pagination: pagination,
            recordIds: this.mapRecordIds(records)
        };
        this.updatePaginationLocalStorage(pagination);
        this.savePreference(module, 'current-record-pagination', recordPaginationObj);
    }

    public updatePaginationLocalStorage(pagination: Pagination): void {
        const module = this.getModule();
        const key = module + '-' + 'listview-current-pagination';
        this.localStorageService.set(key, pagination);
    }

    protected savePreference(module: string, storageKey: string, value: any): void {
        this.preferences.setUi(module, this.getPreferenceKey(storageKey), value);
    }

    protected getPreferenceKey(storageKey: string): string {
        return 'recordview-' + storageKey;
    }

    public mapRecordIds(records: Record[]): ObjectMap[] {
        return records.map(record => ({id: record.id}));
    }

    public getModule(): string {
        return this.appStateStore.getModule();
    }

    public getTotalRecords(): number {
        const key = this.getModule() + '-' + 'listview-current-pagination';
        const currentPagination = this.localStorageService.get(key) as Pagination;
        return currentPagination?.total;
    }

    public checkRecordValid(recordId: string): boolean {
        const paginationObj: RecordPaginationModel = this.getRecordPaginationObj(this.getModule());
        const pagination: Pagination = paginationObj?.pagination;
        const recordIds: ObjectMap[] = paginationObj?.recordIds;

        if (!pagination) {
            return false;
        }

        const pageSize = this.getPageSize();
        const offset = this.getOffsetFromUrl();

        if (this.paginationType === PaginationType.LOAD_MORE && (offset > pageSize)) {
            return false;
        }

        let index = (offset - 1) % pageSize;
        if (this.paginationType === PaginationType.LOAD_MORE) {
            index = offset - 1;
        }
        if (index >= 0 && index < recordIds.length) {

            return recordIds[index]?.id === recordId;
        }

        return false;
    }

    public getPageSize(): number {
        const paginationObj: RecordPaginationModel = this.getRecordPaginationObj(this.getModule());
        return paginationObj?.pagination?.pageSize;
    }

    public getOffsetFromUrl(): number {
        const queryParams = this.route.snapshot.queryParamMap;
        return toNumber(queryParams.get('offset'));
    }

    public getRecordPaginationObj(module: string): RecordPaginationModel {
        const key = module + '-' + 'recordview-current-record-pagination';
        const data = this.localStorageService.get(key)[module] ?? null;
        if (!data || emptyObject(data)) {
            return;
        }
        return data;
    }
}
