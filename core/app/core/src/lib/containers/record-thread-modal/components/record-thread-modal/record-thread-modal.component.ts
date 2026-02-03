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
import {Component, Input, OnInit, signal, WritableSignal} from "@angular/core";
import {RecordThreadConfig} from "../../../record-thread/components/record-thread/record-thread.model";
import {of, Subscription} from "rxjs";
import {ModalModule} from "../../../../components/modal/components/modal/modal.module";
import {RecordThreadModule} from "../../../record-thread/components/record-thread/record-thread.module";
import {RecordThreadModalService} from "../../../../store/record-thread-modal/record-thread-modal.service";
import {RecordThreadModalStore} from "../../../../store/record-thread-modal/record-thread-modal.store";
import {StringMap} from "../../../../common/types/string-map";
import {FieldMap} from "../../../../common/record/field.model";
import {
    RecordThreadModalHeaderActionsAdapterFactory
} from "../../adapters/record-thread-modal-header-actions.adapter.factory";
import {RecordThreadModalStoreFactory} from "../../../../store/record-thread-modal/record-thread-modal.store.factory";
import {NgbActiveModal} from "@ng-bootstrap/ng-bootstrap";
import {SearchCriteria} from "../../../../common/views/list/search-criteria.model";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {ActionGroupMenuModule} from "../../../../components/action-group-menu/action-group-menu.module";
import {NgIf} from "@angular/common";

@Component({
    selector: 'scrm-record-thread-modal',
    templateUrl: './record-thread-modal.component.html',
    standalone: true,
    imports: [
        ModalModule,
        RecordThreadModule,
        ActionGroupMenuModule,
        NgIf,
    ],
    styleUrls: [],
})
export class RecordThreadModalComponent implements OnInit {

    @Input() closable: boolean = true;
    @Input() titleKey = '';
    @Input() titleClass = '';
    @Input() titleIcon = '';
    @Input() dynamicTitleKey = '';
    @Input() descriptionKey = '';
    @Input() dynamicDescriptionKey = '';
    @Input() closeOnOutsideClick = false;
    @Input() module: string;
    @Input() modalStore: RecordThreadModalStore;
    @Input() openMinimised = false;
    @Input() modalHeaderActionsAdapter: any = null;
    @Input() modalHeaderActionKlass: string = '';
    @Input() minimizable: boolean = false;
    @Input() headerClass: string = '';
    @Input() bodyClass: string = '';
    @Input() footerClass: string = '';
    @Input() wrapperClass: string = '';
    @Input() closeConfirmationLabel: string = '';
    @Input() closeConfirmationMessages: string[] = [];
    @Input() closeConfirmationModal: boolean = false;
    @Input() dynamicTitleContext: WritableSignal<StringMap> = signal({});
    @Input() dynamicTitleFields: WritableSignal<FieldMap> = signal({});

    recordThreadConfig: RecordThreadConfig;
    recordThreadModalOptions: any = {};
    closeButton: ButtonInterface;

    protected subs: Subscription[] = [];

    constructor(
        protected activeModal: NgbActiveModal,
        protected recordThreadModalService: RecordThreadModalService,
        protected storeFactory: RecordThreadModalStoreFactory,
        protected recordThreadModalHeaderActionsAdapterFactory: RecordThreadModalHeaderActionsAdapterFactory
    ) {
    }


    ngOnInit(): void {
        this.recordThreadConfig = this.buildConfig();
        this.closeButton = this.initCloseButton();
    }

    init(): void {
        this.modalHeaderActionsAdapter = null;

        if (!this.modalStore) {
            this.modalStore = this.storeFactory.create();
        }

        this.modalHeaderActionsAdapter = this.recordThreadModalHeaderActionsAdapterFactory.create(this.modalStore, this.activeModal);
    }

    buildConfig(): RecordThreadConfig {

        this.recordThreadModalOptions = this.recordThreadModalService.getOptions(this?.modalStore?.optionsKey)?.recordThreadConfig?.recordThreadOptions || {};

        const filter= {};
        const fields = this?.recordThreadModalOptions?.filters?.static ?? [];

        Object.keys(fields).forEach((field) => {
            filter[field] = {
                field: field,
                operator: '=',
                values: [fields[field]],
                rangeSearch: false
            };
        })

        const config = {
            filters$: of({
                orderBy: this?.recordThreadModalOptions?.filters?.orderBy ?? 'date_entered',
                sortOrder: this?.recordThreadModalOptions?.filters?.sortOrder ?? 'asc',
                filters: filter,
            } as SearchCriteria),
            module: this.recordThreadModalOptions?.module ?? '',
            klass: this.recordThreadModalOptions?.class ?? '',
            maxListHeight: this.recordThreadModalOptions.maxListHeight ?? 350,
            direction: this.recordThreadModalOptions.direction || 'asc',
            autoRefreshFrequency: this.recordThreadModalOptions.autoRefreshFrequency || 0,
            autoRefreshDeviationMin: this.recordThreadModalOptions.autoRefreshDeviationMin ?? 0,
            autoRefreshDeviationMax: this.recordThreadModalOptions.autoRefreshDeviationMax ?? 0,
            onRefresh: () => {
                this.modalStore.getRecordThreadStore().reload();
            },
            onLoadMore: () => {
                this.modalStore.getRecordThreadStore().loadMore();
            },
            loadMorePosition: this.recordThreadModalOptions?.loadMorePosition ?? '',
            create: false,
            listActionsClass: this.recordThreadModalOptions?.listActionsClass ?? '',
            listActionsButtonClass: this.recordThreadModalOptions?.listActionsButtonClass ?? '',
            listActionsButtonGroupClass: this.recordThreadModalOptions?.listActionsButtonGroupClass ?? '',
            pageSize: this.recordThreadModalOptions?.pageSize ?? '',
            showNoDataMessage: this.recordThreadModalOptions?.showNoDataMessage,
            noDataLabel: this.recordThreadModalOptions?.noDataLabel,
        } as RecordThreadConfig;

        this.recordThreadModalService.setupListActions(config, this.recordThreadModalOptions);
        this.recordThreadModalService.setupItemConfig(config, this.recordThreadModalOptions);

        config.store = this.modalStore.getRecordThreadStore();
        return config;
    }


    protected initCloseButton(): ButtonInterface {
        return {
            klass: 'btn',
            onClick: () => {
                this.activeModal.close();
            },
        } as ButtonInterface;
    }
}
