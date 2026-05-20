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

import {BehaviorSubject, Observable, of} from 'rxjs';
import {shareReplay} from 'rxjs/operators';
import {ThemeImages, ThemeImagesStore} from './theme-images.store';
import {EntityGQL} from '../../services/api/graphql-api/api.entity.get';
import {appStateStoreMock} from '../app-state/app-state.store.spec.mock';
import {AppStateStore} from '../app-state/app-state.store';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const themeImagesMockData = {
    bgOcher: {
        path: 'legacy/themes/default/images/bgOcher.gif',
        name: 'bgOcher',
        type: 'gif'
    },
    Createjjwg_Maps: {
        path: 'legacy/themes/default/images/Createjjwg_Maps.gif',
        name: 'Createjjwg_Maps',
        type: 'gif'
    },
    close: {
        path: 'legacy/themes/suite8/images/close.png',
        name: 'close',
        type: 'png'
    },
    ProjectCopy: {
        path: 'legacy/themes/default/images/ProjectCopy.gif',
        name: 'ProjectCopy',
        type: 'gif'
    },
    line: {
        path: 'legacy/themes/default/images/line.gif',
        name: 'line',
        type: 'gif'
    },
    download: {
        path: 'core/app/themes/suite8/images/download.svg',
        name: 'download',
        type: 'svg'
    },
    paginate_next: {
        path: 'core/app/themes/suite8/images/paginate_next.svg',
        name: 'paginate_next',
        type: 'svg'
    },
    filter: {
        path: 'core/app/themes/suite8/images/filter.svg',
        name: 'filter',
        type: 'svg'
    },
    reset: {
        path: 'core/app/themes/suite8/images/reset.svg',
        name: 'reset',
        type: 'svg'
    },
    column_chooser: {
        path: 'core/app/themes/suite8/images/column_chooser.svg',
        name: 'column_chooser',
        type: 'svg'
    },
    pie: {
        path: 'core/app/themes/suite8/images/pie.svg',
        name: 'pie',
        type: 'svg'
    },
    cross: {
        path: 'core/app/themes/suite8/images/cross.svg',
        name: 'cross',
        type: 'svg'
    },
    plus: {
        path: 'core/app/themes/suite8/images/plus.svg',
        name: 'plus',
        type: 'svg'
    },
    edit: {
        path: 'core/app/themes/suite8/images/edit.svg',
        name: 'edit',
        type: 'svg'
    },
    minimise_circled: {
        path: 'core/app/themes/suite8/images/minimise_circled.svg',
        name: 'minimise_circled',
        type: 'svg'
    },
    paginate_previous: {
        path: 'core/app/themes/suite8/images/paginate_previous.svg',
        name: 'paginate_previous',
        type: 'svg'
    },
    paginate_first: {
        path: 'core/app/themes/suite8/images/paginate_first.svg',
        name: 'paginate_first',
        type: 'svg'
    },
    paginate_last: {
        path: 'core/app/themes/suite8/images/paginate_last.svg',
        name: 'paginate_last',
        type: 'svg'
    },
    home: {
        path: 'core/app/themes/suite8/images/home.svg',
        name: 'home',
        type: 'svg'
    },
    view: {
        path: 'core/app/themes/suite8/images/view.svg',
        name: 'view',
        type: 'svg'
    },
    sort: {
        path: 'core/app/themes/suite8/images/sort.svg',
        name: 'sort',
        type: 'svg'
    },
    sort_ascend: {
        path: 'core/app/themes/suite8/images/sort_ascend.svg',
        name: 'sort_ascend',
        type: 'svg'
    },
    sort_descend: {
        path: 'core/app/themes/suite8/images/sort_descend.svg',
        name: 'sort_descend',
        type: 'svg'
    },
    minimise: {
        path: 'core/app/themes/suite8/images/minimise.svg',
        name: 'minimise',
        type: 'svg'
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class ThemeImagesRecordGQLSpy extends EntityGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(module: string, id: string, metadata: { fields: string[] }): Observable<any> {

        return of({
            data: {
                themeImages: {
                    _id: 'suite8',
                    items: themeImagesMockData
                }
            }
        }).pipe(shareReplay(1));
    }
}

class MockThemeImagesStore extends ThemeImagesStore {
    protected store = new BehaviorSubject<ThemeImages>({
        theme: 'suite8',
        images: themeImagesMockData
    });

    constructor(protected recordGQL: EntityGQL, protected appStateStore: AppStateStore) {
        super(recordGQL, appStateStore);
    }
}

export const themeImagesStoreMock = new MockThemeImagesStore(new ThemeImagesRecordGQLSpy(), appStateStoreMock);
