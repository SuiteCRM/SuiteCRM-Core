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


namespace App\ViewDefinitions\Service;

use App\ViewDefinitions\Entity\ViewDefinition;
use Exception;

interface ViewDefinitionsProviderInterface
{

    /**
     * Get list-view defs
     * @param string $moduleName
     * @return ViewDefinition
     * @throws Exception
     */
    public function getListViewDef(string $moduleName): ViewDefinition;

    /**
     * Get view definitions
     * @param string $moduleName
     * @param array $views
     * @return ViewDefinition
     * @throws Exception
     */
    public function getViewDefs(string $moduleName, array $views = []): ViewDefinition;

    /**
     * Get search defs
     * @param string $moduleName
     * @return ViewDefinition
     * @throws Exception
     */
    public function getSearchDefs(string $moduleName): ViewDefinition;

    /**
     * Get record view defs
     * @param string $moduleName
     * @return ViewDefinition
     * @throws Exception
     */
    public function getRecordViewDefs(string $moduleName): ViewDefinition;

    /**
     * Get record-modal defs
     * @param string $moduleName
     * @return ViewDefinition
     * @throws Exception
     */
    public function getRecordModalDefs(string $moduleName): ViewDefinition;
}
