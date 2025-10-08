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

import {
    AfterViewInit,
    Component, computed,
    ElementRef,
    HostListener,
    Input,
    OnDestroy,
    OnInit, Signal,
    signal,
    ViewChild,
    WritableSignal
} from '@angular/core';
import {Action, ActionContext, ActionDataSource} from '../../common/actions/action.model';
import {Button, ButtonInterface} from '../../common/components/button/button.model';
import {ButtonGroupInterface} from '../../common/components/button/button-group.model';
import {isFalse} from '../../common/utils/value-utils';
import {BehaviorSubject, Observable, Subscription} from 'rxjs';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {
    ScreenSize,
    ScreenSizeObserverService
} from '../../services/ui/screen-size-observer/screen-size-observer.service';
import {LanguageStore, LanguageStrings} from '../../store/language/language.store';
import {floor} from "mathjs";

export interface ActionGroupMenuViewModel {
    actions: Action[];
    screenSize: ScreenSize;
    languages: LanguageStrings;
}

@Component({
    selector: 'scrm-action-group-menu',
    templateUrl: './action-group-menu.component.html',
})
export class ActionGroupMenuComponent implements OnInit, AfterViewInit, OnDestroy {

    @Input() klass = '';
    @Input() buttonClass = 'btn btn-sm';
    @Input() buttonGroupClass = '';
    @Input() dropdownLabelKey = 'LBL_ACTIONS';
    @Input() dropdownIcon = '';
    @Input() actionContext: ActionContext;
    @Input() config: ActionDataSource;
    @Input() actionLimitConfig: string = 'recordview_actions_limits';
    @Input() screenSizeMeasures: string = 'default';
    @Input() buttonGroupDropdownClass = 'dropdown-button-secondary';
    @Input() dynamicBreakpoint = false;
    @Input() dynamicBreakpointButtonMax = 100;
    @Input() pushActiveToExpanded = false;
    @Input() isRunning: Signal<boolean> = signal(false);

    @ViewChild('container') containerElement: ElementRef;
    protected screenSize$: Observable<ScreenSize>;
    protected screenSizeState: BehaviorSubject<ScreenSize>;

    @HostListener('window:resize', ['$event'])
    onResize(): void {
        if (this.dynamicBreakpoint && this.actions().length) {
            this.configState.next(this.getButtonGroupConfig(this.actions()));
        }
    }

    configState = new BehaviorSubject<ButtonGroupInterface>({buttons: []});
    config$ = this.configState.asObservable();

    inlineConfirmationEnabled: WritableSignal<boolean> = signal(false);
    confirmationLabel = '';
    confirmationDynamicLabel = '';
    inlineCancelButton: ButtonInterface = null;
    inlineConfirmButton: ButtonInterface = null;
    loading: WritableSignal<boolean> = signal(false);

    protected subs: Subscription[] = [];
    protected screen: ScreenSize = ScreenSize.Medium;
    protected defaultBreakpoint = 4;
    protected breakpoint: number;
    protected maxButtonWidth?: string;
    protected actions: WritableSignal<Action[]> = signal([]);

    constructor(
        protected languages: LanguageStore,
        protected screenSize: ScreenSizeObserverService,
        protected systemConfigStore: SystemConfigStore,
    ) {
    }

