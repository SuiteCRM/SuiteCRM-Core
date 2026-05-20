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

import {Directive, ElementRef, HostListener, Input, OnChanges, OnDestroy, OnInit, SimpleChanges} from '@angular/core';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {tap} from 'rxjs/operators';
import {Subscription} from 'rxjs';

@Directive({
    selector: '[scrm-button-loading]'
})
export class ButtonLoadingDirective implements OnInit, OnDestroy, OnChanges {

    @Input('scrm-button-loading') state: boolean;
    private subscription: Subscription;
    private appLoading = false;

    constructor(private el: ElementRef, private appStateStore: AppStateStore) {
    }

    ngOnInit(): void {
        this.subscription = this.appStateStore.loading$.pipe(tap((loading: boolean) => {
            this.appLoading = loading;
            this.updateComponent();
        })).subscribe();
        this.updateComponent();
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes.state) {
            this.updateComponent();
        }
    }

    ngOnDestroy(): void {
        this.subscription.unsubscribe();
    }

    @HostListener('click')
    clickEvent(): void {
        this.updateComponent();
    }

    /**
     * Calculate loading state and update loading
     */
    private updateComponent(): void {
        const loading = this.isLoading();
        this.setDisabledState(loading);
    }

    /**
     * Calculate if is loading
     *
     * @returns {boolean} isLoading
     */
    private isLoading(): boolean {
        let loading = false;

        if (this.state === true || this.appLoading === true) {
            loading = true;
        }

        return loading;
    }

    /**
     * Disable or enable button
     *
     * @param {boolean} state to set
     */
    private setDisabledState(state: boolean): void {
        this.el.nativeElement.disabled = state;
    }
}
