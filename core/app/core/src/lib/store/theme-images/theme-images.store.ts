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
import {BehaviorSubject, Observable, of} from 'rxjs';
import {distinctUntilChanged, map, shareReplay, tap} from 'rxjs/operators';
import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {AppStateStore} from '../app-state/app-state.store';
import {StateStore} from '../state';
import {deepClone, emptyObject} from '../../common/utils/object-utils';
import {SvgIconRegistryService} from 'angular-svg-icon';

export interface ThemeImage {
    path: string;
    name: string;
    type: string;
    content?: string;
}

export interface ThemeImages {
    theme: string;
    images: ThemeImageMap;
}

export interface ThemeImageMap {
    [key: string]: ThemeImage;
}

const initialState: ThemeImages = {
    theme: null,
    images: {}
};

let internalState: ThemeImages = deepClone(initialState);

let cachedTheme = null;
let cache$: Observable<any> = null;

@Injectable({
    providedIn: 'root',
})
export class ThemeImagesStore implements StateStore {

    /**
     * Public long-lived observable streams
     */
    images$: Observable<ThemeImageMap>;

    protected store = new BehaviorSubject<ThemeImages>(internalState);
    protected state$ = this.store.asObservable();
    protected resourceName = 'themeImages';
    protected frontendName = 'theme-images';
    protected fieldsMetadata = {
        fields: [
            'id',
            '_id',
            'items'
        ]
    };

    constructor(
        protected recordGQL: EntityGQL,
        protected appStateStore: AppStateStore,
        protected iconRegistry: SvgIconRegistryService
    ) {
        this.images$ = this.state$.pipe(map(state => state.images), distinctUntilChanged());
    }


    /**
     * Public Api
     */

    /**
     * Clear state
     */
    public clear(): void {
        cachedTheme = null;
        cache$ = null;
        this.updateState(deepClone(initialState));
    }

    public clearAuthBased(): void {
    }

    /**
     * Change the current theme
     *
     * @param {string} theme to set
     */
    public changeTheme(theme: string): void {

        this.appStateStore.updateLoading('change-theme', true);

        this.load(theme).pipe(
            tap(() => this.appStateStore.updateLoading('change-theme', false))
        ).subscribe();
    }

    /**
     * Returns the currently active image theme
     *
     * @returns {string} the theme
     */
    public getTheme(): string {
        return internalState.theme;
    }


    /**
     * Initial ThemeImages load if not cached and update state.
     * Returns observable to be used in resolver if needed
     *
     * @param {string} theme to load
     * @returns {object} Observable<any>
     */
    public load(theme: string): Observable<any> {

        return this.getThemeImages(theme).pipe(
            tap(images => {
                this.updateState({...internalState, images, theme});
            })
        );
    }

    /**
     * Check if loaded
     */
    public isCached(): boolean {
        return cache$ !== null;
    }

    /**
     * Set pre-loaded theme images and cache
     */
    public set(theme: string, images: ThemeImageMap): void {

        cachedTheme = theme;
        this.registerSvgs(images);

        cache$ = of(images).pipe(shareReplay(1));
        this.updateState({...internalState, images});
    }


    /**
     * Internal API
     */

    /**
     * Update the state
     *
     * @param {object} state to set
     */
    protected updateState(state: ThemeImages): void {
        this.store.next(internalState = state);
    }

    /**
     * Get theme images cached Observable or call the backend
     *
     * @param {string} theme to retrieve
     * @returns {object} Observable<any>
     */
    protected getThemeImages(theme: string): Observable<any> {

        if (cachedTheme !== theme || cache$ === null) {
            cachedTheme = theme;
            cache$ = this.fetch(theme).pipe(
                shareReplay(1)
            );
        }

        return cache$;
    }

    protected registerSvgs(images: ThemeImageMap) {
        Object.keys(images).forEach(key => {
            const image = images[key];
            const content = image['content'] ?? '';
            const name = image['name'] ?? '';

            if (content === '' || name === '') {
                return;
            }

            this.iconRegistry.addSvg(name, content);
        });
    }

    /**
     * Fetch the theme images from the backend
     *
     * @param {string} theme to load
     * @returns {object} Observable<any>
     */
    protected fetch(theme: string): Observable<any> {

        return this.recordGQL
            .fetch(this.resourceName, `/api/${this.frontendName}/${theme}`, this.fieldsMetadata)
            .pipe(
                map(({data}) => {
                    let images = {};

                    if (data && data.themeImages) {
                        images = data.themeImages.items;
                    }

                    if (!emptyObject(images)) {
                        const parsedImages = {};
                        this.registerSvgs(images);

                        Object.keys(images).forEach(key => {
                            const {content, ...image} = images[key] ?? {};
                            parsedImages[key] = image;
                        });

                        return parsedImages;
                    }

                    return images;
                })
            );
    }
}
