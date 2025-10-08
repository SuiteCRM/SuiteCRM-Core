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

import {Component, Input, OnDestroy, OnInit, signal, WritableSignal} from '@angular/core';
import {filter, take} from "rxjs/operators";
import {CommonModule} from "@angular/common";
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {animate, transition, trigger} from '@angular/animations';
import {combineLatest, Observable, Subscription} from 'rxjs';
import {LabelModule} from "../../../../components/label/label.module";
import {RecordModalStore} from "../../store/record-modal/record-modal.store";
import {ModalModule} from "../../../../components/modal/components/modal/modal.module";
import {RecordModalStoreFactory} from "../../store/record-modal/record-modal.store.factory";
import {RecordModalContentAdapterFactory} from "../../adapters/record-modal-content.adapter.factory";
import {RecordModalActionsAdapterFactory} from "../../adapters/record-modal-actions-adapter.factory";
import {RecordContentModule} from "../../../../components/record-content/record-content.module";
import {LoadingSpinnerModule} from "../../../../components/loading-spinner/loading-spinner.module";
import {ActionGroupMenuModule} from "../../../../components/action-group-menu/action-group-menu.module";
import {ViewMode} from "../../../../common/views/view.model";
import {ActionContext} from "../../../../common/actions/action.model";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {ModalCloseFeedBack} from "../../../../common/components/modal/modal.model";
import {Record} from "../../../../common/record/record.model";
import {ObjectMap} from "../../../../common/types/object-map";
import {StringMap} from "../../../../common/types/string-map";
import {FieldMap} from "../../../../common/record/field.model";
import {deepClone} from "../../../../common/utils/object-utils";
import {ConfirmationModalService} from "../../../../services/modals/confirmation-modal.service";

@Component({
    selector: 'scrm-record-modal',
    templateUrl: './record-modal.component.html',
    styleUrls: [],
    standalone: true,
    imports: [
        CommonModule,
        ModalModule,
        LabelModule,
        LoadingSpinnerModule,
        RecordContentModule,
        ActionGroupMenuModule
    ],
    animations: [
        trigger('modalFade', [
            transition('void <=> *', [
                animate('800ms')
            ]),
        ]),
    ]
})
export class RecordModalComponent implements OnInit, OnDestroy {

    @Input() titleKey = '';
    @Input() dynamicTitleKey = '';
    @Input() descriptionKey = '';
    @Input() dynamicDescriptionKey = '';
    @Input() module: string;
    @Input() metadataView: string = 'recordView';
    @Input() mode: ViewMode;
    @Input() minimizable: boolean = false;
    @Input() recordId: string = '';
    @Input() parentId: string = '';
    @Input() parentModule: string = '';
    @Input() mappedFields: ObjectMap = null;
    @Input() contentAdapter: any = null;
    @Input() actionsAdapter: any = null;
    @Input() headerClass: string = '';
    @Input() bodyClass: string = '';
    @Input() footerClass: string = '';
    @Input() wrapperClass: string = '';
    @Input() context: WritableSignal<StringMap> = signal({});
    @Input() fields: WritableSignal<FieldMap> = signal({});
    @Input() closeConfirmationLabel: string = '';
    @Input() closeConfirmationMessages: string[] = [];
    @Input() closeConfirmationModal: boolean = false;

    validating: WritableSignal<boolean> = signal(false);

    record: Record;
    modalStore: RecordModalStore;
    viewContext: ActionContext;
    closeButton: ButtonInterface;

    loading$: Observable<boolean>;
    isMinimized: WritableSignal<boolean> = signal(false);
    protected subs: Subscription[] = [];

    constructor(
        protected activeModal: NgbActiveModal,
        protected storeFactory: RecordModalStoreFactory,
        protected confirmation: ConfirmationModalService,
        protected recordModalContentAdapterFactory: RecordModalContentAdapterFactory,
        protected recordModalActionsAdapterFactory: RecordModalActionsAdapterFactory
    ) {
    }

    ngOnInit(): void {


        this.closeButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: (): void => {

                if (this.closeConfirmationModal) {
                    const confirmation = [this.closeConfirmationLabel, ...this.closeConfirmationMessages] ?? [];
                    this.confirmation.showModal(confirmation, () => {
                        this.activeModal.close({
                            type: 'close-button'
                        } as ModalCloseFeedBack);
                    });
                    return;
                }
                this.activeModal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;


        this.subs.push(this.modalStore.validating$.subscribe((validating: boolean) => {
            this.validating.set(validating);
        }));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.contentAdapter = null
        this.actionsAdapter = null

        this.modalStore.clear();
        this.modalStore = null;
        this.subs = [];
    }

    init(): void {
        this.record = null;
        this.subs = [];
        this.contentAdapter = null;
        this.actionsAdapter = null;
        this.modalStore = null;


        this.modalStore = this.storeFactory.create(this.metadataView);
        this.contentAdapter = this.recordModalContentAdapterFactory.create(this.modalStore);
        this.actionsAdapter = this.recordModalActionsAdapterFactory.create(this.modalStore, this.activeModal);


        this.subs.push(
            this.modalStore.loadMetadata(this.module).pipe(take(1)).subscribe(() => {
                this.initStore();
                this.subs.push(
                    combineLatest([this.modalStore.stagingRecord$, this.modalStore.loading$, this.modalStore.viewContext$]).pipe(
                        filter(([record, loading, viewContext]) => !!record && !loading),
                        take(1)
                    ).subscribe(([record, loading, viewContext]): void => {
                        this.record = record;
                        this.fields.set({...(record.fields ?? {})})
                        this.viewContext = {...viewContext};
                    })
                );
            })
        );
    }

    protected initStore(): void {
        if (!this.module) {
            return;
        }

        this.modalStore.init(this.module, this.recordId, this.mode, deepClone(this.mappedFields ?? {}));
        this.loading$ = this.modalStore.metadataLoading$;
    }

    onMinimizeToggle($event: boolean) {
        this.isMinimized.set($event);
    }
}
