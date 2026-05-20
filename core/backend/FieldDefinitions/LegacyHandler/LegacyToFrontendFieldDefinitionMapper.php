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


namespace App\FieldDefinitions\LegacyHandler;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\LegacyToFrontendDefinitionMapper;

class LegacyToFrontendFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    /**
     * @var array
     */
    private $legacyToFrontEndFieldsMap;

    /**
     * LegacyFieldDefinitionMapper constructor.
     * @param array $legacyToFrontEndFieldsMap
     */
    public function __construct(array $legacyToFrontEndFieldsMap)
    {
        $this->legacyToFrontEndFieldsMap = $legacyToFrontEndFieldsMap;
    }

    /**
     * @inheritDoc
     */

    public function getKey(): string
    {
        return 'legacy-to-frontend-fields-mapper';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    /**
     * getLegacyToFrontEndFieldsMap
     * @return array
     */
    public function getLegacyToFrontendFieldsMap(): array
    {
        return $this->legacyToFrontEndFieldsMap ?? [];
    }

    /**
     * @inheritDoc
     */
    public function map(FieldDefinition $fieldDefinition): void
    {
        $legacyToFrontEndFieldsMap = $this->getLegacyToFrontendFieldsMap();
        if (empty($legacyToFrontEndFieldsMap)) {
            return;
        }

        foreach ($legacyToFrontEndFieldsMap as $mapKey => $mapDefinition) {

            $legacyFieldsMap = $mapDefinition['from'] ?? [];
            $toProperties = $mapDefinition['to'] ?? [];
            $unset = $mapDefinition['unset'] ?? false;
            $matchAll = $mapDefinition['matchAll'] ?? true;

            if (empty($mapDefinition) || empty($legacyFieldsMap) || empty($toProperties)) {
                continue;
            }

            $this->mapFieldDefinitions($fieldDefinition, $legacyFieldsMap, $toProperties, $unset, $matchAll);
        }
    }

    /**
     * @param FieldDefinition $fieldDefinition
     * @param array $legacyMap
     * @param array $frontendMap
     * @param bool $unset
     * @param bool $matchAll
     * @return void
     * @desc maps legacy to front-end field format for Record Vardefs
     */
    public function mapFieldDefinitions(
        FieldDefinition $fieldDefinition,
        array $legacyMap,
        array $frontendMap,
        bool $unset = false,
        bool $matchAll = true
    ): void {
        $vardefs = $fieldDefinition->getVardef() ?? [];

        $service = new LegacyToFrontendDefinitionMapper();
        $transformedVardefs = $service->getTransformedVardefs($vardefs, $legacyMap, $frontendMap, $unset, $matchAll);

        $fieldDefinition->setVardef($transformedVardefs);
    }

}
