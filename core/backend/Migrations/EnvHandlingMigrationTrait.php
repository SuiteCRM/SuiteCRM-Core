<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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

namespace App\Migrations;

trait EnvHandlingMigrationTrait
{

    /**
     * Get env properties content to add
     * @param string $contents
     * @param array $properties
     * @param string $wrapperStart
     * @param string $wrapperEnd
     * @return string
     */
    protected function getContentToAdd(
        string &$contents,
        array $properties,
        string $wrapperStart,
        string $wrapperEnd
    ): string {

        $missingProperties = $this->getMissingProperties($contents, $properties);

        if (empty($missingProperties)) {
            return '';
        }

        $envContents = array_merge([$wrapperStart], $missingProperties, [$wrapperEnd]);

        return "\n" . implode("\n", $envContents) . "\n";
    }

    /**
     * Get missing properties
     * @param string $contents
     * @param array $properties
     * @return array
     */
    protected function getMissingProperties(string &$contents, array $properties): array
    {
        $propertiesToAdd = [];

        foreach ($properties as $propertyName => $propertyValue) {
            if ($this->hasEnvProperty($contents, $propertyName)) {
                continue;
            }
            $propertiesToAdd[] = "$propertyName=$propertyValue";
        }

        return $propertiesToAdd;
    }

    protected function hasEnvProperty(string &$contents, string $propertyName): bool
    {
        return strpos($contents, $propertyName) !== false;
    }

}
