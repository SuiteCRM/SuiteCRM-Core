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

import {CdkDragDrop, CdkDropList} from '@angular/cdk/drag-drop';
import {NgbActiveModal} from '@ng-bootstrap/ng-bootstrap';
import {ColumnDefinition} from '../../common/metadata/list.metadata.model';
import {ColumnChooserComponent} from './columnchooser.component';

describe('ColumnChooserComponent', () => {
    let component: ColumnChooserComponent;
    let modal: NgbActiveModal;
    const name = {name: 'name', label: 'LBL_NAME'} as ColumnDefinition;
    const phone = {name: 'phone', label: 'LBL_PHONE'} as ColumnDefinition;
    const email = {name: 'email', label: 'LBL_EMAIL'} as ColumnDefinition;

    beforeEach(() => {
        modal = new NgbActiveModal();
        spyOn(modal, 'close');
        component = new ColumnChooserComponent(null, null, modal);
        component.displayed = [name, phone];
        component.hidden = [email];
        component.ngOnInit();
    });

    it('reorders a column within the displayed list', () => {
        const displayed = {data: component.displayed} as CdkDropList<ColumnDefinition[]>;
        component.drop({
            container: displayed, previousContainer: displayed, previousIndex: 1, currentIndex: 0
        } as CdkDragDrop<ColumnDefinition[]>);

        expect(component.displayed).toEqual([phone, name]);
        expect(component.hidden).toEqual([email]);
    });

    it('saves a column moved from the hidden list into the displayed list', () => {
        component.drop({
            container: {data: component.displayed}, previousContainer: {data: component.hidden},
            previousIndex: 0, currentIndex: 1
        } as CdkDragDrop<ColumnDefinition[]>);
        component.saveButton.onClick();

        expect(modal.close).toHaveBeenCalledOnceWith({
            type: 'close-button', displayed: [name, email, phone], hidden: []
        });
    });

    it('closes without submitting a selection when cancelled', () => {
        component.closeButton.onClick();
        expect(modal.close).toHaveBeenCalledOnceWith({type: 'close-button'});
    });
});
