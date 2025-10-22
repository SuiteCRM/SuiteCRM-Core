/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {Component, OnInit} from "@angular/core";
import {BaseAttachmentComponent} from "../../../base/base-attachment.component";
import {DataTypeFormatter} from "../../../../services/formatters/data-type.formatter.service";
import {FieldLogicManager} from "../../../field-logic/field-logic.manager";
import {FieldLogicDisplayManager} from "../../../field-logic-display/field-logic-display.manager";
import {MediaObjectsService} from "../../../../services/media-objects/media-objects.service";
import {
    LegacyEntrypointLinkBuilder
} from "../../../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";

@Component({
    selector: 'scrm-attachments-list',
    templateUrl: './attachment.component.html',
    styles: [],
})
export class AttachmentListFieldComponent extends BaseAttachmentComponent implements OnInit {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder,
        protected systemConfig: SystemConfigStore
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjects, legacyEntrypointLinkBuilder, systemConfig);
    }

    ngOnInit() {
        super.ngOnInit();
        this.getValuesFromMetadata('list');
        this.initUploadedFiles();
    }
}
