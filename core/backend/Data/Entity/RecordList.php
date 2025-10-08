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
use ApiPlatform\Metadata\GraphQl\Query;
use App\Data\DataProvider\RecordListStateProvider;
use App\Data\Resolver\RecordListResolver;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: "/record-list/{id}",
            security: "is_granted('ROLE_USER')",
            provider: RecordListStateProvider::class
        )
    ],
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(
            resolver: RecordListResolver::class,
            args: [
                'module' => ['type' => 'String!'],
                'limit' => ['type' => 'Int'],
                'offset' => ['type' => 'Int'],
                'criteria' => ['type' => 'Iterable', 'description' => 'search criteria'],
                'sort' => ['type' => 'Iterable', 'description' => 'sort'],
            ],
            security: "is_granted('ROLE_USER')",
            provider: RecordListStateProvider::class,
        )
    ]
)]
class RecordList
{
    /**
     * RecordList data
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The list-view data',
        ]
    )]
    public array $records = [];

    /**
     * RecordList metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The list metadata',
        ]
    )]
    public array $meta = [];

    /**
     * RecordList filters
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The list-view filters',
        ]
    )]
    public array $filters = [];

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
    protected string $id = '';

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Get records
     * @return array
     */
    public function getRecords(): ?array
    {
        return $this->records ?? null;
    }

    /**
     * Set records
     * @param array $records
     */
    public function setRecords(array $records): void
    {
        $this->records = $records;
    }

    /**
     * Get meta
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Set meta
     * @param array $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * get filters
     * @return array
     */
    public function getFilters(): ?array
    {
        return $this->filters ?? null;
    }

    /**
     * Set filters
     * @param array $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }
}
