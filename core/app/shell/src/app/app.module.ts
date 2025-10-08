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

import {APP_INITIALIZER, NgModule, provideExperimentalZonelessChangeDetection} from '@angular/core';
import {BrowserModule} from '@angular/platform-browser';
import {
    HTTP_INTERCEPTORS,
    provideHttpClient,
    withInterceptorsFromDi,
    withXsrfConfiguration
} from '@angular/common/http';

import {Apollo, ApolloModule} from 'apollo-angular';
import {HttpLink} from 'apollo-angular/http';
import {ApolloLink, InMemoryCache} from '@apollo/client/core';
import {FetchPolicy} from '@apollo/client/core/watchQueryOptions';
import {onError} from '@apollo/client/link/error';
import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {
    AppStateStore,
    AuthService,
    BaseRouteService,
    ClassicViewUiModule,
    ColumnChooserModule,
    CreateRecordModule,
    ErrorInterceptor,
    FooterUiModule,
    FullPageSpinnerModule,
    ImageModule,
    InstallViewModule,
    ListContainerModule,
    ListHeaderModule,
    ListModule,
    MessageModalModule,
    MessageUiModule,
    ModuleTitleModule,
    NavbarUiModule,
    RecordListModalModule,
    RecordModalComponent,
    RecordModule,
    SidebarComponent,
    TableModule
} from 'core';

import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {environment} from '../environments/environment';
import {RouteReuseStrategy} from '@angular/router';
import {AppRouteReuseStrategy} from './app-router-reuse-strategy';
import {AppInit} from './app-initializer';
import {GraphQLError} from 'graphql';
import {AngularSvgIconModule} from 'angular-svg-icon';

export const initializeApp = (appInitService: AppInit) => (): Promise<any> => appInitService.init();

@NgModule({
    declarations: [
        AppComponent,
    ],
    bootstrap: [AppComponent], imports: [
        BrowserModule,
        AppRoutingModule,
        FooterUiModule,
        NavbarUiModule,
        MessageUiModule,
        ClassicViewUiModule,
        ListModule,
        RecordModule,
        CreateRecordModule,
        InstallViewModule,
        TableModule,
        ModuleTitleModule,
        ListHeaderModule,
        ListContainerModule,
        ColumnChooserModule,
        AngularSvgIconModule.forRoot(),
        ImageModule,
        BrowserAnimationsModule,
        NgbModule,
        FullPageSpinnerModule,
        MessageModalModule,
        RecordListModalModule,
        RecordModalComponent,
        ApolloModule,
        SidebarComponent
    ],
    providers: [
        {provide: HTTP_INTERCEPTORS, useClass: ErrorInterceptor, multi: true},
        {provide: RouteReuseStrategy, useClass: AppRouteReuseStrategy},
        provideExperimentalZonelessChangeDetection(),
        AppInit,
        {
            provide: APP_INITIALIZER,
            useFactory: initializeApp,
            multi: true,
            deps: [AppInit]
        },
        provideHttpClient(withInterceptorsFromDi(),
            withXsrfConfiguration({
                cookieName: 'XSRF-TOKEN',
                headerName: 'X-XSRF-TOKEN'
            })
        )
    ],
})
export class AppModule {
    constructor(apollo: Apollo, httpLink: HttpLink, protected auth: AuthService, protected appStore: AppStateStore, protected baseRoute: BaseRouteService) {

        const defaultOptions = {
            watchQuery: {
                fetchPolicy: 'no-cache' as FetchPolicy
            },
            query: {
                fetchPolicy: 'no-cache' as FetchPolicy
            },
        };

        let fullPath = environment.graphqlApiUrl;
        fullPath = this.baseRoute.calculateRoute(fullPath);
        const http = httpLink.create({
            uri: fullPath,
            withCredentials: true
        });

        const logoutLink = onError((err) => {
            appStore.removeActiveRequest();
            const networkError = (err.networkError ?? null) as any;
            if (networkError !== null && networkError?.status === 403 && networkError?.error?.detail === 'Invalid CSRF token') {
                auth.handleInvalidSession('LBL_SESSION_EXPIRED');
            }

            if (err.graphQLErrors && err.graphQLErrors.length > 0) {
                err.graphQLErrors.forEach((value: GraphQLError) => {
                    if (this.auth.isUserLoggedIn.value === true && value.message.includes('Access Denied')) {
                        auth.handleInvalidSession('LBL_SESSION_EXPIRED');
                    }
                });
            }
        });

        const middleware = new ApolloLink((operation, forward) => {
            appStore.addActiveRequest();
            return forward(operation);
        });

        const afterware = new ApolloLink((operation, forward) => {
            return forward(operation).map(response => {
                appStore.removeActiveRequest();
                return response;
            });
        });

        apollo.create({
            defaultOptions,
            link: ApolloLink.from([middleware, afterware, logoutLink.concat(http)]),
            cache: new InMemoryCache()
        });
    }
}
