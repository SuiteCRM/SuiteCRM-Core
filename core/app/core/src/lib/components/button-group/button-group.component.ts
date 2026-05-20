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

import {Component, Input, OnDestroy, OnInit, signal, WritableSignal} from '@angular/core';
import {Button} from '../../common/components/button/button.model';
import {ButtonGroupInterface} from '../../common/components/button/button-group.model';
import {AnyButtonInterface, DropdownButtonInterface} from '../../common/components/button/dropdown-button.model';

import {Observable, Subscription} from 'rxjs';

interface SplitButtons {
    expanded: AnyButtonInterface[];
    collapsed: AnyButtonInterface[];
}

@Component({
    selector: 'scrm-button-group',
    templateUrl: './button-group.component.html',
    styles: [],
})
export class ButtonGroupComponent implements OnInit, OnDestroy {

    @Input() config$: Observable<ButtonGroupInterface>;
    @Input() klass: string = '';

    buttons: WritableSignal<SplitButtons> = signal({
        expanded: [],
        collapsed: [],
    });

    dropdownConfig: DropdownButtonInterface;

    protected internalConfig: ButtonGroupInterface;
    private sub: Subscription;


    constructor() {
    }

    ngOnInit(): void {
        this.sub = this.config$.subscribe(config => {
            this.internalConfig = {...config};
            this.splitButtons();
        });
    }

    ngOnDestroy(): void {
        this.sub.unsubscribe();
    }

    buildDropdownConfig(): void {

        let buttonClasses = ['button-group-button'];

        if (this.internalConfig.buttonKlass && this.internalConfig.buttonKlass.length > 0) {
            buttonClasses = buttonClasses.concat(this.internalConfig.buttonKlass);
        }

        if (this?.internalConfig?.dropdownOptions?.klass) {
            buttonClasses = buttonClasses.concat(this.internalConfig.dropdownOptions.klass);
        }

        let wrapperClasses = ['button-group-dropdown'];

        const dropdownOptions = this.internalConfig.dropdownOptions;
        const optionsWrapperKlass = dropdownOptions && dropdownOptions.wrapperKlass;

        if (optionsWrapperKlass && optionsWrapperKlass.length > 0) {
            wrapperClasses = wrapperClasses.concat(optionsWrapperKlass);
        }

        this.dropdownConfig = {
            label: this.internalConfig.dropdownLabel,
            klass: [...buttonClasses],
            wrapperKlass: wrapperClasses,
            items: this.buttons().collapsed,
        } as DropdownButtonInterface;

        if (this.internalConfig.dropdownOptions && this.internalConfig.dropdownOptions.placement) {
            this.dropdownConfig.placement = this.internalConfig.dropdownOptions.placement;
        }

        if (this.internalConfig.dropdownOptions && this.internalConfig.dropdownOptions.icon) {
            this.dropdownConfig.icon = this.internalConfig.dropdownOptions.icon;
        }
    }

    protected getBreakpoint(): number {

        if (!this.internalConfig.breakpoint && this.internalConfig.breakpoint !== 0) {
            return 4;
        }

        return this.internalConfig.breakpoint;
    }

    protected splitButtons(): void {

        const expanded: AnyButtonInterface[] = [];
        const collapsed: AnyButtonInterface[] = [];

        if (!this.internalConfig.buttons || this.internalConfig.buttons.length < 1) {
            this.buttons.set({ expanded, collapsed });
            return;
        }

        let count = 0;

        const showAfterBreakpoint = this.internalConfig.showAfterBreakpoint ?? true;
        const breakPoint = this.getBreakpoint();

        const pushToExpanded = [];
        this.internalConfig.buttons.forEach(button => {

            if (!button) {
                return;
            }

            if (count < breakPoint) {
                let classes = ['button-group-button'];
                if (this.internalConfig.buttonKlass && this.internalConfig.buttonKlass.length > 0) {
                    classes = classes.concat(this.internalConfig.buttonKlass);
                }
                const newButton = {...button};
                Button.appendClasses(newButton, [...classes]);

                expanded.push(newButton);
            } else if (showAfterBreakpoint === true) {
                if (this.internalConfig.pushActiveToExpanded && button?.active) {
                    pushToExpanded.push({...button});
                    count++;
                    return;
                }
                collapsed.push({...button});
            }

            count++;
        });

        this.applyPushActiveToExpanded(pushToExpanded, expanded, collapsed);

        this.buildDropdownConfig();
    }

    protected applyPushActiveToExpanded(pushToExpanded: any[], expanded: AnyButtonInterface[], collapsed: AnyButtonInterface[]): void {
        const pushToExpandedLength = pushToExpanded?.length;

        if (!pushToExpandedLength) {
            this.buttons.set({ expanded, collapsed });
            return;
        }

        const expandedLength = expanded?.length ?? 0;
        const pushToCollapsedCount = expandedLength - pushToExpandedLength;

        if (pushToCollapsedCount < 0) {
            const overflow = pushToExpanded.slice(pushToCollapsedCount);
            const pushToCollapsed = overflow.concat([...expanded]);
            const newExpanded = pushToExpanded.slice(expanded.length);
            const newCollapsed = pushToCollapsed.concat(collapsed);
            this.buttons.set({ expanded: newExpanded, collapsed: newCollapsed });
            return;
        }

        if (pushToCollapsedCount === 0) {
            const newCollapsed = [...pushToExpanded].concat(collapsed);
            this.buttons.set({ expanded: [...pushToExpanded], collapsed: newCollapsed });
            return;
        }

        const pushToCollapsed = expanded.slice(-1 * pushToExpandedLength);
        const keepOnExpanded = expanded.slice(0, pushToCollapsedCount);
        const newExpanded = keepOnExpanded.concat([...pushToExpanded]);
        const newCollapsed = pushToCollapsed.concat(collapsed);
        this.buttons.set({ expanded: newExpanded, collapsed: newCollapsed });
    }
}
