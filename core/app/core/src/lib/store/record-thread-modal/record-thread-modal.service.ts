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

import {Injectable} from "@angular/core";
import {NgbModal} from "@ng-bootstrap/ng-bootstrap";
import {NgbModalOptions} from "@ng-bootstrap/ng-bootstrap/modal/modal-config";
import {AppStateStore} from "../app-state/app-state.store";
import {RecordThreadModalModel} from "../../services/modals/record-thread-modal.model";
import {
    RecordThreadModalComponent
} from "../../containers/record-thread-modal/components/record-thread-modal/record-thread-modal.component";
import {
    RecordThreadConfig,
    ThreadItemMetadataConfig
} from "../../containers/record-thread/components/record-thread/record-thread.model";
import {
    RecordThreadItemMetadata
} from "../../containers/record-thread/store/record-thread/record-thread-item.store.model";
import {
    RecordThreadItemConfig
} from "../../containers/record-thread/components/record-thread-item/record-thread-item.model";
import {deepClone} from "../../common/utils/object-utils";
import {RecordThreadStore} from "../../containers/record-thread/store/record-thread/record-thread.store";
import {RecordThreadStoreFactory} from "../../containers/record-thread/store/record-thread/record-thread.store.factory";
import {SystemConfigStore} from "../system-config/system-config.store";
import {NgbModalRef} from "@ng-bootstrap/ng-bootstrap/modal/modal-ref";
import {SearchCriteria} from "../../common/views/list/search-criteria.model";
import {take} from "rxjs/operators";
import {Observable} from "rxjs";
import {Record} from "../../common/record/record.model";

@Injectable({
    providedIn: 'root'
})
export class RecordThreadModalService {
    initialized = false;

    constructor(
        protected appState: AppStateStore,
        protected modalService: NgbModal,
        protected storeFactory: RecordThreadStoreFactory,
        protected systemConfig: SystemConfigStore,
    ) {
    }

    init(): void {
        this.initialized = true;
        this.appState.recordThreadModalOpenEventEmitter.subscribe((options) => {
            const modalConfig = options.modalConfig ?? {};
            this.showModal(modalConfig);
        });
    }

    showModal(recordModalOptions: RecordThreadModalModel, options: any = {}): NgbModalRef {

        const backdrop = recordModalOptions.backdrop ?? true;

        const modalOptions = {...recordModalOptions.modalOptions, backdrop} as NgbModalOptions;

        const detached = recordModalOptions.detached ?? false;

        if (detached) {
            modalOptions.backdrop = false;
            modalOptions.windowClass = 'detached-modal';
            modalOptions.animation = true;
            modalOptions.container = recordModalOptions?.container ?? '#detached-modals';
        }

        const modal = this.modalService.open(RecordThreadModalComponent, modalOptions);

        const moduleName = recordModalOptions?.module;
        let minimizable = recordModalOptions?.minimizable ?? false;
        if (detached && !recordModalOptions?.keepMinimizableFalse) {
            minimizable = true;
        }

        const modalConfig = recordModalOptions.recordModalOptions ?? {};

        modal.componentInstance.module = moduleName;
        modal.componentInstance.minimizable = minimizable;
        modal.componentInstance.titleKey = modalConfig?.headerLabelKey ?? modalConfig?.labelKey ?? '';
        modal.componentInstance.dynamicTitleKey = modalConfig?.dynamicTitleKey ?? '';
        modal.componentInstance.dynamicTitleContext = modalConfig?.dynamicTitleContext ?? {};
        modal.componentInstance.descriptionKey = modalConfig?.descriptionLabelKey ?? '';
        modal.componentInstance.dynamicDescriptionKey = modalConfig?.dynamicDescriptionKey ?? '';
        modal.componentInstance.titleClass = modalConfig?.titleClass ?? '';
        modal.componentInstance.titleIcon = modalConfig?.titleIcon ?? '';
        modal.componentInstance.headerKlass = modalConfig?.headerKlass ?? '';
        modal.componentInstance.dynamicDescriptionContext = modalConfig?.dynamicDescriptionContext ?? '';
        modal.componentInstance.headerClass = modalConfig.headerClass ?? '';
        modal.componentInstance.closeOnOutsideClick = modalConfig.closeOnOutsideClick ?? false;
        modal.componentInstance.bodyClass = modalConfig.bodyClass ?? '';
        modal.componentInstance.closable = modalConfig?.closable ?? true;
        modal.componentInstance.openMinimised = modalConfig?.openMinimised ?? false;
        modal.componentInstance.footerClass = modalConfig.footerClass ?? '';
        modal.componentInstance.modalHeaderActionPosition = modalConfig.modalHeaderActionPosition ?? 'right';
        modal.componentInstance.modalHeaderActionKlass = modalConfig.modalHeaderActionKlass ?? '';
        modal.componentInstance.headerActionsKlass = modalConfig.headerActionsKlass ?? '';
        modal.componentInstance.wrapperClass = modalConfig.wrapperClass ?? '';
        modal.componentInstance.closeConfirmationMessages = modalConfig.closeConfirmationMessage ?? [];
        modal.componentInstance.closeConfirmationLabel = modalConfig.closeConfirmationLabel ?? '';
        modal.componentInstance.closeConfirmationModal = modalConfig.closeConfirmationModal ?? false;
        modal.componentInstance.showFullHeaderConfirmation = modalConfig.showFullHeaderConfirmation ?? false;
        modal.componentInstance.headerConfirmationClass = modalConfig.headerConfirmationClass ?? '';

        if (recordModalOptions.modalStore) {
            modal.componentInstance.modalStore = recordModalOptions.modalStore;
        }

        modal.componentInstance.init();

        if ((options?.addToAppState ?? true)) {

            // Store modal reference to handle cleanup
            this.appState.addModalRef(modal);

            // Handle modal close/dismiss
            modal.result.then(
                () => this.appState.removeModalRef(modal),
                () => this.appState.removeModalRef(modal)
            );

            return modal;
        }

        window.document.body.classList.add('detached-modal-open');

        modal.result.then(
            () => window.document.body.classList.add('detached-modal-open'),
            () => window.document.body.classList.add('detached-modal-open')
        );

        return modal;
    }

