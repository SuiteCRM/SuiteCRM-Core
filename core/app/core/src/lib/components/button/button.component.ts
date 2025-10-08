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

import {Component, Input, OnDestroy, OnInit, signal, Signal} from '@angular/core';
import {ButtonCallback, ButtonInterface} from '../../common/components/button/button.model';
import {LanguageStore} from '../../store/language/language.store';
import {Observable, Subject, Subscription} from 'rxjs';
import {debounceTime} from 'rxjs/operators';

@Component({
    selector: 'scrm-button',
    templateUrl: 'button.component.html'
})
export class ButtonComponent implements OnInit, OnDestroy {
    @Input() config: ButtonInterface;

    isRunning: Signal<boolean> = signal(false);
    dynamicClass: Signal<string> = signal('');
    dynamicIcon: Signal<string> = signal('');
    disabled: Signal<boolean> = signal(false);
    clickCallBack: ButtonCallback;
    protected clickBuffer = new Subject<any>();
    protected clickBuffer$: Observable<any> = this.clickBuffer.asObservable();
    protected subs: Subscription[] = [];

    constructor(public language: LanguageStore) {
    }

    ngOnInit(): void {
        const isToDebounce = this.config?.debounceClick ?? null;
        this.clickCallBack = this.config?.onClick ?? null;
        const clickDebounceTime = this.getDebounceTime();

        if (this.config?.dynamicClass) {
            this.dynamicClass = this.config?.dynamicClass;
        }

        if (this.config?.dynamicIcon) {
            this.dynamicIcon = this.config?.dynamicIcon;
        }

        if (this.config?.disabled) {
            this.disabled = this.config?.disabled;
        }

        if (this.config.isRunning){
            this.isRunning = this.config.isRunning;
        }

        if (isToDebounce && this.clickCallBack) {
            this.subs.push(this.clickBuffer$.pipe(debounceTime(clickDebounceTime)).subscribe(value => {
                const input = value ?? null;
                this.clickCallBack(input);
            }));
        }
    }


    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    runClick(input?: any): void {
        const isToDebounce = this.config?.debounceClick ?? null;

        if (!this.clickCallBack) {
            return;
        }

        if (isToDebounce && this.clickCallBack) {
            this.clickBuffer.next(input ?? null);
            return;
        }

        this.clickCallBack(input ?? null);
    }

    /**
     * Get debounce time
     * @return number
     * @protected
     */
    protected getDebounceTime(): number {
        let clickDebounceTime = this.config?.clickDebounceTime ?? 625;
        if (!isFinite(clickDebounceTime)) {
            clickDebounceTime = 625;
        }
        return clickDebounceTime;
    }
}
