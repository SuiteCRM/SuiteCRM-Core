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

import {Injectable, Injector} from '@angular/core';
import {Route, Router} from '@angular/router';
import {
    AppStateStore,
    AuthGuard,
    BaseMetadataResolver,
    BaseModuleResolver,
    BaseRecordResolver,
    ClassicViewResolver,
    ClassicViewUiComponent,
    CreateRecordComponent,
    ExtensionLoader,
    InstallAuthGuard,
    InstallViewComponent,
    isFalse,
    ListComponent,
    LoginAuthGuard,
    LoginUiComponent,
    RecordComponent,
    SystemConfigStore,
    SystemNameService,
    BaseRouteService,
    LogoutComponent,
    TwoFactorAuthGuard,
    AdminPanelComponent,
    TwoFactorComponent
} from 'core';
import {take} from 'rxjs/operators';

@Injectable()
export class AppInit {

    constructor(
        private router: Router,
        protected systemConfigStore: SystemConfigStore,
        protected appStore: AppStateStore,
        protected injector: Injector,
        protected extensionLoader: ExtensionLoader,
        protected systemNameService: SystemNameService,
        protected baseRoute: BaseRouteService
    ) {
    }

    init(): Promise<void> {

        // eslint-disable-next-line compat/compat
        return new Promise<void>((resolve) => {
            this.systemConfigStore.load().subscribe(() => {
                this.appStore.init();
                const systemName = this.systemConfigStore.getConfigValue('system_name');
                this.systemNameService.setSystemName(systemName);
                this.extensionLoader.load(this.injector).pipe(take(1)).subscribe(() => {
                    const routes = this.router.config;
                    const configRoutes = this.systemConfigStore.getConfigValue('module_routing');

                    let loggedOutConfig = {
                        path: 'logged-out',
                        component: LogoutComponent,
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            metadata: BaseMetadataResolver
                        },
                        data: {
                            reuseRoute: false,
                            load: {
                                navigation: false,
                                preferences: false,
                                languageStrings: ['appStrings']
                            }
                        }
                    } as Route;

                    if (this.baseRoute.isLoggedOutPath()) {
                        loggedOutConfig.path = '';
                        routes.push(loggedOutConfig);
                        routes.push({
                            path: '**',
                            redirectTo: ''
                        });
                        this.router.resetConfig(routes);
                        resolve();
                        return;
                    }

                    routes.push({
                        path: 'Login',
                        component: LoginUiComponent,
                        canActivate: [LoginAuthGuard],
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            metadata: BaseMetadataResolver
                        },
                        data: {
                            reuseRoute: false,
                            load: {
                                navigation: false,
                                preferences: false,
                                languageStrings: ['appStrings']
                            }
                        }
                    });

                    routes.push({
                        path: 'install',
                        component: InstallViewComponent,
                        canActivate: [InstallAuthGuard],
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            metadata: BaseMetadataResolver
                        },
                        data: {
                            reuseRoute: false,
                            checkSession: false,
                            load: {
                                navigation: false,
                                preferences: false,
                                languageStrings: ['appStrings']
                            }
                        }
                    });

                    routes.push({
                        path: 'administration',
                        component: AdminPanelComponent,
                        canActivate: [AuthGuard],
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            metadata: BaseModuleResolver
                        },
                        data: {
                            reuseRoute: false,
                            checkSession: true,
                            module: 'administration'
                        },
                        children: [
                            {
                                path: 'index',
                                redirectTo: ''
                            }
                        ]
                    });

                    routes.push({
                        path: 'users/2fa-config',
                        component: TwoFactorComponent,
                        canActivate: [TwoFactorAuthGuard],
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            metadata: BaseMetadataResolver
                        },
                        data: {
                            reuseRoute: false,
                            checkSession: true,
                            load: {
                                navigation: false,
                                preferences: false,
                                languageStrings: ['appStrings']
                            }
                        }
                    });
                    routes.push(loggedOutConfig);

                    Object.keys(configRoutes).forEach(routeName => {
                        if (configRoutes[routeName].index) {
                            routes.push({
                                path: routeName,
                                component: ListComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    metadata: BaseModuleResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName
                                }
                            });
                            routes.push({
                                path: routeName + '/index',
                                component: ListComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    metadata: BaseModuleResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName
                                }
                            });
                        }

                        if (configRoutes[routeName].list) {
                            routes.push({
                                path: routeName + '/list',
                                component: ListComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    metadata: BaseModuleResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName
                                }
                            });
                        }

                        if (!isFalse(configRoutes[routeName].create) && !isFalse(configRoutes[routeName].record)) {
                            routes.push({
                                path: routeName + '/create',
                                component: CreateRecordComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    view: BaseModuleResolver,
                                    metadata: BaseRecordResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName,
                                    mode: 'create'
                                }
                            });

                            routes.push({
                                path: routeName + '/edit',
                                component: CreateRecordComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    view: BaseModuleResolver,
                                    metadata: BaseRecordResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName,
                                    mode: 'create'
                                }
                            });

