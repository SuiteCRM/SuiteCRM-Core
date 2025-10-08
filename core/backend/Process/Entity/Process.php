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

namespace App\Process\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Put;
use App\Process\DataPersister\ProcessProcessor;
use App\Process\DataProvider\ProcessStateProvider;

#[ApiResource(
    operations: [
        new Get(provider: ProcessStateProvider::class),
        new Put(processor: ProcessProcessor::class),
        new GetCollection(provider: ProcessStateProvider::class)
    ],
    graphQlOperations: [
        new Query(provider: ProcessStateProvider::class),
        new QueryCollection(provider: ProcessStateProvider::class),
        new Mutation(name: 'create', processor: ProcessProcessor::class)
    ]
)]
class Process
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
     * @var string|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'type',
        ]
    )]
    protected ?string $type;

    /**
     * @var string|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'status',
        ]
    )]
    protected ?string $status;

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
     * @var bool|null
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'bool',
            'description' => 'async',
        ]
    )]
    protected ?bool $async;

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
     * @return Process
     */
    public function setId(?string $id): Process
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Type
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type ?? null;
    }

    /**
     * Set Type
     * @param string|null $type
     * @return Process
     */
    public function setType(?string $type): Process
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get Status
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status ?? null;
    }

    /**
     * Set Status
     * @param string|null $status
     * @return Process
     */
    public function setStatus(?string $status): Process
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get Messages
     * @return array|null
     */
    public function getMessages(): ?array
    {
        return $this->messages ?? null;
    }

    /**
     * Set Messages
     * @param String[]|null $messages
     * @return Process
     */
    public function setMessages(?array $messages): Process
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Get Async flag
     * @return bool|null
     */
    public function getAsync(): ?bool
    {
        return $this->async ?? null;
    }

    /**
     * Set Async flag
     * @param bool|null $async
     * @return Process
     */
    public function setAsync(?bool $async): Process
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Get options
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->options ?? null;
    }

    /**
     * Set Options
     * @param array|null $options
     * @return Process
     */
    public function setOptions(?array $options): Process
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get data
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data ?? null;
    }

    /**
     * Set data
     * @param array|null $data
     * @return Process
     */
    public function setData(?array $data): Process
    {
        $this->data = $data;

        return $this;
    }

}
