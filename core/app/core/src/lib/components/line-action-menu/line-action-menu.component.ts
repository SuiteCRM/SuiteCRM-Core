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

import {Component, ElementRef, HostListener, Input, OnDestroy, OnInit, signal} from '@angular/core';
import {Action, ActionContext, ActionDataSource, ActiveLineAction} from '../../common/actions/action.model';
import {Button, ButtonInterface} from '../../common/components/button/button.model';
import {ButtonGroupInterface} from '../../common/components/button/button-group.model';
import {isFalse} from '../../common/utils/value-utils';
import {Record} from '../../common/record/record.model';
import {LanguageStore, LanguageStrings} from '../../store/language/language.store';
import {BehaviorSubject, combineLatestWith, Observable, Subscription} from 'rxjs';
import {
    ScreenSize,
    ScreenSizeObserverService
} from '../../services/ui/screen-size-observer/screen-size-observer.service';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {map} from 'rxjs/operators';

export interface LineActionMenuViewModel {
    actions: Action[];
    screenSize: ScreenSize;
    languages: LanguageStrings;
}

@Component({
    selector: 'scrm-line-action-menu',
    templateUrl: 'line-action-menu.component.html'
})
export class LineActionMenuComponent implements OnInit, OnDestroy {

    @Input() klass = '';
    @Input() wrapperClass = '';
    @Input() record: Record;
    @Input() config: ActionDataSource;
    @Input() activeLineAction: ActiveLineAction;
    @Input() limitConfigKey = 'listview_line_actions_limits';
    configState = new BehaviorSubject<ButtonGroupInterface>({buttons: []});
    config$ = this.configState.asObservable();
    actions: Action[];
    isActive: boolean = false;

    isClickedOutside = signal<boolean>(false)

    vm$: Observable<LineActionMenuViewModel>;

    protected buttonClass = 'line-action-item line-action';
    protected buttonGroupClass = 'float-right';

    protected subs: Subscription[] = [];
    protected screen: ScreenSize = ScreenSize.Medium;
    protected defaultBreakpoint = 3;
    protected breakpoint: number;

    @HostListener('document:click', ['$event.target'])
    onClickOutside(target) {
        if (!this.el.nativeElement.contains(target)) {
            this.isActive = false;
            this.isClickedOutside.set(true)
        }
    }

    constructor(
        protected languageStore: LanguageStore,
        protected languages: LanguageStore,
        protected screenSize: ScreenSizeObserverService,
        protected systemConfigStore: SystemConfigStore,
        private el: ElementRef
    ) {
    }

    ngOnInit(): void {
        this.subs.push(this.config.getActions({record: this.record}).pipe(
            combineLatestWith(
                this.screenSize.screenSize$,
                this.languages.vm$
            ),
            map(([actions, screenSize, languages]) => {
                if (screenSize) {
                    this.screen = screenSize;
                }
                this.configState.next(this.getButtonGroupConfig(actions));

                this.actions = actions;
            })
        ).subscribe());

        this.subs.push(this.activeLineAction.activeAction$.subscribe(
            (activeAction: string) => {
                if (this.record?.id === activeAction) {
                    this.isActive = true;
                } else {
                    this.isActive = false;
                }
            }
        ));
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.isActive = false;
        this.activeLineAction.resetActiveAction();
    }

    getButtonGroupConfig(actions: Action[]): ButtonGroupInterface {

        const expanded = [];
        const collapsed = [];

        actions.forEach((action: Action) => {
            const button = this.buildButton(action);

            if (action.params && action.params.expanded) {
                expanded.push(button);
                return;
            }

            collapsed.push(button);
        });

        let breakpoint = actions.length;

        const buttons = expanded.concat(collapsed);

        return {
            buttonKlass: [this.buttonClass],
            dropdownLabel: this.languages.getAppString('LBL_ACTIONS') || '',
            breakpoint,
            dropdownOptions: {
                placement: ['bottom-right'],
                wrapperKlass: [(this.buttonGroupClass)]
            },
            buttons
        } as ButtonGroupInterface;
    }

    getBreakpoint(): number {
        const breakpointMap = this.systemConfigStore.getConfigValue(this.limitConfigKey);

        if (this.screen && breakpointMap && breakpointMap[this.screen]) {
            this.breakpoint = breakpointMap[this.screen];
            return this.breakpoint;
        }

        if (this.breakpoint) {
            return this.breakpoint;
        }

        return this.defaultBreakpoint;
    }

    protected buildButton(action: Action): ButtonInterface {
        const button = {
            titleKey: action.labelKey || '',
            klass: this.buttonClass,
            icon: action.icon || '',
            onClick: (): void => {
                this.config.runAction(action, {
                    module: (this.record && this.record.module) || '',
                    record: this.record
                } as ActionContext);
            }
        } as ButtonInterface;

        if (action.icon) {
            button.icon = action.icon;
        }
        const debounceClick = action?.params?.debounceClick ?? null;

        button.debounceClick = true;

        if (isFalse(debounceClick)) {
            button.debounceClick = false;
        }

        if (action.status) {
            Button.appendClasses(button, [action.status]);
        }

        return button;
    }

    toggleExpand(recordId: string) {
        const activeId = this.activeLineAction.getActiveAction();
        if (activeId === recordId && !this.isClickedOutside()) {
            this.activeLineAction.resetActiveAction();
        } else {
            this.activeLineAction.setActiveAction(recordId);
            this.isActive = true;
            this.isClickedOutside.set(false)
        }
    }
}
