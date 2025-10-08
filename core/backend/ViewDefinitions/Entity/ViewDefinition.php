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

namespace App\ViewDefinitions\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use App\ViewDefinitions\DataProvider\ViewDefinitionStateProvider;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')", provider: ViewDefinitionStateProvider::class),
        new GetCollection(security: "is_granted('ROLE_USER')", provider: ViewDefinitionStateProvider::class)
    ],
    routePrefix: '/metadata',
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(security: "is_granted('ROLE_USER')", provider: ViewDefinitionStateProvider::class),
    ]
)]
class ViewDefinition
{
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The record-view metadata'
        ]
    )]
    public array $recordView;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The edit-view metadata'
        ]
    )]
    public array $editView;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The list-view metadata'
        ]
    )]
    public array $listView;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The record-modal metadata'
        ]
    )]
    public array $recordModal;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The search metadata'
        ]
    )]
    public array $search;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The subpanel metadata'
        ]
    )]
    public array $subpanel;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The massUpdate metadata'
        ]
    )]
    public array $massUpdate;

    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'Extra metadata entries'
        ]
    )]
    public array $extra;

    /**
     * The module
     */
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'The module',
            'example' => 'Accounts'
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
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get Record View metadata
     * @return array|null
     */
    public function getRecordView(): ?array
    {
        return $this->recordView ?? null;
    }

    /**
     * Set Record view metadata
     * @param array $recordView
     */
    public function setRecordView(array $recordView): void
    {
        $this->recordView = $recordView;
    }

    /**
     * Get EditView Metadata
     * @return array|null
     */
    public function getEditView(): ?array
    {
        return $this->editView ?? null;
    }

    /**
     * Set EditView Metadata
     * @param array $editView
     */
    public function setEditView(array $editView): void
    {
        $this->editView = $editView;
    }

    /**
     * Get List View Metadata
     * @return array|null
     */
    public function getListView(): ?array
    {
        return $this->listView ?? null;
    }

    /**
     * Set List View metadata
     * @param array $listView
     */
    public function setListView(array $listView): void
    {
        $this->listView = $listView;
    }

    /**
     * Get Record Modal metadata
     * @return array|null
     */
    public function getRecordModal(): ?array
    {
        return $this->recordModal ?? null;
    }

    /**
     * Set Record Modal metadata
     * @param array $recordModal
     */
    public function setRecordModal(array $recordModal): void
    {
        $this->recordModal = $recordModal;
    }

    /**
     * Get Search Metadata
     * @return array|null
     */
    public function getSearch(): ?array
    {
        return $this->search ?? null;
    }

    /**
     * Set Search Metadata
     * @param array $search
     * @return ViewDefinition
     */
    public function setSearch(array $search): ViewDefinition
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Get Subpanel Metadata
     * @return array|null
     */
    public function getSubPanel(): ?array
    {
        return $this->subpanel ?? null;
    }

    /**
     * Set Subpanel Metadata
     * @param array $subpanel
     * @return ViewDefinition
     */
    public function setSubPanel(array $subpanel): ViewDefinition
    {
        $this->subpanel = $subpanel;

        return $this;
    }

    /**
     * Get Mass Update definitions
     * @return array|null
     */
    public function getMassUpdate(): ?array
    {
        return $this->massUpdate ?? null;
    }

    /**
     * Set Mass Update definitions
     * @param array $massUpdate
     * @return ViewDefinition
     */
    public function setMassUpdate(array $massUpdate): ViewDefinition
    {
        $this->massUpdate = $massUpdate;

        return $this;
    }

    /**
     * Get extra metadata entries
     * @return array|null
     */
    public function getExtraEntries(): ?array
    {
        return $this->extra ?? null;
    }

    /**
     * Set extra metadata entries
     * @param array $extraEntries
     */
    public function setExtraEntries(array $extraEntries): void
    {
        $this->extra = $extraEntries;
    }

    /**
     * Get extra metadata entry
     * @param $key
     * @return array|null
     */
    public function getExtraEntry($key): ?array
    {
        if (empty($this->extra[$key])) {
            return null;
        }

        return $this->extra[$key] ?? null;
    }

    /**
     * Set extra metadata entry
     * @param string $key
     * @param array $definition
     */
    public function setExtraEntry(string$key, array $definition): void
    {
        if (empty($this->extra)) {
            $this->extra = [];
        }

        $this->extra[$key] = $definition;
    }
}
