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

namespace App\Metadata\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use App\Metadata\DataProvider\ModuleMetadataStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/module-metadata/{id}',
            security: "is_granted('ROLE_USER')",
            provider: ModuleMetadataStateProvider::class
        ),
    ],
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(security: "is_granted('ROLE_USER')", provider: ModuleMetadataStateProvider::class)
    ]
)]
class ModuleMetadata
{
    /**
     * Record View metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The record-view metadata',
        ]
    )]
    public array $recordView;

    /**
     * Record Modal metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The record-modal metadata',
        ]
    )]
    public array $recordModal;

    /**
     * Edit View metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The edit-view metadata',
        ]
    )]
    public array $editView;

    /**
     * Edit View metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'Extra metadata entries',
        ]
    )]
    public array $extra;

    /**
     * List View metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The list-view metadata',
        ]
    )]
    public array $listView;

    /**
     * Search metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The search metadata',
        ]
    )]
    public array $search;

    /**
     * Subpanel metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The subpanel metadata',
        ]
    )]
    public array $subpanel;

    /**
     * MassUpdate metadata
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The massUpdate metadata',
        ]
    )]
    public array $massUpdate;

    /**
     * recentlyViewed
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The recently viewed records',
        ]
    )]
    public array $recentlyViewed;

    /**
     * Favorites
     *
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'The favorite records',
        ]
    )]
    public array $favorites;

    /**
     * The module
     *
     * @var string
     */
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'the module',
        ]
    )]
    protected string $id;

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
     * @param array|null $editView
     */
    public function setEditView(?array $editView): void
    {
        $this->editView = $editView;
    }

    /**
     * Get extra metadata
     * @return array|null
     */
    public function getExtra(): ?array
    {
        return $this->extra ?? null;
    }

    /**
     * Set extra metadata
     * @param array|null $extra
     */
    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            '_id' => $this->getId(),
            'recordView' => $this->getRecordView() ?? [],
            'recordModal' => $this->getRecordModal() ?? [],
            'listView' => $this->getListView() ?? [],
            'search' => $this->getSearch() ?? [],
            'subPanel' => $this->getSubPanel() ?? [],
            'massUpdate' => $this->getMassUpdate() ?? [],
            'recentlyViewed' => $this->getRecentlyViewed() ?? [],
            'favorites' => $this->getFavorites() ?? [],
            'extra' => $this->getExtra() ?? [],
        ];
    }

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
    public function setId($id): void
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
     * @param array|null $recordView
     */
    public function setRecordView(?array $recordView): void
    {
        $this->recordView = $recordView;
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
     * @param array|null $recordModal
     */
    public function setRecordModal(?array $recordModal): void
    {
        $this->recordModal = $recordModal;
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
     * @param array|null $listView
     */
    public function setListView(?array $listView): void
    {
        $this->listView = $listView;
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
     * @param array|null $search
     * @return ModuleMetadata
     */
    public function setSearch(?array $search): ModuleMetadata
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
     * @param array|null $subpanel
     * @return ModuleMetadata
     */
    public function setSubPanel(?array $subpanel): ModuleMetadata
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
     * @param array|null $massUpdate
     * @return ModuleMetadata
     */
    public function setMassUpdate(?array $massUpdate): ModuleMetadata
    {
        $this->massUpdate = $massUpdate;

        return $this;
    }

    /**
     * Get Recently viewed records
     * @return array|null
     */
    public function getRecentlyViewed(): ?array
    {
        return $this->recentlyViewed ?? null;
    }

    /**
     * Set recently viewed
     * @param array|null $recentlyViewed
     * @return ModuleMetadata
     */
    public function setRecentlyViewed(?array $recentlyViewed): ModuleMetadata
    {
        $this->recentlyViewed = $recentlyViewed;

        return $this;
    }

    /**
     * Get favorite records
     * @return array|null
     */
    public function getFavorites(): ?array
    {
        return $this->favorites ?? null;
    }

    /**
     * @param array|null $favorites
     * @return ModuleMetadata
     */
    public function setFavorites(?array $favorites): ModuleMetadata
    {
        $this->favorites = $favorites;

        return $this;
    }

}
