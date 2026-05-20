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
import {Component, EventEmitter, Input, OnDestroy, OnInit, signal, WritableSignal} from '@angular/core';
import {LabelModule} from "../../../label/label.module";
import {PopupButtonModule} from "../popup-button/popup-button.module";
import {ButtonModule} from "../../../button/button.module";
import {FormsModule, ReactiveFormsModule} from "@angular/forms";
import {DropdownButtonInterface} from "../../../../common/components/button/dropdown-button.model";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {NgForOf, NgIf} from "@angular/common";
import {LanguageStore} from "../../../../store/language/language.store";

@Component({
    selector: 'scrm-insert-link-popup-button',
    standalone: true,
    imports: [
        LabelModule,
        PopupButtonModule,
        ButtonModule,
        FormsModule,
        NgForOf,
        NgIf,
        ReactiveFormsModule,
    ],
    templateUrl: './insert-link-popup-button.component.html',
})
export class InsertLinkPopupButtonComponent implements OnInit, OnDestroy {

    @Input('config') config: DropdownButtonInterface;
    @Input() openStatusEventEmitter: EventEmitter<boolean>;
    @Input() linkEventEmitter: EventEmitter<string>;
    @Input() displayButton: WritableSignal<boolean> = signal(true);
    @Input() linkUrl: string = '';
    @Input() inputClass: string = 'form-control form-control-sm';
    buttons: ButtonInterface[] = [];
    placeHolderLabel: string;
    protected subs: any[] = [];

    constructor(protected language: LanguageStore) {
    }

    ngOnInit(): void {
        this.placeHolderLabel = this.language.getFieldLabel('LBL_INSERT_LINK_PLACEHOLDER');
        this.buttons = [];
        this.subs = [];

        if (this?.config?.items?.length) {
            this?.config?.items.forEach((item: ButtonInterface) => {
                this.buttons.push({
                    ...item,
                    onClick: (event) => {
                        item?.onClick(this.linkUrl);
                    },
                })
            });
        }

        this.subs.push(this.linkEventEmitter.subscribe((string) => {
            this.linkUrl = string;
        }));
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
    }
}
