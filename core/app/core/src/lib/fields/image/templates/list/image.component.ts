/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

import {Component} from "@angular/core";
import {DataTypeFormatter} from "../../../../services/formatters/data-type.formatter.service";
import {FieldLogicManager} from "../../../field-logic/field-logic.manager";
import {FieldLogicDisplayManager} from "../../../field-logic-display/field-logic-display.manager";
import {MediaObjectsService} from "../../../../services/media-objects/media-objects.service";
import {
    LegacyEntrypointLinkBuilder
} from "../../../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";
import {SystemConfigStore} from "../../../../store/system-config/system-config.store";
import {BaseImageComponent} from "../../../base/base-image.component";

@Component({
    selector: 'scrm-image-list',
    templateUrl: './image.component.html',
    styles: [],
})
export class ImageListFieldComponent extends BaseImageComponent {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder,
        protected systemConfigs: SystemConfigStore,
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjects, legacyEntrypointLinkBuilder, systemConfigs);
    }

    protected formatSize(value: string): string {
        if (value === '100%' || value.endsWith('px')) {
            return value;
        }
        return value + 'px';
    }

    ngOnInit(): void {
        this.showThumbnail = this.field.metadata?.showThumbnail ?? true;
        this.thumbnailCreated = this.field.metadata?.createThumbnail ?? true;
        this.maxHeight = this.formatSize(this.field?.metadata?.maxHeight || '60px');
        this.maxWidth = this.formatSize(this.field?.metadata?.maxWidth || '100%');
        this.preview = this.isAllowedPreview();

        const hasFile = this.field?.valueObject &&
            (this.field.valueObject.id || this.field.valueObject.value);

        if (hasFile) {
            this.loading.set(true);
            this.initUploadedFile();
            this.setLoading(false, true);
            return;
        }

        this.loading.set(false);
    }
}
