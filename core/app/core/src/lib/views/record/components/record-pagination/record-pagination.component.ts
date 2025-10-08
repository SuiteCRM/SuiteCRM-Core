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

import {ChangeDetectionStrategy, Component, computed, OnDestroy, OnInit} from '@angular/core';
import {CommonModule} from "@angular/common";
import {ActivatedRoute, Router} from "@angular/router";
import {combineLatestWith, Observable, Subscription} from "rxjs";
import {filter, map, tap} from "rxjs/operators";
import {toNumber} from "lodash-es";
import {MessageModalComponent} from "../../../../components/modal/components/message-modal/message-modal.component";
import {ModuleNavigation} from "../../../../services/navigation/module-navigation/module-navigation.service";
import {ModuleNameMapper} from "../../../../services/navigation/module-name-mapper/module-name-mapper.service";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";
import {LocalStorageService} from "../../../../services/local-storage/local-storage.service";
import {LanguageStore, LanguageStringMap} from "../../../../store/language/language.store";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {RecordViewStore} from "../../store/record-view/record-view.store";
import {RecordPaginationService} from "../../store/record-pagination/record-pagination.service";
import {RecordPaginationStore} from "../../store/record-pagination/record-pagination.store";
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {ButtonModule} from "../../../../components/button/button.module";
import {PageSelection, PaginationCount, PaginationType} from "../../../../common/views/list/list-navigation.model";
import {ViewMode} from "../../../../common/views/view.model";
import {ObjectMap} from "../../../../common/types/object-map";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {ModalButtonInterface} from "../../../../common/components/modal/modal.model";

interface RecordPaginationViewModel {
    appStrings: LanguageStringMap;
    pageCount: PaginationCount;
    paginationEnabled: boolean;
}

@Component({
    selector: 'scrm-record-pagination',
    templateUrl: './record-pagination.component.html',
    styles: [],
    standalone: true,
    imports: [CommonModule, ButtonModule],
    changeDetection: ChangeDetectionStrategy.OnPush,
})

export class RecordPaginationComponent implements OnInit, OnDestroy {

    currentIndex: number = 1;
    currentPage: number = 1;
    pageSize: number = 20;
    totalRecordsCount: number = 0;
    isRecordsLoading: boolean = false;
    isSaveContinueClicked: boolean = false;
    mode: ViewMode = 'detail' as ViewMode;
    paginationType: string = PaginationType.PAGINATION;
    recordIds: ObjectMap[];
    subs: Subscription[] = [];

    prevButton: ButtonInterface = null;
    nextButton: ButtonInterface = null;

    appStrings$: Observable<LanguageStringMap> = this.languageStore.appStrings$;
    recordIds$: Observable<ObjectMap[]> = this.recordPaginationStore.recordIds$;
    mode$: Observable<ViewMode> = this.recordViewStore.mode$;
    vm$: Observable<RecordPaginationViewModel> = null;

    constructor(
        private systemConfigStore: SystemConfigStore,
        private preferences: UserPreferenceStore,
        private localStorageService: LocalStorageService,
        private languageStore: LanguageStore,
        private navigation: ModuleNavigation,
        private nameMapper: ModuleNameMapper,
        private recordViewStore: RecordViewStore,
        private recordPaginationStore: RecordPaginationStore,
        private recordPaginationService: RecordPaginationService,
        private route: ActivatedRoute,
        private router: Router,
        private modalService: NgbModal
    ) {
        this.subs.push(this.route.queryParamMap
            .subscribe((params: any) => {
                this.currentIndex = toNumber(params.get('offset'));
            })
        );
    }