                            if (!isFalse(configRoutes[routeName].duplicate)) {
                                routes.push({
                                    path: routeName + '/duplicate/:record',
                                    component: CreateRecordComponent,
                                    canActivate: [AuthGuard],
                                    runGuardsAndResolvers: 'always',
                                    resolve: {
                                        view: BaseModuleResolver,
                                        metadata: BaseRecordResolver
                                    },
                                    data: {
                                        reuseRoute: false,
                                        checkSession: true,
                                        module: routeName,
                                        mode: 'create',
                                        duplicate: true
                                    }
                                });
                            }

                            if (!isFalse(configRoutes[routeName].convert)) {
                                routes.push({
                                    path: routeName + '/convert/:record',
                                    component: CreateRecordComponent,
                                    canActivate: [AuthGuard],
                                    runGuardsAndResolvers: 'always',
                                    resolve: {
                                        view: BaseModuleResolver,
                                        metadata: BaseRecordResolver
                                    },
                                    data: {
                                        reuseRoute: false,
                                        checkSession: true,
                                        module: routeName,
                                        mode: 'create',
                                        convert: true
                                    }
                                });
                            }
                        }

                        if (configRoutes[routeName].record) {
                            routes.push({
                                path: routeName + '/record/:record',
                                component: RecordComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    view: BaseModuleResolver,
                                    metadata: BaseRecordResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName
                                }
                            });
                            routes.push({
                                path: routeName + '/edit/:record',
                                component: RecordComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    view: BaseModuleResolver,
                                    metadata: BaseRecordResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName,
                                    mode: 'edit'
                                }
                            });
                            routes.push({
                                path: routeName + '/detail/:record',
                                component: RecordComponent,
                                canActivate: [AuthGuard],
                                runGuardsAndResolvers: 'always',
                                resolve: {
                                    view: BaseModuleResolver,
                                    metadata: BaseRecordResolver
                                },
                                data: {
                                    reuseRoute: false,
                                    checkSession: true,
                                    module: routeName
                                }
                            });
                        }
                    });

                    routes.push({
                        path: ':module',
                        component: ClassicViewUiComponent,
                        canActivate: [AuthGuard],
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            legacyUrl: ClassicViewResolver,
                        },
                        data: {
                            reuseRoute: false,
                            checkSession: true
                        }
                    });

                    routes.push({
                        path: ':module/:action',
                        component: ClassicViewUiComponent,
                        canActivate: [AuthGuard],
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            legacyUrl: ClassicViewResolver,
                        },
                        data: {
                            reuseRoute: false,
                            checkSession: true
                        }
                    });

                    routes.push({
                        path: ':module/:action/:record',
                        component: ClassicViewUiComponent,
                        canActivate: [AuthGuard],
                        runGuardsAndResolvers: 'always',
                        resolve: {
                            legacyUrl: ClassicViewResolver,
                        },
                        data: {
                            reuseRoute: false,
                            checkSession: true
                        }
                    });

                    routes.push({
                        path: '**',
                        redirectTo: 'Login'
                    });

                    routes.push({
                        path: '',
                        component: ClassicViewUiComponent
                    });

                    this.router.resetConfig(routes);
                    resolve();
                });

            });
        });
    }
}
