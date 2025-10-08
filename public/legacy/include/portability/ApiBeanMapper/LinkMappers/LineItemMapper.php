<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2021 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

require_once __DIR__ . '/LinkMapperInterface.php';
require_once __DIR__ . '/LineItemMapperTrait.php';

abstract class LineItemMapper implements LinkMapperInterface
{
    use LineItemMapperTrait;

    /**
     * @inheritDoc
     */
    public function toApi(SugarBean $bean, array &$container, string $name): void
    {
        $newName = $name;

        $definition = $this->getDefinition($bean, $name);
        $isLineItem = $this->isLineItem($bean, $name);

        if (!$isLineItem) {
            return;
        }

        $container[$newName] = [];

        $itemBeans = $this->getItemBeans($bean, $definition);

        $container = $this->mapBeansToApi($itemBeans, $container, $newName);
    }


    /**
     * Check if it is a line Item
     * @param SugarBean $bean
     * @param string $name
     * @return bool
     */
    protected function isLineItem(SugarBean $bean, string $name): bool
    {
        $definition = $this->getDefinition($bean, $name);

        return (bool)($definition['line-item'] ?? false);
    }

    /**
     * @inheritDoc
     */
    public function toBean(SugarBean $bean, array &$container, string $name, string $alternativeName = ''): void
    {
        $items = $container[$name] ?? null;
        if (empty($items)) {
            return;
        }

        if(!empty($bean->field_defs[$name]) && $bean->field_defs[$name]['type'] === 'link') {
            $bean->field_defs[$name]['line-item'] = true;
        }

        $definition = $this->getDefinition($bean, $name);

        $module = $definition['module'] ?? '';
        $isLineItem = $this->isLineItem($bean, $name);

        unset($bean->field_defs[$name]['line-item']);

        if (!$isLineItem || $module === '') {
            return;
        }

        $bean->line_item_entries[$name] = [];

        foreach ($items as $item) {
            $mapper = new ApiBeanMapper();

            $itemBean = $this->buildItemBean($module, $item);

            $mapper->toBean($itemBean, $item['attributes']);

            $bean->line_item_entries[$name][] = $itemBean;
        }
    }

    /**
     * @param string $module
     * @param $item
     * @return bool|SugarBean
     */
    protected function buildItemBean(string $module, $item)
    {
        $itemBean = BeanFactory::newBean($module);
        $id = $item['attributes']['id'] ?? '';
        if (!empty($id)) {
            $itemBean = BeanFactory::getBean($module, $id);
        }

        return $itemBean;
    }
}
