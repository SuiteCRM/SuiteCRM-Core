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

import {fakeAsync, flush} from '@angular/core/testing';
import {take} from 'rxjs/operators';
import {ViewFieldDefinition} from '../../../../common/metadata/metadata.model';
import {metadataStoreMock} from '../../../../store/metadata/metadata.store.spec.mock';
import {recordviewStoreMock} from './record-view.store.spec.mock';

describe('RecordViewStore', () => {

    const definitionNames = (): string[] => {
        const definitions = (recordviewStoreMock.recordStore as any).definitions as ViewFieldDefinition[];
        return (definitions ?? []).map(definition => definition.name);
    };

    afterEach(() => {
        metadataStoreMock.load('accounts', metadataStoreMock.getMetadataTypes()).pipe(take(1)).subscribe();
    });

    it('keeps the view field subscription alive when metadata is reset and recovers on reload', fakeAsync(() => {
        expect(definitionNames()).toContain('name');

        // Logout resets auth-based stores, re-emitting MetadataStore's initial `recordView: {}`.
        metadataStoreMock.clear();
        // Unhandled subscriber errors are rethrown by RxJS on a timer; flushing surfaces them here.
        flush();

        expect(definitionNames()).toEqual([]);

        metadataStoreMock.load('accounts', metadataStoreMock.getMetadataTypes()).pipe(take(1)).subscribe();
        flush();

        expect(definitionNames()).toContain('name');
        expect(definitionNames()).toContain('phone_office');
    }));
});
