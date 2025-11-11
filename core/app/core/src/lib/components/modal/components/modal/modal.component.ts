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

import {Component, EventEmitter, Input, OnDestroy, OnInit, Output, signal, WritableSignal} from '@angular/core';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {MinimiseButtonStatus} from "../../../minimise-button/minimise-button.component";
import {Observable, Subscription} from "rxjs";
import {StringMap} from "../../../../common/types/string-map";
import {FieldMap} from "../../../../common/record/field.model";
import {toObservable} from "@angular/core/rxjs-interop";
import {MaximizeButtonStatus} from "../../../maximize-button/maximize-button.component";

@Component({
    selector: 'scrm-modal',
    templateUrl: './modal.component.html',
    styleUrls: [],
})
export class ModalComponent implements OnInit, OnDestroy {

    @Input() klass = '';
    @Input() headerKlass = '';
    @Input() bodyKlass = '';
    @Input() footerKlass = '';
    @Input() titleKey = '';
    @Input() dynamicTitleKey = '';
    @Input() dynamicTitleContext: WritableSignal<StringMap> = signal({});
    @Input() dynamicTitleFields: WritableSignal<FieldMap> = signal({});
    @Input() descriptionKey = '';
    @Input() dynamicDescriptionKey = '';
    @Input() dynamicDescriptionContext: WritableSignal<StringMap> = signal({});
    @Input() dynamicDescriptionFields: WritableSignal<FieldMap> = signal({});
    @Input() limit = '';
    @Input() limitEndLabel = '';
    @Input() limitLabel = 'LBL_LIMIT';
    @Input() closable: boolean = false;
    @Input() minimizable: boolean = false;
    @Input() maximizable: boolean = false;
    @Input() isMinimized$: Observable<boolean>;
    @Input() isMaximized$: Observable<boolean>;
    @Input() close: ButtonInterface = {
        klass: ['btn', 'btn-outline-light', 'btn-sm']
    } as ButtonInterface;
    @Output('onMinimizeToggle') onMinimizeToggle: EventEmitter<boolean> = new EventEmitter<boolean>();
    @Output('onMaximizeToggle') onMaximizeToggle: EventEmitter<boolean> = new EventEmitter<boolean>();

    isMinimized: WritableSignal<boolean> = signal(false);
    minimiseButton: ButtonInterface;
    minimiseStatus: WritableSignal<MinimiseButtonStatus> = signal('maximised');
    minimiseStatus$: Observable<MinimiseButtonStatus> = toObservable(this.minimiseStatus);

    isMaximized: WritableSignal<boolean> = signal(false);
    maximizeButton: ButtonInterface;
    maximizeStatus: WritableSignal<MaximizeButtonStatus> = signal('inactive');
    maximizeStatus$: Observable<MaximizeButtonStatus> = toObservable(this.maximizeStatus);

    protected subs: Subscription[] = [];

    ngOnInit(): void {
        if (this.isMinimized$) {
            this.subs.push(this.isMinimized$.subscribe(minimize => {
                this.isMinimized.set(minimize);
                this.initMinimiseButton();
                if (this.isMinimized()) {
                    this.isMaximized.set(false);
                    this.initMaximizeStatus();
                }
            }));
        }
        if (this.isMaximized$) {
            this.subs.push(this.isMaximized$.subscribe(maximize => {
                this.isMaximized.set(maximize);
                this.initMaximizeButton();
                if (this.isMaximized()) {
                    this.isMinimized.set(false);
                    this.initMinimiseStatus();
                }
            }));
        }
        this.initMinimiseButton();
        this.initMaximizeButton();
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    initMinimiseButton(): void {
        this.minimiseButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: () => {
                this.toggleMinimize();
            },
        } as ButtonInterface;
        this.initMinimiseStatus();
    }

    toggleMinimize() {
        this.isMinimized.set(!this.isMinimized());
        this.onMinimizeToggle.emit(this.isMinimized());
        this.initMinimiseStatus();
        if (this.isMinimized()) {
            this.isMaximized.set(false);
            this.initMaximizeStatus();
        }
    }

    initMinimiseStatus(): void {
        if (this.isMinimized()) {
            this.minimiseStatus.set('minimised');
            return;
        }
        this.minimiseStatus.set('maximised');
    }

    initMaximizeButton(): void {
        this.maximizeButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm', 'border-0'],
            onClick: () => {
                this.toggleMaximize();
            },
        } as ButtonInterface;
        this.initMaximizeStatus();
    }

    toggleMaximize(): void {
        this.isMaximized.set(!this.isMaximized());
        this.onMaximizeToggle.emit(this.isMaximized());
        this.initMaximizeStatus();
        if (this.isMaximized()) {
            this.isMinimized.set(false);
            this.initMinimiseStatus();
        }
    }

    initMaximizeStatus(): void {
        if (this.isMaximized()) {
            this.maximizeStatus.set('active');
            return;
        }
        this.maximizeStatus.set('inactive');
    }

}
