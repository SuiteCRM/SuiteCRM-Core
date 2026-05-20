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

namespace App\Data\Resolver;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use App\Data\Entity\Record;
use App\Data\Entity\RecordList;
use App\Data\LegacyHandler\RecordListHandler;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordMapperRunner;

class RecordListResolver implements QueryItemResolverInterface
{
    protected RecordListHandler $recordListHandler;
    protected ApiRecordMapperRunner $apiRecordMapperRunner;

    /**
     * RecordListResolver constructor.
     * @param RecordListHandler $recordListHandler
     * @param ApiRecordMapperRunner $apiRecordMapperRunner
     */
    public function __construct(
        RecordListHandler $recordListHandler,
        ApiRecordMapperRunner $apiRecordMapperRunner
    )
    {
        $this->recordListHandler = $recordListHandler;
        $this->apiRecordMapperRunner = $apiRecordMapperRunner;
    }

    /**
     * @param RecordList|null $item
     *
     * @param array $context
     * @return RecordList
     */
    public function __invoke($item, array $context): RecordList
    {
        $module = $context['args']['module'] ?? '';
        $limit = $context['args']['limit'] ?? -1;
        $offset = $context['args']['offset'] ?? -1;
        $criteria = $context['args']['criteria'] ?? [];
        $sort = $context['args']['sort'] ?? [];

        $list = $this->recordListHandler->getList($module, $criteria, $offset, $limit, $sort);

        $mappedRecords = [];
        foreach ($list->getRecords() as $recordArray) {
            $record = new Record();
            $record->fromArray($recordArray);

            $this->apiRecordMapperRunner->toExternal($record, 'list');
            $mappedRecords[] = $record->toArray();
        }

        $list->setRecords($mappedRecords);
        return $list;
    }
}
