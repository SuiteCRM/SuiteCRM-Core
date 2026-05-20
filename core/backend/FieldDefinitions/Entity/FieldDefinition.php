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


namespace App\FieldDefinitions\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use App\FieldDefinitions\DataProvider\FieldDefinitionStateProvider;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')", provider: FieldDefinitionStateProvider::class),
    ],
    routePrefix: "/vardef",
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(security: "is_granted('ROLE_USER')", provider: FieldDefinitionStateProvider::class)
    ]
)]
class FieldDefinition
{
    /**
     * Module vardef metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The vardef metadata',
        ]
    )]
    public array $vardef;

    /**
     * The module
     *
     * @var string
     */
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'The module',
        ]
    )]
    protected string $id;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id ?? '';
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get vardef metadata
     * @return array
     */
    public function getVardef(): ?array
    {
        return $this->vardef ?? null;
    }

    /**
     * Set vardef metadata
     * @param array $vardef
     */
    public function setVardef(array $vardef): void
    {
        $this->vardef = $vardef;
    }

    /**
     * Get readonly metadata
     * @param array $fieldDefinition
     * @param ?array $readOnlyPatterns
     * @return array
     */
    public function getReadOnlyFieldDefinition(array $fieldDefinition, ?array $readOnlyPatterns = []): array
    {
        $defaultReadOnlyPatterns = array('readonly' => true, 'type' => 'readonly');
        if (empty($readOnlyPatterns)) {
            $readOnlyPatterns = $defaultReadOnlyPatterns;
        }

        return array_uintersect_uassoc($fieldDefinition, $readOnlyPatterns, "strcasecmp", "strcasecmp");
    }

}