    ngOnInit(): void {
        this.recordPaginationStore.init();
        this.currentPage = this.recordPaginationStore.getCurrentPage();
        this.pageSize = this.recordPaginationStore.getPageSize();
        this.totalRecordsCount = this.recordPaginationStore.getRecordsCount();
        this.paginationType = this.preferences.getUserPreference('listview_pagination_type') ?? this.systemConfigStore.getConfigValue('listview_pagination_type');
        this.recordPaginationService.paginationType = this.paginationType;
        this.subs.push(this.mode$.subscribe(mode => {
            this.mode = mode;
        }));

        this.prevButton = {
            klass: {
                'record-pagination-button': true,
                'pagination-previous': true,
                disabled: this.currentIndex === 1
            },
            icon: 'paginate_previous',
            iconKlass: 'sicon-2x',
            disabled: computed((): boolean => {
                return this.currentIndex === 1 || this.isRecordsLoading;
            }),
            onClick: () => this.prevRecord()
        } as ButtonInterface;

        this.nextButton = {
            klass: {
                'record-pagination-button': true,
                'pagination-next': true,
                disabled: this.currentIndex === this.totalRecordsCount
            },
            icon: 'paginate_next',
            iconKlass: 'sicon-2x',
            disabled: computed((): boolean => {
                return this.currentIndex === this.totalRecordsCount || this.isRecordsLoading;
            }),
            onClick: () => this.nextRecord()
        } as ButtonInterface;

        this.vm$ = this.appStrings$.pipe(
            combineLatestWith(this.recordPaginationStore.pagination$, this.recordPaginationStore.paginationEnabled$),
            map(([appStrings, pageCount, paginationEnabled]: [LanguageStringMap, PaginationCount, boolean]) => {
                const module = this.nameMapper.toFrontend(this.recordPaginationStore.getModule()) ?? '';
                const key = module + '-' + 'recordview-current-record-pagination';
                const isRecordPaginationExist = this.localStorageService.get(key);
                const isRecordValid = this.recordPaginationService.checkRecordValid(this.recordViewStore.getRecordId());

                if (!isRecordPaginationExist || !isRecordValid || (this.currentIndex > this.totalRecordsCount)) {
                    paginationEnabled = false;
                }
                this.prevButton = {
                    ...this.prevButton,
                    titleKey: appStrings['LBL_SEARCH_PREV'] || ''
                } as ButtonInterface;
                this.nextButton = {
                    ...this.nextButton,
                    titleKey: appStrings['LBL_SEARCH_NEXT'] || ''
                } as ButtonInterface;

                return {appStrings, pageCount, paginationEnabled};
            })
        );

        this.subs.push(this.recordIds$.subscribe(recordIds => {
            this.recordIds = recordIds;
        }));

        this.subs.push(this.recordPaginationService.nextRecord$.pipe(
            filter(data => {
                if (!data) {
                    return false;
                }
                return true;
            }),
            tap((data) => {
                this.isSaveContinueClicked = true;
                this.nextRecord();
            })
        ).subscribe((data) => {
            this.isSaveContinueClicked = false;
            this.recordPaginationService.triggerNextRecord(false);

        }));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.recordPaginationStore.clear();
    }

    protected prevRecord(): void {
        if (this.currentIndex <= 0) {
            return;
        }

        let nextRecordIndex = (this.currentIndex - 2) % this.pageSize;
        let nextPageThreshold = this.currentIndex - ((this.currentPage - 1) * this.pageSize) - 1;

        if (nextPageThreshold <= 0) {
            this.loadPage(PageSelection.PREVIOUS);
        } else {
            if (this.mode === 'edit' && this.recordViewStore.recordStore.isDirty()) {
                this.showConfirmationModal(PageSelection.PREVIOUS, nextRecordIndex);
            } else {
                this.navigatePrevRoute(nextRecordIndex);
            }
        }
    }

