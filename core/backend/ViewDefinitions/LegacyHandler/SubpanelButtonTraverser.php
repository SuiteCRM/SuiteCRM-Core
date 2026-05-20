<?php
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

namespace App\ViewDefinitions\LegacyHandler;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\Module\Service\ModuleNameMapperInterface;

/**
 * Class SubpanelButtonTraverser
 * @package App\ViewDefinitions\LegacyHandler
 */
class SubpanelButtonTraverser implements ViewDefinitionMapperInterface
{
    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * @var SubpanelButtonMappers
     */
    private $mappers;

    /**
     * SubpanelButtonTraverser constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param SubpanelButtonMappers $mappers
     */
    public function __construct(
        ModuleNameMapperInterface $moduleNameMapper,
        SubpanelButtonMappers $mappers
    ) {
        $this->moduleNameMapper = $moduleNameMapper;
        $this->mappers = $mappers;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'subpanel-top-button-traverser';
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
        $moduleName = $definition->getId();
        $subpanels = $definition->getSubPanel() ?? [];
        $vardefs = $fieldDefinition->getVardef();

        $legacyName = $this->moduleNameMapper->toLegacy($moduleName);

        $mappers = $this->mappers->get($moduleName);

        if (empty($subpanels)) {
            return;
        }

        foreach ($subpanels as $key => $subpanel) {

            if (empty($subpanel['top_buttons'])) {
                continue;
            }

            $subpanels[$key]['top_buttons'] = $this->mapButtons(
                $mappers,
                $legacyName,
                $subpanel,
                $subpanel['top_buttons'],
                $vardefs
            );
        }

        $definition->setSubPanel($subpanels);
    }

    /**
     * Iterate over button and map SubPanelTopButtonQuickCreate buttons
     * @param SubpanelButtonMapperInterface[] $mappers
     * @param string $legacyModuleName
     * @param array $subpanel
     * @param array $buttons
     * @param array $parentVardefs
     * @return array
     */
    protected function mapButtons(
        array $mappers,
        string $legacyModuleName,
        array $subpanel,
        array $buttons,
        array $parentVardefs
    ): array {

        $defaults = [
            'create' => 'SubPanelTopButtonQuickCreate'
        ];

        foreach ($buttons as $key => $button) {

            $widgetClass = $button['widget_class'] ?? '';
            $buttonKey = $button['key'] ?? '';
            $default = $defaults[$buttonKey] ?? '';
            $mapper = $mappers[$widgetClass] ?? $mappers[$default] ?? null;

            if ($mapper !== null) {
                $buttons[$key] = $mapper->map($legacyModuleName, $subpanel, $button, $parentVardefs);
            }
        }

        return $buttons;
    }
}
