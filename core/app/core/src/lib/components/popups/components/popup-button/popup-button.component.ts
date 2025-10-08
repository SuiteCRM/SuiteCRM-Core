/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

import {
    Component,
    EventEmitter,
    Input,
    OnDestroy,
    OnInit, Signal,
    signal,
    ViewChild,
    WritableSignal
} from '@angular/core';
import {ButtonInterface, PopoverValidation} from '../../../../common/components/button/button.model';
import {Subscription} from "rxjs";
import {NgbPopover} from "@ng-bootstrap/ng-bootstrap";


@Component({
    selector: 'scrm-popup-button',
    templateUrl: 'popup-button.component.html',
})

export class PopupButtonComponent implements OnInit, OnDestroy {

    @Input() icon: string;
    @Input() titleKey: string;
    @Input() klass: string = 'line-action-item line-action float-right';
    @Input() placement: string = 'right';
    @Input() popoverClass: string = 'popover-wrapper';
    @Input() openStatusEventEmitter: EventEmitter<boolean>;
    @Input() displayButton: WritableSignal<boolean> = signal(true);
    @Input() dynamicClass: Signal<string> = signal('');
    @Input() disabled: Signal<boolean> = signal(false);
    @Input() showPopup: PopoverValidation = () => {return true};

    @ViewChild('popover') popover: NgbPopover;

    buttonConfig = signal<ButtonInterface>({});

    protected subs: Subscription[] = [];

    constructor() {
    }

    ngOnInit(): void {
        this.buttonConfig.update(() => this.getButtonConfig());
        this.subs = [];

        if (this.openStatusEventEmitter) {
            this.subs.push(this.openStatusEventEmitter.subscribe((status: boolean) => {
                if (status === true) {
                    this.popover.open();
                    return;
                }

                this.popover.close();
            }));
        }
    }

    ngOnDestroy(): void {
        this.subs.forEach((sub: Subscription) => {
            sub.unsubscribe();
        });
        this.subs = [];
    }

    getButtonConfig(): ButtonInterface {
        return {
            icon: this.icon,
            klass: this.klass,
            titleKey: this.titleKey,
            dynamicClass: this.dynamicClass,
            disabled: this.disabled
        } as ButtonInterface;
    }


}
