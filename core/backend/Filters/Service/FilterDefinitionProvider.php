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


namespace App\Filters\Service;

use DBManagerFactory;
use Symfony\Component\Security\Core\Security;
use function is_array;

/**
 * Class FilterDefinitionProvider
 * @package App\Service
 */
class FilterDefinitionProvider implements FilterDefinitionProviderInterface
{
    /**
     * @var array
     */
    private $listViewFilters;

    /**
     * @var Security
     */
    private $security;

    /**
     * FilterDefinitionProvider constructor.
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param $moduleName
     * @return array
     */
    public function getFilters(string $moduleName): array
    {
        $user = $this->security->getUser();
        $userid = $user->getId();

        $db = DBManagerFactory::getInstance();
        $query = 'SELECT id, name, contents FROM saved_search
				  WHERE
					deleted = \'0\' AND
				  	assigned_user_id = \'' . $userid . '\' AND
					search_module =  \'' . $moduleName . '\'
				  ORDER BY name';
        $result = $db->query($query, true, 'Error filling in saved search list: ');

        $this->listViewFilters = [];
        while ($row = $db->fetchByAssoc($result, -1, false)) {
            $contents = unserialize(base64_decode($row['contents']));
            unset(
                $contents['searchFormTab'],
                $contents['query'],
                $contents['search_module'],
                $contents['saved_search_action'],
                $contents['displayColumns'],
                $contents['hideTabs'],
                $contents['orderBy'],
                $contents['sortOrder'],
                $contents['advanced']
            );

            foreach ($contents as $key => $item) {
                $newkey = str_replace('_advanced', '', $key);
                $contents[$newkey] = $item;
                if (is_array($contents[$key])) {
                    unset($contents[$newkey]);
                }
                unset($contents[$key]);
            }


            $contents = array_filter($contents, '\strlen');

            $this->listViewFilters[] = [
                'id' => $row['id'],
                'name' => htmlspecialchars($row['name'], ENT_QUOTES),
                'filters' => $contents
            ];
        }

        return $this->listViewFilters;
    }
}
