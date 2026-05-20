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


namespace App\Statistics\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use App\Statistics\DataProvider\StatisticsStateProvider;
use App\Statistics\Resolver\StatisticsItemResolver;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')", provider: StatisticsStateProvider::class),
    ],
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(
            resolver: StatisticsItemResolver::class,
            args: [
                'module' => ['type' => 'String!'],
                'query' => ['type' => 'Iterable'],
            ],
            security: "is_granted('ROLE_USER')",
            provider: StatisticsStateProvider::class
        ),
    ]
)]
class Statistic
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
     * @var string[]|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'messages',
        ]
    )]
    protected ?array $messages;

    /**
     * @var array|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'options',
        ]
    )]
    protected ?array $options;

    /**
     * @var array|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'data',
        ]
    )]
    protected ?array $data;

    /**
     * @var array|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'metadata',
        ]
    )]
    protected ?array $metadata;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id ?? null;
    }

    /**
     * @param string|null $id
     * @return Statistic
     */
    public function setId(?string $id): Statistic
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getMessages(): ?array
    {
        return $this->messages ?? null;
    }

    /**
     * @param string[]|null $messages
     * @return Statistic
     */
    public function setMessages(?array $messages): Statistic
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->options ?? null;
    }

    /**
     * @param array|null $options
     * @return Statistic
     */
    public function setOptions(?array $options): Statistic
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data ?? null;
    }

    /**
     * @param array|null $data
     * @return Statistic
     */
    public function setData(?array $data): Statistic
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata ?? null;
    }

    /**
     * @param array|null $metadata
     * @return Statistic
     */
    public function setMetadata(?array $metadata): Statistic
    {
        $this->metadata = $metadata;

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
            'messages' => $this->getMessages(),
            'options' => $this->getOptions(),
            'data' => $this->getData(),
            'metadata' => $this->getMetadata(),
        ];
    }
}
