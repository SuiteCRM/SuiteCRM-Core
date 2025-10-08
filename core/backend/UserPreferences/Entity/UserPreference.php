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


namespace App\UserPreferences\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\UserPreferences\DataProvider\UserPreferenceStateProvider;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')", provider: UserPreferenceStateProvider::class),
        new GetCollection(security: "is_granted('ROLE_USER')", provider: UserPreferenceStateProvider::class)
    ],
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(security: "is_granted('ROLE_USER')", provider: UserPreferenceStateProvider::class),
        new QueryCollection(security: "is_granted('ROLE_USER')", provider: UserPreferenceStateProvider::class)
    ]
)]
class UserPreference
{
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'The preference id',
        ]
    )]
    protected ?string $id;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The preference value',
        ]
    )]
    protected ?string $value;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The preference items',
        ]
    )]
    protected array $items = [];

    /**
     * Get Id
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id ?? null;
    }

    /**
     * Set Id
     * @param string|null $id
     * @return UserPreference
     */
    public function setId(?string $id): UserPreference
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get value
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value ?? null;
    }

    /**
     * Set value
     * @param string|null $value
     * @return UserPreference
     */
    public function setValue(?string $value): UserPreference
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get items
     * @return array
     */
    public function getItems(): array
    {
        return $this->items ?? [];
    }

    /**
     * Set Items
     * @param array $items
     * @return UserPreference
     */
    public function setItems(array $items): UserPreference
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            '_id' => $this->getId(),
            'items' => $this->getItems() ?? [],
            'value' => $this->getValue()
        ];
    }
}
