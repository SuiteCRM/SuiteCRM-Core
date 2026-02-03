/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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
import {RecordModalComponent} from "../../containers/record-modal/components/record-modal/record-modal.component";
import {ViewMode} from "../../common/views/view.model";
import {deepClone} from "../../common/utils/object-utils";
import {RecordFieldInjector} from "../record/record-field-injector.service";
import {AppStateStore} from "../../store/app-state/app-state.store";
import {RecordModalOptions} from "./record-modal.model";

@Injectable({
    providedIn: 'root'
})
export class RecordModalService {

    initialized = false;

    constructor(
        protected appState: AppStateStore,
        protected recordFieldInjector: RecordFieldInjector,
        protected modalService: NgbModal
    ) {
    }

    init(): void {
        this.initialized = true;
        this.appState.recordModalOpenEventEmitter.subscribe((options) => {
            this.showModal(options);
        });
    }

    showModal(recordModalOptions: RecordModalOptions) {

        const backdrop = recordModalOptions.backdrop ?? true;

        const modalOptions = {...recordModalOptions.modalOptions, backdrop} as NgbModalOptions;

        const detached = recordModalOptions.detached ?? false;

        if (detached) {
            modalOptions.backdrop = false;
            modalOptions.windowClass = 'detached-modal';
            modalOptions.animation = true;
            modalOptions.container = '#detached-modals';
            modalOptions.keyboard = false;
        }

        const modal = this.modalService.open(RecordModalComponent, modalOptions);

        let mode = recordModalOptions?.mode ?? '';

        if (mode === ''){
            mode = 'create';
        }

        const moduleName = recordModalOptions?.module;

        const parentId = recordModalOptions?.parentId ?? '';
        const parentModule = recordModalOptions?.parentModule ?? '';

        let minimizable = recordModalOptions?.minimizable ?? false;
        if (detached) {
            minimizable = true;
        }

        let maximizable = recordModalOptions?.maximizable ?? false;
        if (detached) {
            maximizable = true;
        }

        if (recordModalOptions?.mapFields ?? false){
            let mappedFieldsConfig = recordModalOptions?.mapFields[parentModule] ?? null;
            if (!mappedFieldsConfig) {
                mappedFieldsConfig = recordModalOptions?.mapFields['default'] ?? null;
            }

            if (recordModalOptions?.record && mappedFieldsConfig) {
                modal.componentInstance.mappedFields = deepClone(this.recordFieldInjector.getInjectFieldsMap(recordModalOptions.record, mappedFieldsConfig));
            }
        }


        modal.componentInstance.metadataView = recordModalOptions?.metadataView ?? 'recordView';
        modal.componentInstance.module = moduleName;
        modal.componentInstance.mode = mode as ViewMode;
        modal.componentInstance.minimizable = minimizable;
        modal.componentInstance.maximizable = maximizable;
        modal.componentInstance.titleKey =recordModalOptions?.headerLabelKey ?? recordModalOptions?.labelKey ?? '';
        modal.componentInstance.dynamicTitleKey = recordModalOptions?.dynamicTitleKey ??'';
        modal.componentInstance.dynamicTitleContext = recordModalOptions?.dynamicTitleContext ?? {};
        modal.componentInstance.descriptionKey = recordModalOptions?.descriptionLabelKey ??'';
        modal.componentInstance.dynamicDescriptionKey = recordModalOptions?.dynamicDescriptionKey ??'';
        modal.componentInstance.dynamicDescriptionContext = recordModalOptions?.dynamicDescriptionContext ??'';
        modal.componentInstance.parentId = parentId ?? '';
        modal.componentInstance.recordId = recordModalOptions?.recordId ?? '';
        modal.componentInstance.parentModule = parentModule ?? '';
        modal.componentInstance.modalHeaderActionsKlass = recordModalOptions?.headerActionsKlass ?? '';
        modal.componentInstance.headerClass = recordModalOptions.headerClass ?? '';
        modal.componentInstance.bodyClass = recordModalOptions.bodyClass ?? '';
        modal.componentInstance.closable = recordModalOptions.closable ?? true;
        modal.componentInstance.headerActionsKlass = recordModalOptions.headerActionsKlass ?? '';
        modal.componentInstance.footerClass = recordModalOptions.footerClass ?? '';
        modal.componentInstance.wrapperClass = recordModalOptions.wrapperClass ?? '';
        modal.componentInstance.closeConfirmationMessages = recordModalOptions.closeConfirmationMessage ?? [];
        modal.componentInstance.closeConfirmationLabel = recordModalOptions.closeConfirmationLabel ?? '';
        modal.componentInstance.closeConfirmationModal = recordModalOptions.closeConfirmationModal ?? false;
        modal.componentInstance.modalOptions = {...modalOptions ?? {}};

        modal.componentInstance.onMaximize.subscribe((maximize) => {

            const detachedModals = this.getDetachedModalsElement();

            if (maximize) {
                this.appState.getActiveModals().forEach(modalRef => {
                    if (modalRef !== modal) {
                        modalRef.componentInstance.minimize();
                    }
                });

                if (detachedModals) {
                    window.dispatchEvent(new Event('resize'));
                    detachedModals.classList.add('has-maximized-modal');
                }

                return;
            }

            const hasMaximized = this.appState.getActiveModals().some(modalRef => modalRef !== modal && modalRef.componentInstance.modalExpandStatus === 'maximized');

            if (!hasMaximized) {
                if (detachedModals) {
                    window.dispatchEvent(new Event('resize'));
                    detachedModals.classList.remove('has-maximized-modal');
                }
            }

        });

        modal.componentInstance.init();

        // Store modal reference to handle cleanup
        this.appState.addModalRef(modal);

        // Handle modal close/dismiss
        modal.result.then(
            () => this.appState.removeModalRef(modal),
            () => this.appState.removeModalRef(modal)
        );
    }

    protected getDetachedModalsElement(): HTMLElement | null {
        const detachedModals = window.document.body.querySelector('#detached-modals');

        if (detachedModals) {
            return detachedModals as HTMLElement;
        }

        return null;
    }


}
