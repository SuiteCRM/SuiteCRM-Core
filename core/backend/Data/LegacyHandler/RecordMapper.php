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



namespace App\Data\LegacyHandler;


use App\Data\Entity\Record;
use App\Module\Service\ModuleNameMapperInterface;

class RecordMapper
{

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * RecordMapper constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     */
    public function __construct(ModuleNameMapperInterface $moduleNameMapper)
    {
        $this->moduleNameMapper = $moduleNameMapper;
    }


    /**
     * @param array $listData
     * @param array|null $pageData
     * @return Record[]
     */
    public function mapRecords(array $listData, ?array $pageData = []): array
    {

        $records = [];
        foreach ($listData as $key => $data) {

            $moduleName = $data['module_name'] ?? '';
            $id = $data['id'] ?? '';
            $data['name'] = html_entity_decode($data['name'] ?? '', ENT_QUOTES);
            if (!empty($moduleName)) {
                $moduleName = $this->moduleNameMapper->toFrontEnd($moduleName);
            }

            $acls = $pageData['acls'][$id] ?? [];

            $record = new Record();
            $record->setType($data['object_name'] ?? '');
            $record->setModule($moduleName);
            $record->setId($id);
            $record->setAttributes($data);
            $record->setAcls($acls);
            $records[] = $record;
        }

        return $records;
    }

}
