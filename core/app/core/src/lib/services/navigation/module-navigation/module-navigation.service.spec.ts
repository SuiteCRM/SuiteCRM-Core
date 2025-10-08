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

import {ModuleNavigation} from './module-navigation.service';
import {mockModuleNavigation} from './module-navigation.service.spec.mock';
import {Navigation} from '../../../store/navigation/navigation.store';

describe('Module Navigation Service', () => {
    const service: ModuleNavigation = mockModuleNavigation;

    beforeEach(() => {
    });

    it('#getModuleRoute', () => {

        const route = service.getModuleRoute({
            name: 'accounts',
            defaultRoute: './#/accounts',
            labelKey: 'Accounts',
            path: null,
            menu: [],
        });

        expect(route.url).toEqual(null);
        expect(route.route).toEqual('/accounts');
        expect(route.params).toEqual(null);
    });

    it('#getModuleLabel', () => {

        const label = service.getModuleLabel(
            {
                name: 'accounts',
                defaultRoute: './#/accounts',
                labelKey: 'Accounts',
                path: null,
                menu: [],
            }, {
                moduleList: {
                    Accounts: 'Accounts Label'
                }
            });

        expect(label).toEqual('Accounts Label');
    });

    it('#getModuleInfo', () => {

        const moduleInfo = service.getModuleInfo(
            'accounts',
            {
                tabs: [],
                groupedTabs: [],
                userActionMenu: [],
                maxTabs: 0,
                modules: {
                    accounts: {
                        name: 'accounts',
                        defaultRoute: './#/accounts',
                        labelKey: 'Accounts',
                        path: null,
                        menu: [],
                    }
                }
            } as Navigation
        );

        expect(moduleInfo).toBeTruthy();
        expect(moduleInfo.name).toEqual('accounts');
        expect(moduleInfo.defaultRoute).toEqual('./#/accounts');
        expect(moduleInfo.labelKey).toEqual('Accounts');
        expect(moduleInfo.path).toEqual(null);
        expect(moduleInfo.menu).toEqual([]);
    });

    it('#getActionRoute', () => {

        const route = service.getActionRoute({
            name: 'list',
            url: './#/accounts/list',
            labelKey: 'LBL_LIST',
            params: null,
            icon: ''
        });

        expect(route.url).toEqual(null);
        expect(route.route).toEqual('/accounts/list');
        expect(route.params).toEqual(null);
    });

    it('#getActionLabel', () => {

        const label = service.getActionLabel(
            'accounts',
            {
                name: 'list',
                url: './#/accounts/list',
                labelKey: 'LBL_LIST',
                params: null,
                icon: ''
            },
            {
                modStrings: {
                    LBL_LIST: 'List View'
                },
                appStrings: {
                    LBL_LIST: 'List View'
                },
                appListStrings: {},
                languageKey: 'en_us'
            });

        expect(label).toEqual('List View');
    });

    it('#navigate', () => {

        const promise = service.navigate({
            name: 'list',
            url: './#/accounts/list',
            labelKey: 'LBL_LIST',
            params: null,
            icon: ''
        });

        expect(promise).toEqual(null);
    });
});

