/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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
import {AdminMetadataStore} from '../../../../store/admin-metadata/admin-metadata.store';
import {
    AdminLinkGroupModel,
    AdminMetadata,
    AdminMetadataModel
} from '../../../../store/admin-metadata/admin-metadata.model';


@Component({
    selector: 'scrm-admin-panel',
    templateUrl: './admin-panel.component.html',
    styleUrls: [],
})
export class AdminPanelComponent implements OnInit {

    adminPanelData: AdminMetadataModel[] = [];

    constructor(protected adminMetaData: AdminMetadataStore) {
    }

    ngOnInit(): void {
        const adminData: AdminMetadata = this.adminMetaData.getAdminPanel();
        this.setData(adminData);
    }

    protected setData(adminData: AdminMetadata): void {
        adminData.forEach(({icon, titleLabelKey, descriptionLabelKey, linkGroup}) => {
            this.adminPanelData.push({
                icon,
                titleLabelKey,
                descriptionLabelKey,
                linkGroup: this.setLinkGroups(linkGroup)
            });
        });
    }

    protected setLinkGroups(groupData: AdminLinkGroupModel): AdminLinkGroupModel[] {
        let linkGroups: AdminLinkGroupModel[] = [];
        let linkGroupKeys: string[] = Object.keys(groupData);
        for (let j = 0; j < linkGroupKeys.length; j++) {
            let linkGroup = groupData[linkGroupKeys[j]];
            let links = Object.values(linkGroup);
            for (let i = 0; i < links.length; i++) {
                linkGroups.push(links[i]);
            }
        }
        return linkGroups;
    }
}
