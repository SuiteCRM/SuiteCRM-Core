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


namespace App\Themes\Entity;


use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use App\Themes\DataProvider\ThemeImagesStateProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/theme-images/{id}', provider: ThemeImagesStateProvider::class)
    ],
    graphQlOperations: [
        new Query(provider: ThemeImagesStateProvider::class)
    ]
)]
class ThemeImages
{
    /**
     * @var string|null
     */
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'The id',
        ]
    )]
    protected ?string $id;

    /**
     * @var array|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The items',
        ]
    )]
    protected ?array $items;

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
     * @return ThemeImages
     */
    public function setId(?string $id): ThemeImages
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get items
     * @return array|null
     */
    public function getItems(): ?array
    {
        return $this->items ?? null;
    }

    /**
     * Set Items
     * @param array|null $items
     * @return ThemeImages
     */
    public function setItems(?array $items): ThemeImages
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
            'items' => $this->getItems(),
        ];
    }
}
