<?php
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

namespace App\Module\Service\Fields\Image\ViewDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

class SubPanelDefaultSizeImageMapper implements ViewDefinitionMapperInterface
{

    public function __construct(
        protected SystemConfigProviderInterface $systemConfigProvider,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'subpanel-default-size-image';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    /**
     * @inheritDoc
     */
    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $subpanels = $definition->getSubPanel() ?? [];

        foreach ($subpanels as $subpanelKey => $subpanel) {
            $columns = $subpanel['columns'] ?? [];

            if (empty($columns)) {
                continue;
            }

            foreach ($columns as $colKey => $col) {
                $type = $col['type'] ?? '';
                if ($type !== 'image') {
                    continue;
                }

                $metadata = $col['metadata'] ?? [];

                if (empty($metadata['maxHeight'])) {
                    $metadata['maxHeight'] = $this->getMaxHeight();
                }

                $col['metadata'] = $metadata;
                $columns[$colKey] = $col;
            }

            $subpanel['columns'] = $columns;
            $subpanels[$subpanelKey] = $subpanel;
        }

        $definition->setSubPanel($subpanels);
    }

    protected function getMaxHeight(): string
    {
        $defaultMaxHeight = $this->systemConfigProvider->getSystemConfig('image_field_subpanel_height_default')->getValue();

        if (!$defaultMaxHeight) {
            return '60px';
        }

        return $defaultMaxHeight;
    }
}
