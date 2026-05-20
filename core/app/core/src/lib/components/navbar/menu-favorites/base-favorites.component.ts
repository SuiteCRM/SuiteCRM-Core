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

import {Component, Input, OnChanges, OnDestroy, OnInit, signal, SimpleChanges, WritableSignal} from '@angular/core';
import {Favorite} from '../../../common/record/favorites.model';
import {ModuleNavigation} from '../../../services/navigation/module-navigation/module-navigation.service';
import {ModuleNameMapper} from '../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {SystemConfigStore} from '../../../store/system-config/system-config.store';
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {map} from 'rxjs/operators';
import {Subscription} from 'rxjs';

@Component({template: ''})
export class BaseFavoritesComponent implements OnInit, OnDestroy, OnChanges {
    @Input() module: string;
    maxDisplayed: number = 5;
    records: WritableSignal<Favorite[]> = signal([]);
    protected subs: Subscription[] = [];

    constructor(
        protected navigation: ModuleNavigation,
        protected nameMapper: ModuleNameMapper,
        protected configs: SystemConfigStore,
        protected metadata: MetadataStore
    ) {
    }

    ngOnInit(): void {
        const ui = this.configs.getConfigValue('ui') ?? {};
        this.maxDisplayed = parseInt(ui.navigation_max_module_favorites) ?? 5;
        this.initMetadata$();
    }

    ngOnDestroy(): void {
        this.clear();
    }

    ngOnChanges(changes: SimpleChanges): void {
        const moduleChanges = changes?.module ?? null;

        if (moduleChanges === null) {
            return;
        }

        const previousModule = changes?.module?.previousValue ?? '';
        const currentModule = changes?.module?.currentValue ?? '';
        if (previousModule !== currentModule) {
            this.clear();
            this.initMetadata$();
        }
    }

    /**
     * Build route from recently viewed item
     * @param item
     */
    buildRoute(item: Favorite): string {
        const legacyName = item.attributes.parent_type ?? '';
        const module = this.nameMapper.toFrontend(legacyName) ?? '';
        const id = item.attributes.parent_id ?? '';
        return this.navigation.getRecordRouterLink(module, id);
    }

    /**
     * Init metadata subscription
     * @protected
     */
    protected initMetadata$(): void {
        const moduleMeta$ = this.metadata.allModuleMetadata$.pipe(map(value => value[this.module] ?? null));

        this.subs.push(moduleMeta$.subscribe(meta => {
            this.records.set(meta?.favorites ?? null);
        }));
    }

    /**
     * Clear subscription and data
     * @protected
     */
    protected clear() {
        this.records.set(null);
        this.subs.forEach(sub => sub.unsubscribe());
    }
}
