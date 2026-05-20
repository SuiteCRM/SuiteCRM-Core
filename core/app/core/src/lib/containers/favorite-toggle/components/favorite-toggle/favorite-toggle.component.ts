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

import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {Record} from '../../../../common/record/record.model';
import {FavoritesService} from '../../../../services/navigation/favorites/favorites.service';
import {ImmediateDebounce} from '../../../../services/utils/immediate-debounce.service';

@Component({
    selector: 'scrm-favorite-toggle',
    templateUrl: './favorite-toggle.component.html',
    styleUrls: []
})
export class FavoriteToggleComponent implements OnInit, OnDestroy {
    @Input() record: Record;
    addButton: ButtonInterface;
    removeButton: ButtonInterface;
    favorite: boolean = false;
    protected debounceService: ImmediateDebounce;

    constructor(
        protected handler: FavoritesService,
    ) {
    }

    ngOnInit(): void {

        if (!this.record) {
            return;
        }

        this.debounceService = new ImmediateDebounce();
        this.debounceService.init();

        this.favorite = this?.record?.favorite ?? false;

        this.addButton = {
            klass: ['btn btn-sm btn-outline-light favorite-star favorite-off'],
            onClick: () => {
                this.debounceService.debounce(() => {
                    this.add();
                })
            },
            icon: 'star'
        } as ButtonInterface;

        this.removeButton = {
            klass: ['btn btn-sm btn-outline-light favorite-star favorite-on'],
            onClick: () => {
                this.debounceService.debounce(() => {
                    this.remove();
                })
            },
            icon: 'star'
        } as ButtonInterface;
    }

    ngOnDestroy(): void {
        this.debounceService.destroy();
    }

    protected add(): void {
        this.record.favorite = true;
        this.favorite = true;
        const favorite = this.handler.build(this.record.module, this.record.id);
        this.handler.add(this.record.module, favorite);
    }

    protected remove(): void {
        this.record.favorite = false;
        this.favorite = false;
        const favorite = this.handler.build(this.record.module, this.record.id);
        this.handler.remove(this.record.module, favorite);
    }
}
