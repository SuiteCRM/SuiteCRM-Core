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
import {AppStateStore} from './app-state/app-state.store';
import {LanguageStore} from './language/language.store';
import {NavigationStore} from './navigation/navigation.store';
import {SystemConfigStore} from './system-config/system-config.store';
import {ThemeImagesStore} from './theme-images/theme-images.store';
import {UserPreferenceStore} from './user-preference/user-preference.store';
import {StateStore, StateStoreMap, StateStoreMapEntry} from './state';
import {MetadataStore} from './metadata/metadata.store.service';
import {AppMetadataStore} from './app-metadata/app-metadata.store.service';

@Injectable({
    providedIn: 'root',
})
export class StateManager {
    protected stateStores: StateStoreMap = {};

    constructor(
        protected appStore: AppStateStore,
        protected languageStore: LanguageStore,
        protected metadataStore: MetadataStore,
        protected navigationStore: NavigationStore,
        protected systemConfigStore: SystemConfigStore,
        protected themeImagesStore: ThemeImagesStore,
        protected userPreferenceStore: UserPreferenceStore,
        protected appMetadataStore: AppMetadataStore,
    ) {
        this.stateStores.appStore = this.buildMapEntry(appStore, false);
        this.stateStores.navigationStore = this.buildMapEntry(navigationStore, true);
        this.stateStores.languageStore = this.buildMapEntry(languageStore, true);
        this.stateStores.metadataStore = this.buildMapEntry(metadataStore, true);
        this.stateStores.systemConfigStore = this.buildMapEntry(systemConfigStore, false);
        this.stateStores.themeImagesStore = this.buildMapEntry(themeImagesStore, false);
        this.stateStores.userPreferenceStore = this.buildMapEntry(userPreferenceStore, true);
        this.stateStores.appMetadataStore = this.buildMapEntry(appMetadataStore, true);
    }

    /**
     * Public Api
     */

    /**
     * Clear all state
     */
    public clear(): void {
        Object.keys(this.stateStores).forEach((key) => {
            this.stateStores[key].store.clear();
        });
    }

    /**
     * Clear all state
     */
    public clearAuthBased(): void {
        Object.keys(this.stateStores).forEach((key) => {
            if (this.stateStores[key].authBased) {
                this.stateStores[key].store.clearAuthBased();
            }
        });
    }

    public clearBackendCacheable(): void {
        this.clearAuthBased();
        this.systemConfigStore.clear();
    }

    /**
     * Internal api
     */

    /**
     * Build Map entry
     *
     * @param {{}} store to use
     * @param {boolean} authBased flag
     * @returns {{}} StateStoreMapEntry
     */
    protected buildMapEntry(store: StateStore, authBased: boolean): StateStoreMapEntry {
        return {
            store,
            authBased
        };
    }
}
