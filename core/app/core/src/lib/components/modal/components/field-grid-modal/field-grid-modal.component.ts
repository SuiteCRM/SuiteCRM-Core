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

import {Component, Input} from '@angular/core';
import {ModalModule} from "../modal/modal.module";
import {FieldGridModule} from "../../../field-grid/field-grid.module";
import {ModalCloseFeedBack} from "../../../../common/components/modal/modal.model";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {NgbActiveModal} from "@ng-bootstrap/ng-bootstrap";
import {ModalFieldBuilder} from "../../../../services/record/field/modal-field.builder";
import {ButtonModule} from "../../../button/button.module";
import {FieldModule} from "../../../../fields/field.module";
import {deepClone} from "../../../../common/utils/object-utils";
import {MessageService} from "../../../../services/message/message.service";
import {ViewFieldDefinition} from "../../../../common/metadata/metadata.model";
import {FieldGridOptions} from "../../../field-grid/field-grid.model";
import {FieldModalResult} from "../../../../services/modals/field-modal.service";
import {Field} from "../../../../common/record/field.model";
import {Observable} from "rxjs";
import {Process} from "../../../../services/process/process.service";
import {take} from "rxjs/operators";


const fieldGridDefaultOptions = {
  special: false,
  actions: false,
  appendActions: false,
  labelDisplay: 'top',
  labelClass: {},
  inputClass: {},
  rowClass: {},
  colClass: {},
  colAlignItems: '',
  maxColumns: 3,
  sizeMap: {
    handset: 1,
    tablet: 2,
    web: 3,
    wide: 4
  }
} as FieldGridOptions;


export declare type FieldModalValidationFunction = (fields) => Observable<Process>;

@Component({
  selector: 'scrm-field-grid-modal',
  standalone: true,
  imports: [
    ModalModule,
    FieldGridModule,
    ButtonModule,
    FieldModule
  ],
  templateUrl: './field-grid-modal.component.html',
})
export class FieldGridModalComponent {

    @Input() fields: ViewFieldDefinition[];
    @Input() titleKey: string = '';
    @Input() descriptionKey: string = '';
    @Input() module: string;
    @Input() limit = '';
    @Input() limitEndLabel = '';
    @Input() fieldGridOptions: FieldGridOptions = deepClone(fieldGridDefaultOptions);
    @Input() actionLabelKey: string = 'LBL_ACTIONS';
    @Input() validation: FieldModalValidationFunction;

    cancelButton: ButtonInterface;
    mappedFields: Field[];
    crossButton: ButtonInterface;
    actionButton: ButtonInterface;

    constructor(
        public activeModal: NgbActiveModal,
        protected modalFieldBuilder: ModalFieldBuilder,
        protected message: MessageService
    ) {
    }

    ngOnInit(): void {
        this.buildFields();
        this.initFieldGridOptions();
        this.initButtons();
    }

    protected initButtons() {
        this.cancelButton = {
            klass: 'btn btn-primary btn-sm mt-3 mb-2',
            labelKey: 'LBL_CANCEL',
            onClick: (): void => {
                this.activeModal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

        this.crossButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: (): void => {
                this.activeModal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

        this.actionButton = {
            klass: 'btn btn-primary btn-sm mt-3 mb-2',
            labelKey: this.actionLabelKey,
            onClick: (): void => {
                if (this.validation) {
                    this.validation(this.mappedFields).pipe(take(1)).subscribe((process) => {

                        if ((process?.status ?? false) === 'success') {
                            this.activeModal.close({
                                fields: this.mappedFields,
                                module: this.module,
                                type: 'run',
                            } as FieldModalResult);
                        }

                        return false;
                    });

                    return;
                }
                this.activeModal.close({
                    fields: this.mappedFields,
                    module: this.module,
                    type: 'run',
                } as FieldModalResult);
            }
        } as ButtonInterface;
    }

    protected buildFields() {
        const fields = [];

        Object.entries(this.fields).forEach(([key, field]) => {
            fields.push(this.modalFieldBuilder.buildModalField(this.module, field))
        })

        this.mappedFields = fields;
    }

    protected initFieldGridOptions() {
        const options = fieldGridDefaultOptions;
        Object.entries(fieldGridDefaultOptions).forEach(([key, value]) => {
            if (this.fieldGridOptions[key]) {
                options[key] = this.fieldGridOptions[key];
            }
        })

        this.fieldGridOptions = options;
    }

}
