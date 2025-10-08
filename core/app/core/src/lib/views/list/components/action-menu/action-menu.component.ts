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

import {Component, OnInit} from '@angular/core';
import {ButtonInterface} from '../../../../common/components/button/button.model';
import {ButtonGroupInterface} from '../../../../common/components/button/button-group.model';
import {BehaviorSubject} from 'rxjs';
import {map, take} from 'rxjs/operators';
import {ListViewStore} from '../../store/list-view/list-view.store';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {ModuleAction} from '../../../../store/navigation/navigation.store';
import {
    ScreenSize,
    ScreenSizeObserverService
} from '../../../../services/ui/screen-size-observer/screen-size-observer.service';
import {ModuleNavigation} from '../../../../services/navigation/module-navigation/module-navigation.service';
import {AsyncActionInput, AsyncActionService} from '../../../../services/process/processes/async-action/async-action';

@Component({
    selector: 'scrm-action-menu',
    templateUrl: 'action-menu.component.html',
})
export class ActionMenuComponent implements OnInit {

    configState = new BehaviorSubject<ButtonGroupInterface>({buttons: []});
    config$ = this.configState.asObservable();

    vm$ = this.screenSize.screenSize$.pipe(
        map((screenSize) => {
            if (screenSize) {
                this.screen = screenSize;
            }
            this.configState.next(this.getButtonGroupConfig());
            return {screenSize};
        })
    );

    protected screen: ScreenSize = ScreenSize.Medium;
    protected defaultBreakpoint = 3;
    protected breakpoint: number;


    constructor(
        protected listStore: ListViewStore,
        protected actionHandler: ModuleNavigation,
        protected screenSize: ScreenSizeObserverService,
        protected systemConfigs: SystemConfigStore,
        protected asyncActionService: AsyncActionService
    ) {
    }

    ngOnInit(): void {
        this.configState.next(this.getButtonGroupConfig());
    }

    getButtonGroupConfig(): ButtonGroupInterface {
        const actions = this.actions;
        const config = {
            buttonKlass: ['action-button'],
            dropdownLabel: this.listStore.appStrings.LBL_MORE || '',
            buttons: [],
            dropdownOptions: {
                placement: ['bottom-right']
            },
            breakpoint: this.getBreakpoint()
        } as ButtonGroupInterface;

        actions.forEach(action => {
            const buttonConfig = this.getButtonConfig(action);
            if (buttonConfig && buttonConfig.klass) {
                config.buttons.push(buttonConfig);
            }
        });
        return config;
    }

    getBreakpoint(): number {

        const breakpointMap = this.systemConfigs && this.systemConfigs.getConfigValue('listview_actions_limits');

        if (this.screen && breakpointMap && breakpointMap[this.screen]) {
            this.breakpoint = breakpointMap[this.screen];
            return this.breakpoint;
        }

        if (this.breakpoint) {
            return this.breakpoint;
        }

        return this.defaultBreakpoint;
    }

    get actions(): ModuleAction[] {
        if (!this.listStore.vm.appData.module || !this.listStore.vm.appData.module.menu) {
            return [];
        }

        return this.listStore.vm.appData.module.menu.filter(action => !(action.name === 'List' || action.name === 'View'));
    }

    public getButtonConfig(action: ModuleAction): ButtonInterface {

        if (!this.listStore.vm.appData.appState.module) {
            return {};
        }

        if (!this.listStore.vm.appData.language) {
            return {};
        }

        const module = this.listStore.vm.appData.appState.module;
        const language = this.listStore.vm.appData.language;
        let labelKey = '';
        if (action.actionLabelKey) {
            labelKey = action.actionLabelKey;
        }

        return {
            klass: 'action-button',
            label: this.actionHandler.getActionLabel(module, action, language, labelKey),
            onClick: (): void => {
                if(action?.process) {
                    this.handleProcess(module, action)
                    return ;
                }
                this.actionHandler.navigate(action).then();
            }
        };
    }

    protected handleProcess(moduleName: string, action: ModuleAction) {

        if(!action.process) {
            return;
        }

        const processType = action.process;

        const options = {
            action: processType,
            module: moduleName,
            params: action?.processParams ?? [],
        } as AsyncActionInput;

        this.asyncActionService.run(processType, options).pipe(take(1)).subscribe();
    }
}
