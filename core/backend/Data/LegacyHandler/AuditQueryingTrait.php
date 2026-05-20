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

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use SugarBean;

trait AuditQueryingTrait
{

    /**
     * @param EntityManagerInterface $em
     * @param SugarBean $bean
     * @param string $field
     * @param array $queryParts
     * @param string $keyField
     * @param array $procedureParams
     * @return array
     * @throws Exception
     */
    protected function queryAuditInfo(
        EntityManagerInterface $em,
        SugarBean $bean,
        string $field,
        array $queryParts = [],
        string $keyField = 'after_value_string',
        array $procedureParams = []
    ): array
    {
        $parts = [];
        $parts['select'] = ' SELECT after_value_string, min(date_created) as first_update, max(date_created) as last_update ';
        $parts['from'] = ' FROM ' . $bean->get_audit_table_name() . ' ';
        $parts['where'] = " WHERE field_name = :field ";
        $parts['where'] .= " AND parent_id = :parentId ";
        $parts['group_by'] = ' GROUP BY after_value_string ';
        $parts['order_by'] = '';

        foreach ($parts as $key => $item) {
            if (isset($queryParts[$key])) {
                $parts[$key] = $queryParts[$key];
            }
        }

        $innerQuery = $this->joinQueryParts($parts);

        $result = $this->runAuditInfoQuery($em, $bean, $field, $procedureParams, $innerQuery);

        $rows = [];

        foreach ($result as $row) {
            if (!empty($keyField)) {
                $value = $row[$keyField] ?? '';
                if (!empty($value)) {
                    $rows[$value] = $row;
                }
            } else {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param EntityManagerInterface $em
     * @param SugarBean $bean
     * @param string $field
     * @param array $procedureParams
     * @param $innerQuery
     * @return array|null
     * @throws Exception
     */
    protected function runAuditInfoQuery(
        EntityManagerInterface $em,
        SugarBean $bean,
        string $field,
        array $procedureParams,
        $innerQuery
    ): ?array
    {
        $stmt = $em->getConnection()->prepare($innerQuery);
        $result = $stmt->executeQuery(array_merge(['field' => $field, 'parentId' => $bean->id], $procedureParams));

        return $result->fetchAllAssociative() ?? [];
    }
}
