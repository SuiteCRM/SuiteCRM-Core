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


namespace App\Data\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use App\Data\DataPersister\RecordProcessor;
use App\Data\DataProvider\RecordStateProvider;
use App\Data\Resolver\RecordItemResolver;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: "/record/{id}",
            security: "is_granted('ROLE_USER')",
            provider: RecordStateProvider::class
        )
    ],
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(
            resolver: RecordItemResolver::class,
            args: [
                'module' => ['type' => 'String!'],
                'record' => ['type' => 'String!'],
            ],
            security: "is_granted('ROLE_USER')",
            provider: RecordStateProvider::class
        ),
        new Mutation(
            args: [
                '_id' => ['type' => 'String', 'description' => 'id'],
                'identifier' => ['type' => 'String', 'description' => 'id'],
                'module' => ['type' => 'String!', 'description' => 'module'],
                'attributes' => ['type' => 'Iterable', 'description' => 'attributes'],
            ],
            security: "is_granted('ROLE_USER')",
            validate: false,
            name: 'save',
            processor: RecordProcessor::class
        )
    ]
)]
class Record
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
    protected ?string $id = '';

    /**
     * @var string|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The module',
        ]
    )]
    protected ?string $module = '';

    /**
     * @var string|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The type',
        ]
    )]
    protected ?string $type = '';

    /**
     * @var array|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The attributes',
        ]
    )]
    protected ?array $attributes = [];

    /**
     * @var array|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The acls',
        ]
    )]
    protected ?array $acls = [];

    /**
     * @var bool|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'bool',
            'description' => 'The favorite',
        ]
    )]
    protected ?bool $favorite = false;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'module' => $this->getModule(),
            'type' => $this->getType(),
            'attributes' => $this->getAttributes(),
            'acls' => $this->getAcls()
        ];
    }

    /**
     * @param array $recordData
     * @return void
     */
    public function fromArray(array $recordData): void
    {
        $this->setId($recordData['id'] ?? '');
        $this->setModule($recordData['module']?? '');
        $this->setType($recordData['type']?? '');
        $this->setAttributes($recordData['attributes'] ?? []);
        $this->setAcls($recordData['acls'] ?? []);
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id ?? null;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module ?? null;
    }

    /**
     * @param string|null $module
     * @return Record
     */
    public function setModule(?string $module): Record
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type ?? null;
    }

    /**
     * @param string $type
     * @return Record
     */
    public function setType(?string $type): Record
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get record attributes
     * @return array|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes ?? null;
    }

    /**
     * Set record attributes
     * @param array|null $attributes
     */
    public function setAttributes(?array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array|null
     */
    public function getAcls(): ?array
    {
        return $this->acls ?? null;
    }

    /**
     * @param array|null $acls
     * @return Record
     */
    public function setAcls(?array $acls): Record
    {
        $this->acls = $acls;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getFavorite(): ?bool
    {
        return $this->favorite ?? null;
    }

    /**
     * @param bool|null $favorite
     * @return Record
     */
    public function setFavorite(?bool $favorite): Record
    {
        $this->favorite = $favorite;

        return $this;
    }

}
