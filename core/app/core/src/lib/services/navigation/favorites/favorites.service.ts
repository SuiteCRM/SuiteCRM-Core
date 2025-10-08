/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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
import {MetadataStore} from '../../../store/metadata/metadata.store.service';
import {Favorite} from '../../../common/record/favorites.model';
import {deepClone} from '../../../common/utils/object-utils';
import {ProcessService} from '../../process/process.service';
import {take} from 'rxjs/operators';

@Injectable({providedIn: 'root'})
export class FavoritesService {

    constructor(
        protected metadata: MetadataStore,
        protected processService: ProcessService
    ) {
    }

    /**
     * Public Api
     */

    /**
     * Build new favorite
     * @param module
     * @param id
     */
    public build(module: string, id: string): Favorite {
        return deepClone({
            module: 'Favorite',
            type: 'Favorite',
            attributes: {
                parent_id: id,
                parent_type: module,
            },
        } as Favorite);
    }


    /**
     * Add favorite
     * @param module
     * @param favorite
     */
    public add(module: string, favorite: Favorite): void {
        this.update(module, favorite, 'add');
    }

    /**
     * Add favorite
     * @param module
     * @param favorite
     */
    public remove(module: string, favorite: Favorite): void {
        this.update(module, favorite, 'remove');
    }

    /**
     * Save favorite to backend
     * @param module
     * @param favorite
     * @param action
     */
    protected update(module: string, favorite: Favorite, action: string): void {

        const processType = 'update-favorite';

        const options = {
            favorite: favorite,
            action
        };

        setTimeout(() => {
            this.processService.submit(processType, options).pipe(take(1)).subscribe(result => {

                const savedFavorite = result?.data?.favorite ?? null;
                if (savedFavorite === null) {
                    this.removeFavoriteFromMetadata(module, favorite);
                    return;
                }
                this.addFavoriteToMetadata(savedFavorite, module);
            });
        }, 100);
    }

    /**
     *
     * @param savedFavorite
     * @param module
     * @private
     */
    protected addFavoriteToMetadata(savedFavorite: Favorite, module: string): void {
        const saved = {
            id: savedFavorite?.id ?? '',
            module: savedFavorite?.module ?? '',
            attributes: {...(savedFavorite?.attributes ?? {})},
        };

        const newItemId = savedFavorite?.attributes?.parent_id ?? '';
        const metadata = this.metadata.getModuleMeta(module);

        const current = metadata?.favorites ?? null;
        if (current) {
            let cleared = current.filter(item => ((item?.attributes?.parent_id ?? '') !== newItemId));
            cleared.unshift(saved);
            metadata.favorites = cleared;
        }

        this.metadata.setModuleMetadata(module, metadata);
    }

    /**
     * Remove favorite from metadata
     * @param module
     * @param favorite
     */
    protected removeFavoriteFromMetadata(module: string, favorite: Favorite): void {
        const metadata = this.metadata.getModuleMeta(module);

        const current = metadata?.favorites ?? null;
        const parentId = favorite?.attributes?.parent_id ?? null;
        if (!current || !current.length || !parentId) {
            return;
        }

        metadata.favorites = current.filter(item => ((item?.attributes?.parent_id ?? '') !== parentId));
        this.metadata.setModuleMetadata(module, metadata);
    }
}