    protected nextRecord(): void {
        if (this.currentIndex >= this.totalRecordsCount) {
            return;
        }

        let nextRecordIndex = this.currentIndex % this.pageSize;
        let nextPageThreshold = this.currentIndex - ((this.currentPage - 1) * this.pageSize);

        if (nextPageThreshold > this.recordIds.length - 1) {
            this.loadPage(PageSelection.NEXT);
        } else {
            if (this.mode === 'edit' && this.recordViewStore.recordStore.isDirty() && !this.isSaveContinueClicked) {
                this.showConfirmationModal(PageSelection.NEXT, nextRecordIndex);
            } else {
                this.navigateNextRoute(nextRecordIndex);
            }
        }
    }

    protected loadPage(direction: PageSelection): void {
        this.isRecordsLoading = true;
        let nextRecordIndex = 0;
        let isPaginationLoadMore = false;
        if (this.paginationType === PaginationType.LOAD_MORE) {
            isPaginationLoadMore = true;
        }
        if (direction === PageSelection.PREVIOUS) {
            nextRecordIndex = this.pageSize - 1;
        } else if (direction === PageSelection.NEXT && isPaginationLoadMore) {
            nextRecordIndex = this.currentIndex;
        }

        if (isPaginationLoadMore && direction !== PageSelection.PREVIOUS) {
            const jump = this.preferences.getUserPreference('list_max_entries_per_page') ?? this.systemConfigStore.getConfigValue('list_max_entries_per_page');
            const pagination = this.recordPaginationStore.recordListStore.getPagination();
            const currentPageSize = pagination.pageSize || 0;
            const newPageSize = Number(currentPageSize) + Number(jump);

            this.recordPaginationStore.recordListStore.setPageSize(newPageSize);
            this.recordPaginationStore.recordListStore.updatePagination(pagination.current);
        }


        this.recordPaginationStore.recordListStore.setPage(direction as PageSelection, isPaginationLoadMore).subscribe(data => {
            this.recordPaginationService.updateRecordListLocalStorage(data.records, data.pagination);
            this.recordPaginationStore.loadDataLocalStorage();
            this.isRecordsLoading = false;
            if (this.mode === 'edit' && this.recordViewStore.recordStore.isDirty() && !this.isSaveContinueClicked) {
                this.showConfirmationModal(direction, nextRecordIndex);
            } else {
                direction === PageSelection.NEXT ? this.navigateNextRoute(nextRecordIndex) : this.navigatePrevRoute(nextRecordIndex);
            }
        });
    }

    protected navigateNextRoute(nextRecordIndex: number): void {
        const nextRoute = this.buildRoute(this.recordIds[nextRecordIndex]);
        this.router.navigate([nextRoute], {queryParams: {offset: this.currentIndex + 1}});
    }

    protected navigatePrevRoute(nextRecordIndex: number): void {
        const nextRoute = this.buildRoute(this.recordIds[nextRecordIndex]);
        this.router.navigate([nextRoute], {queryParams: {offset: this.currentIndex - 1}});
    }

    protected showConfirmationModal(direction: PageSelection, nextRecordIndex: number): void {
        const modal = this.modalService.open(MessageModalComponent);

        modal.componentInstance.textKey = 'WARN_UNSAVED_CHANGES';
        modal.componentInstance.buttons = [
            {
                labelKey: 'LBL_CANCEL',
                klass: ['btn-secondary'],
                onClick: activeModal => activeModal.dismiss()
            } as ModalButtonInterface,
            {
                labelKey: 'LBL_PROCEED',
                klass: ['btn-main'],
                onClick: activeModal => {
                    direction === PageSelection.NEXT ? this.navigateNextRoute(nextRecordIndex) : this.navigatePrevRoute(nextRecordIndex);
                    activeModal.close();
                }
            } as ModalButtonInterface
        ];
    }

    protected buildRoute(recordId: ObjectMap): string {
        const module = this.nameMapper.toFrontend(this.recordPaginationStore.getModule()) ?? '';
        const id = recordId.id ?? '';
        const isEdit = this.mode === 'edit';
        return this.navigation.getRecordRouterLink(module, id, isEdit);
    }
}
