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

namespace App\Metadata\Service;

use App\Metadata\Entity\ModuleMetadata;
use App\Module\LegacyHandler\Favorites\FavoritesHandler;
use App\Module\LegacyHandler\RecentlyViewed\RecentlyViewedHandler;
use App\Module\Service\ModuleNameMapperInterface;
use App\ViewDefinitions\Service\ViewDefinitionsProviderInterface;
use Exception;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class ViewDefinitionsHandler
 */
class ModuleMetadataProvider implements ModuleMetadataProviderInterface
{
    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * @var ViewDefinitionsProviderInterface
     */
    protected $viewDefinitions;

    /**
     * @var RecentlyViewedHandler
     */
    protected $recentlyViewed;

    /**
     * @var FavoritesHandler
     */
    protected $favorites;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * ModuleMetadataProvider constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param ViewDefinitionsProviderInterface $viewDefinitions
     * @param RecentlyViewedHandler $recentlyViewed
     * @param FavoritesHandler $favorites
     */
    public function __construct(
        ModuleNameMapperInterface $moduleNameMapper,
        ViewDefinitionsProviderInterface $viewDefinitions,
        RecentlyViewedHandler $recentlyViewed,
        FavoritesHandler $favorites,
        CacheInterface $cache
    ) {
        $this->moduleNameMapper = $moduleNameMapper;
        $this->viewDefinitions = $viewDefinitions;
        $this->recentlyViewed = $recentlyViewed;
        $this->favorites = $favorites;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMetadata(string $moduleName, array $exposed = []): ModuleMetadata
    {
        global $current_user;

        $key = 'app-metadata-module-metadata-' . $moduleName . '-' . $current_user->id;

        $metadataArray = $this->cache->get($key, function () use ($moduleName, $exposed) {
            $metadataArray = [];
            $viewDefinitions = $this->viewDefinitions->getViewDefs($moduleName, $exposed);

            $metadataArray['search'] = $viewDefinitions->getSearch() ?? [];
            $metadataArray['mass_update'] = $viewDefinitions->getMassUpdate() ?? [];
            $metadataArray['listview'] = $viewDefinitions->getListView() ?? [];
            $metadataArray['subpanel'] = $viewDefinitions->getSubPanel() ?? [];
            $metadataArray['recordview'] = $viewDefinitions->getRecordView() ?? [];
            $metadataArray['recordmodal'] = $viewDefinitions->getRecordModal() ?? [];
            $metadataArray['extra'] = $viewDefinitions->getExtraEntries() ?? [];
            return $metadataArray;
        });

        $metadata = new ModuleMetadata();
        $metadata->setId($moduleName);
        $metadata->setSearch($metadataArray['search'] ?? []);
        $metadata->setListView($metadataArray['listview'] ?? []);
        $metadata->setMassUpdate($metadataArray['mass_update'] ?? []);
        $metadata->setSubPanel($metadataArray['subpanel'] ?? []);
        $metadata->setRecordView($metadataArray['recordview'] ?? []);
        $metadata->setRecordModal($metadataArray['recordmodal'] ?? []);
        $metadata->setExtra($metadataArray['extra'] ?? []);

        $metadata->setRecentlyViewed($this->recentlyViewed->getModuleTrackers($moduleName));
        $metadata->setFavorites($this->favorites->getModuleFavorites($moduleName));

        return $metadata;
    }
}