    ngOnInit(): void {
        this.subs = [];
        this.screenSize$ = this.screenSize.screenSize$;
        this.screenSizeState = this.screenSize.screenSize;
        if (this.screenSizeMeasures === 'bootstrap') {
            this.screenSize$ = this.screenSize.bootstrapScreenSize$;
            this.screenSizeState = this.screenSize.bootstrapScreenSize;
        }

        this.subs.push(this.config?.getActions().subscribe(actions => {
            this.actions.set(actions);
            this.screen = this.screenSizeState.value;
            this.configState.next(this.getButtonGroupConfig(actions));
        }));

        const limitConfig = this.systemConfigStore.getConfigValue(this.actionLimitConfig) ?? {} as any;
        this.maxButtonWidth = limitConfig?.dynamicBreakpoint?.buttonMax ?? null;
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub && sub.unsubscribe());
        this.subs = [];
    }

    ngAfterViewInit(): void {

        this.subs.push(this.screenSize$.subscribe(screenSize => {
            if (screenSize) {
                this.screen = screenSize;
            }

            this.configState.next(this.getButtonGroupConfig(this.actions()));
        }));
    }

    isXSmallScreen(): boolean {
        return this.screen === ScreenSize.XSmall;
    }

    getButtonGroupConfig(actions: Action[]): ButtonGroupInterface {

        const expanded = [];
        const collapsed = [];

        actions.forEach((action: Action) => {
            const button = this.buildButton(action);
            button.active = this.config.isActive(action) ?? false;

            if (action.params && action.params.collapsedMobile && this.isXSmallScreen()) {
                collapsed.push(button);
                return;
            }

            if (action.params && action.params.expanded) {
                expanded.push(button);
                return;
            }

            collapsed.push(button);
        });

        const collapseButtons = this?.config?.collapseButtons ?? true;

        let breakpoint = actions.length;
        if (collapseButtons === true) {
            breakpoint = this.getBreakpoint(expanded.length, collapsed.length);
            if (expanded.length < breakpoint) {
                breakpoint = expanded.length;
            }
        }

        const buttons = expanded.concat(collapsed);

        return {
            buttonKlass: [this.buttonClass],
            dropdownLabel: this.languages.getAppString(this.dropdownLabelKey) ?? '',
            breakpoint,
            dropdownOptions: {
                placement: ['bottom-right'],
                wrapperKlass: [(this.buttonGroupDropdownClass)],
                icon: this.dropdownIcon
            },
            buttons,
            pushActiveToExpanded: this.pushActiveToExpanded
        } as ButtonGroupInterface;
    }

    getBreakpoint(totalExpandedActions: number, totalCollapsed: number): number {

        let dynamicBreakpoint = this.dynamicBreakpoint;
        const limitConfig = this.systemConfigStore.getConfigValue(this.actionLimitConfig);
        if (limitConfig && limitConfig?.type === 'dynamicBreakpoint') {
            dynamicBreakpoint = true;
        } else if (limitConfig && limitConfig?.type === 'fixedLimits') {
            dynamicBreakpoint = false;
        }

        if (dynamicBreakpoint) {
            return this.calculateDynamicBreakpoint(limitConfig, totalCollapsed, totalExpandedActions);
        }

        let breakpointMap = {} as any;
        if (limitConfig?.fixedLimits) {
            breakpointMap = limitConfig.fixedLimits;
        } else if (!limitConfig?.type) {
            breakpointMap = limitConfig
        }

        if (this.screen && breakpointMap && breakpointMap[this.screen]) {
            this.breakpoint = breakpointMap[this.screen];
            return this.breakpoint;
        }

        if (this.breakpoint) {
            return this.breakpoint;
        }

        return this.defaultBreakpoint;
    }

    protected calculateDynamicBreakpoint(limitConfig, totalCollapsed: number, totalExpandedActions: number): number {
        let buttonMax = this.dynamicBreakpointButtonMax;

        if (limitConfig?.dynamicBreakpoint?.buttonMax) {
            buttonMax = limitConfig?.dynamicBreakpoint?.buttonMax;
        }

        let dropdownWidth = 80;
        if (limitConfig?.dynamicBreakpoint?.dropdownMax) {
            dropdownWidth = limitConfig?.dynamicBreakpoint?.dropdownMax;
        }

        const containerWidth = this?.containerElement?.nativeElement?.parentElement?.parentElement?.offsetWidth;

        if (!containerWidth || containerWidth < buttonMax) {
            return 1;
        }

        if (!this.dropdownLabelKey) {
            dropdownWidth = 20;
        }

        const fitting = floor(containerWidth / buttonMax);
        const fittingWithDropdown = floor((containerWidth - dropdownWidth) / buttonMax);

        if (totalCollapsed) {
            return fittingWithDropdown;
        }

        if (totalExpandedActions <= fitting) {
            return fitting;
        }

        return fittingWithDropdown;
    }

    protected buildButton(action: Action): ButtonInterface {


        const button = {
            label: action.label || '',
            labelModule: this?.actionContext?.module ?? '',
            labelKey: action.labelKey || '',
            klass: this.buttonClass,
            titleKey: action.titleKey || '',
            onClick: (): void => {

                const inlineConfirmation = action?.params?.inlineConfirmation ?? false;
                if (inlineConfirmation) {
                    this.triggerTemporaryLoading();
                    const callback = (): void => {
                        this.config.runAction(action, this.actionContext);
                    }
                    this.initInlineConfirmation(action, callback);

                    return;
                }

                this.config.runAction(action, this.actionContext);
            }
        } as ButtonInterface;

        if (!button.label) {
            button.labelKey = action.labelKey ?? '';
        }

        if (this.maxButtonWidth) {
            button.maxWidth = this.maxButtonWidth;
        }

        const debounceClick = action?.params?.debounceClick ?? null;

        button.debounceClick = true;

        if (isFalse(debounceClick)) {
            button.debounceClick = false;
        }

        if (action.params?.disableOnRun && action.isRunning) {
            button.isRunning = computed(() => {
                return action.isRunning();
            });
        }

        if (action.icon) {
            button.icon = action.icon;
        }

        if (action.status) {
            Button.appendClasses(button, [action.status]);
        }

        if (action.klass) {
            Button.appendClasses(button, action.klass);
        }

        return button;
    }

    protected triggerTemporaryLoading() {
        this.loading.set(true);
        const delay = parseInt(this.systemConfigStore.getUi('inline_confirmation_loading_delay')) ?? 200;
        setTimeout(() => {
            this.loading.set(false);
        }, delay);
    }

    protected initInlineConfirmation(action: Action, callback: () => void): void {
        const cancelConfig = action?.params?.inlineConfirmationButtons?.cancel ?? {};
        const confirmConfig = action?.params?.inlineConfirmationButtons?.confirm ?? {};
        this.confirmationLabel = action?.params?.confirmationLabel ?? '';
        this.confirmationDynamicLabel = action?.params?.confirmationDynamicLabel ?? '';

        this.inlineCancelButton = this.buildInlineCancelButton(cancelConfig)
        this.inlineConfirmButton = this.buildInlineConfirmButton(confirmConfig, callback)
        this.inlineConfirmationEnabled.set(true);
    }

    protected buildInlineCancelButton(config: ButtonInterface): ButtonInterface {
        const defaults = {
            labelKey: 'LBL_NO',
            klass: 'btn btn-sm p-0 m-0 btn-link border-0 line-height-initial',
            debounceClick: true,
        } as ButtonInterface;
        const button = {...defaults, ...(config ?? {})};

        button.onClick = (): void => {
            this.triggerTemporaryLoading();
            this.resetInlineConfirmation();
        }

        return button;
    }

    protected buildInlineConfirmButton(config: ButtonInterface, callback: Function): ButtonInterface {
        const defaults = {
            labelKey: 'LBL_YES',
            klass: 'btn btn-sm p-0 m-0 btn-link border-0 line-height-initial',
            debounceClick: true,
        } as ButtonInterface;
        const button = {...defaults, ...(config ?? {})};

        button.onClick = (): void => {
            this.triggerTemporaryLoading();
            callback();
            this.resetInlineConfirmation();
        }

        return button;
    }

    protected resetInlineConfirmation(): void {
        this.inlineConfirmationEnabled.set(false);
        this.confirmationDynamicLabel = '';
        this.confirmationLabel = '';
        this.inlineConfirmButton = null;
        this.inlineCancelButton = null;
    }

}
