<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2024 SalesAgility Ltd.
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

require_once __DIR__ . '/../BeanSaveHandlerInterface.php';

class BeanMultiRelateSaveHandler implements BeanSaveHandlerInterface
{
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
    public function save(SugarBean $bean): void
    {
        $fieldDefs = $bean->field_defs ?? [];
        foreach ($fieldDefs as $key => $field) {
            $type = ($field['type'] ?? '');
            $linkName = $field['link'] ?? '';

            if ($type !== 'multirelate' || empty($linkName)) {
                continue;
            }

            if (!isset($bean->$key) || !is_array($bean->$key)) {
                continue;
            }

            if (!$bean->load_relationship($linkName)) {
                continue;
            }

            $deleteIds = $this->getDeletedRelatedIds($key, $bean);

            if (!empty($deleteIds)) {
                $bean->$linkName->remove($deleteIds);
            }

            $ids = $this->getAddedRelatedIds($key, $bean);

            if(!empty($ids)) {
                $bean->$linkName->add($ids);
            }
        }
    }

    /**
     * @param int|string $key
     * @param SugarBean $bean
     * @return array
     */
    protected function getAddedRelatedIds(int|string $key, SugarBean $bean): array
    {
        if (empty($bean->$key)) {
            return [];
        }

        $ids = [];

        foreach ($bean->$key as $item) {
            $id = $item['id'] ?? '';
            $deleted = $item['deleted'] ?? 0;
            if ($id === '' || $deleted === 1) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @param int|string $key
     * @param SugarBean $bean
     * @return array
     */
    protected function getDeletedRelatedIds(int|string $key, SugarBean $bean): array
    {
        $idsToDelete = [];
        $existingIds = [];
        $currentIds = [];

        $linkName = $bean->field_defs[$key]['link'] ?? '';
        if (empty($linkName)) {
            return $idsToDelete;
        }

        if (!$bean->load_relationship($linkName)) {
            return $idsToDelete;
        }

        $relatedBeans = $bean->$linkName->getBeans();
        foreach ($relatedBeans as $relatedBean) {
            $existingIds[] = $relatedBean->id;
        }

        if (!empty($bean->$key)) {
            foreach ($bean->$key as $item) {
                $currentIds[] = $item['id'] ?? '';
            }
        }

        if (empty($currentIds)) {
            $idsToDelete = $existingIds;
        } else {
            $idsToDelete = array_diff($existingIds, $currentIds);
        }

        return $idsToDelete;
    }
}