    setupListActions(config: RecordThreadConfig, options): void {
        config.listActions = options?.listActions ?? [];

        if ((options?.collapseListActions ?? null) !== null) {
            config.collapseListActions = options.collapseListActions;
        }
    }

    setupItemConfig(config: RecordThreadConfig, options): void {
        config.itemConfig = {
            collapsible: options?.item?.collapsible ?? false,
            collapseLimit: options?.item?.collapseLimit ?? null,
            klass: options?.item?.itemClass ?? '',
            buttonClass: options?.item?.buttonClass ?? '',
            buttonGroupClass: options?.item?.buttonGroupClass ?? '',
            dynamicClass: options?.item?.dynamicClass ?? [],
            containerClass: options?.item?.containerClass ?? '',
            flexDirection: options?.item?.flexDirection ?? '',
            metadata: {} as RecordThreadItemMetadata,
        } as RecordThreadItemConfig;

        this.setupItemMetadata(config.itemConfig.metadata, options.item.layout, options);
    }

    setupItemMetadata(metadata: RecordThreadItemMetadata, layout: ThreadItemMetadataConfig, options): void {
        if (layout && layout.header) {
            metadata.headerLayout = deepClone(layout.header);
        }

        if (layout && layout.body) {
            metadata.bodyLayout = deepClone(layout.body);
        }

        if (layout && layout.actions) {
            metadata.actions = deepClone(layout.actions);
        }

        if (options?.item && options?.item?.fields) {
            metadata.fields = deepClone(options.item.fields);
        }

        if ((options?.item?.collapseActions ?? null) !== null) {
            metadata.collapseActions = options?.item?.collapseActions;
        }
    }

    buildRecordThreadStore(): RecordThreadStore {
        return this.storeFactory.create();
    }

    initRecordThreadStore(options: any, store: RecordThreadStore): Observable<Record[]> {

        const config = {} as RecordThreadConfig;
        this.setupListActions(config, options);
        this.setupItemConfig(config, options);

        store.setItemMetadata(config.itemConfig.metadata);
        store.setListMetadata({actions: config.listActions});

        const filter = {};
        const preset = options?.filters?.preset ?? {};
        const fields = options?.filters?.static ?? [];

        Object.keys(fields).forEach((field) => {
            filter[field] = {
                field: field,
                operator: '=',
                values: [fields[field]],
                rangeSearch: false
            };
        })

        const filters = {
            orderBy: options?.filters?.orderBy ?? 'date_entered',
            sortOrder: options?.filters?.sortOrder ?? 'asc',
            preset: preset,
            filters: filter,
        } as SearchCriteria;

        store.init(options.module, false, options?.pageSize ?? null, options?.autoRefresh ?? true);
        return store.setFilters(filters).pipe(take(1))
    }

    getOptions(optionsKey: string): any {
        const ui = this.systemConfig.getConfigValue('ui');
        return ui[optionsKey] ?? null;
    }
}
