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
import {BreakpointObserver, Breakpoints, BreakpointState} from '@angular/cdk/layout';
import {BehaviorSubject, merge} from 'rxjs';
import {map} from 'rxjs/operators';

export enum ScreenSize {
    XSmall = 'XSmall',
    Small = 'Small',
    Medium = 'Medium',
    Large = 'Large',
    XLarge = 'XLarge'
}

@Injectable({
    providedIn: 'root'
})
export class ScreenSizeObserverService {

    screenSize = new BehaviorSubject<ScreenSize>(ScreenSize.Medium);
    screenSize$ = this.screenSize.asObservable();
    bootstrapScreenSize = new BehaviorSubject<ScreenSize>(ScreenSize.Medium);
    bootstrapScreenSize$ = this.bootstrapScreenSize.asObservable();

    constructor(protected breakpointObserver: BreakpointObserver) {
        this.initScreenSizeObservable();
    }

    protected initScreenSizeObservable(): void {
        merge(
            this.breakpointObserver.observe([
                Breakpoints.XSmall,
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.XSmall;
                }
            })),
            this.breakpointObserver.observe([
                Breakpoints.Small,
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.Small;
                }
            })),
            this.breakpointObserver.observe([
                Breakpoints.Medium,
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.Medium;
                }
            })),
            this.breakpointObserver.observe([
                Breakpoints.Large,
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.Large;
                }
            })),
            this.breakpointObserver.observe([
                Breakpoints.XLarge,
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.XLarge;
                }
            }))
        ).subscribe((value) => {
            if (value) {
                this.screenSize.next(value);
            }
        });

        merge(
            this.breakpointObserver.observe([
               '(max-width: 575px)',
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.XSmall;
                }
            })),
            this.breakpointObserver.observe([
                '(min-width: 576px) and (max-width: 766px)',
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.Small;
                }
            })),
            this.breakpointObserver.observe([
                '(min-width: 768px) and (max-width: 991px)',
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.Medium;
                }
            })),
            this.breakpointObserver.observe([
                '(min-width: 992px) and (max-width: 1199px)',
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.Large;
                }
            })),
            this.breakpointObserver.observe([
                '(min-width: 1200px)',
            ]).pipe(map((result: BreakpointState) => {
                if (result.matches) {
                    return ScreenSize.XLarge;
                }
            }))
        ).subscribe((value) => {
            if (value) {
                this.bootstrapScreenSize.next(value);
            }
        });
    }
}
