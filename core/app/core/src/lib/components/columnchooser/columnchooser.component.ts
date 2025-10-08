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

import {Component, Input, OnInit} from '@angular/core';
import {CdkDragDrop, moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {ButtonInterface} from '../../common/components/button/button.model';
import {ColumnDefinition} from '../../common/metadata/list.metadata.model';
import {ModalCloseFeedBack} from '../../common/components/modal/modal.model';
import {AppStateStore} from "../../store/app-state/app-state.store";
import {LanguageStore} from "../../store/language/language.store";

@Component({
    selector: 'scrm-columnchooser',
    templateUrl: './columnchooser.component.html',
})

export class ColumnChooserComponent implements OnInit {
    @Input() displayed: ColumnDefinition[];
    @Input() hidden: ColumnDefinition[];

    titleKey = 'LBL_COLUMN_SELECTOR_MODAL_TITLE';
    closeButtonIcon: ButtonInterface;
    closeButton: ButtonInterface;
    saveButton: ButtonInterface;

    constructor(
        protected appState: AppStateStore,
        protected languageStore: LanguageStore,
        public modal: NgbActiveModal) {
    }

    drop(event: CdkDragDrop<{}[], any>): void {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            transferArrayItem(event.previousContainer.data,
                event.container.data,
                event.previousIndex,
                event.currentIndex);
        }
    }

    getHeaderLabel(): string {
        return this.languageStore.getFieldLabel('LBL_COLUMN_SELECTOR_MODAL_TITLE');
    }

    getColumnLabel(label: string): string {
        return this.languageStore.getFieldLabel(label, this.appState.getModule());
    }

    ngOnInit(): void {

        this.closeButtonIcon = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: (): void => {
                this.modal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

        this.closeButton = {
            klass: ['btn', 'modal-button-cancel'],
            labelKey: 'LBL_COLUMN_SELECTOR_CLOSE_BUTTON',
            onClick: (): void => {
                this.modal.close({
                    type: 'close-button'
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

        this.saveButton = {
            klass: ['btn', 'modal-button-save'],
            labelKey: 'LBL_COLUMN_SELECTOR_SAVE_BUTTON',
            onClick: (): void => {
                this.modal.close({
                    type: 'close-button',
                    displayed: this.displayed,
                    hidden: this.hidden
                } as ModalCloseFeedBack);
            }
        } as ButtonInterface;

    }

}
