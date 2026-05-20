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


namespace App\SystemConfig\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\SystemConfig\DataProvider\SystemConfigStateProvider;

#[ApiResource(
    operations: [
        new Get(provider: SystemConfigStateProvider::class),
        new GetCollection(provider: SystemConfigStateProvider::class)
    ],
    graphQlOperations: [
        new Query(provider: SystemConfigStateProvider::class),
        new QueryCollection(provider: SystemConfigStateProvider::class)
    ]
)]
class SystemConfig
{
    /**
     * @var string|null
     */
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'The preference id',
        ]
    )]
    protected ?string $id;

    /**
     * @var string|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The value',
        ]
    )]
    protected ?string $value;

    /**
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The items',
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
     * @return SystemConfig
     */
    public function setId(?string $id): SystemConfig
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
     * @return SystemConfig
     */
    public function setValue(?string $value): SystemConfig
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
     * @return SystemConfig
     */
    public function setItems(array $items): SystemConfig
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
