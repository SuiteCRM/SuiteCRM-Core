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

import {Injectable} from '@angular/core';
import {Record} from '../../common/record/record.model';
import {RecordListModalComponent} from '../../containers/record-list-modal/components/record-list-modal/record-list-modal.component';
import {RecordListModalResult} from '../../containers/record-list-modal/components/record-list-modal/record-list-modal.model';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {LanguageStore} from '../../store/language/language.store';
import {MessageService} from '../message/message.service';


@Injectable({
    providedIn: 'root'
})
export class SelectModalService {

    constructor(
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected modalService: NgbModal
    ) {
    }


    /**
     * Get Selected Record
     *
     * @param {string} selectModule: The Modal module
     * @param onSelectCallback
     * @returns {void}
     */
    showSelectModal(selectModule: string, onSelectCallback: Function = null): void {

        const modal = this.modalService.open(RecordListModalComponent, {size: 'xl', scrollable: true});
        modal.componentInstance.module = selectModule;
        modal.result.then(
            (result: RecordListModalResult) => {

                if (!result || !result.selection || !result.selection.selected) {
                    return;
                }

                const record: Record = this.getSelectedRecord(result);
                if (!record.id) {
                    let message = this.languageStore.getFieldLabel('ERROR_NO_RECORD');
                    this.message.addDangerMessage(message);
                    return;
                }

                if (onSelectCallback !== null) {
                    onSelectCallback(record);
                }
            },
            () => {
                // Modal dismissed
            }
        );
    }

    /**
     * Get Selected Record
     *
     * @param {object} data RecordListModalResult
     * @returns {object} Record
     */
    protected getSelectedRecord(data: RecordListModalResult): Record {

        let id = '';
        Object.keys(data.selection.selected).some(selected => {
            id = selected;
            return true;
        });

        let record: Record = null;

        data.records.some(rec => {
            if (rec && rec.id === id) {
                record = rec;
                return true;
            }
        });

        return record;
    }

}
