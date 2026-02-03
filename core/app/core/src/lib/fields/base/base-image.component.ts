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

import {Component, signal, WritableSignal} from '@angular/core';
import {DataTypeFormatter} from "../../services/formatters/data-type.formatter.service";
import {FieldLogicManager} from "../field-logic/field-logic.manager";
import {FieldLogicDisplayManager} from "../field-logic-display/field-logic-display.manager";
import {BaseFileComponent} from "./base-file.component";
import {MediaObjectsService} from "../../services/media-objects/media-objects.service";
import {
    LegacyEntrypointLinkBuilder
} from "../../services/navigation/legacy-entrypoint-link-builder/legacy-entrypoint-link-builder.service";
import {SystemConfigStore} from "../../store/system-config/system-config.store";
import {isTrue} from "../../common/utils/value-utils";

@Component({template: ''})
export class BaseImageComponent extends BaseFileComponent {

    maxHeight: string;
    maxWidth: string;
    preview = true;
    storageType: string;
    showThumbnail: boolean = true;
    loading: WritableSignal<boolean> = signal(false);

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected mediaObjects: MediaObjectsService,
        protected legacyEntrypointLinkBuilder: LegacyEntrypointLinkBuilder,
        protected systemConfigs: SystemConfigStore,
    ) {
        super(typeFormatter, logic, logicDisplay, mediaObjects, legacyEntrypointLinkBuilder);
    }

    getMaxHeight(): string {

        const maxHeight = this.field?.metadata?.maxHeight ?? null;

        if (maxHeight) {

            if (!maxHeight.endsWith('px')) {
                return maxHeight + 'px';
            }

            return maxHeight;
        }

        const maxHeightDefault = this.systemConfigs.getConfigValue('image_field_height_default') ?? null;

        if (maxHeightDefault) {
            return maxHeightDefault;
        }

        return '150px';
    }

    getMaxWidth(): string {

        const maxWidth = this.field?.metadata?.maxWidth ?? null;

        if (maxWidth) {

            if (!maxWidth.endsWith('px')) {
                return maxWidth + 'px';
            }

            return maxWidth;
        }

        return '100%';
    }

    protected isAllowedPreview(): boolean {
        const allowedPreview = this.systemConfigs.getConfigValue('allowed_preview') ?? [];
        const mimeType = this.field?.valueObject?.attributes?.mime_type ?? '';
        const ext = mimeType.split('/')[1] ?? '';

        return allowedPreview.includes(ext.toLowerCase()) && (isTrue(this.field?.metadata?.preview ?? true));
    }

    setLoading(value: boolean, delay: boolean = false) {
        if (!delay) {
            this.loading.set(value);
            return;
        }

        setTimeout(() => {
            this.loading.set(value);
        }, 600);
    }
}
