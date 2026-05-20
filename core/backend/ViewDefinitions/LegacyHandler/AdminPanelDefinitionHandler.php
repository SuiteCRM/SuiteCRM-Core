<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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


namespace App\ViewDefinitions\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Routes\Service\RouteConverterInterface;
use App\ViewDefinitions\Service\AdminPanelDefinitionProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AdminPanelDefinitionHandler
 */
class AdminPanelDefinitionHandler extends LegacyHandler implements AdminPanelDefinitionProviderInterface
{
    use FieldDefinitionsInjectorTrait;

    public const HANDLER_KEY = 'adminPanel-definitions';

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * @var RouteConverterInterface
     */
    private $routeConverter;

    /**
     * AdminPanelDefinitionsHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     * @param RouteConverterInterface $routeConverter
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session,
        RouteConverterInterface $routeConverter
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->moduleNameMapper = $moduleNameMapper;
        $this->routeConverter = $routeConverter;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getAdminPanelDef(): array
    {
        $this->init();
        $admin_group_header = [];

        /* @noinspection PhpIncludeInspection */
        require 'modules/Administration/metadata/adminpaneldefs.php';
        $admin_group_header = $admin_group_header ?? [];
        $adminPanelMap = [];
        foreach ($admin_group_header as $adminEntry) {
            if (empty($adminEntry)) {
                continue;
            }

            $mapEntry = [
                'titleLabelKey' => $adminEntry[0] ?? '',
                'descriptionLabelKey' => $adminEntry[4] ?? '',
                'linkGroup' => [],
                'icon' => $adminEntry[5] ?? ''
            ];
            $adminEntryLinks = $adminEntry[3] ?? [];

            foreach ($adminEntryLinks as $linkGroupKey => $linkGroup) {
                if (empty($linkGroup)) {
                    continue;
                }
                $mappedLinkGroup = $this->buildAdminMenuGroup($linkGroup);
                $mapEntry['linkGroup'][$linkGroupKey] = $mappedLinkGroup;

            }
            $adminPanelMap[$mapEntry['titleLabelKey']] = $mapEntry;

        }
        $this->close();

        return array_values($adminPanelMap);
    }

    /**
     * @param mixed $linkGroup
     * @return array
     */
    protected function buildAdminMenuGroup($linkGroup): array
    {
        $mappedLinkGroup = [];
        foreach ($linkGroup as $linkKey => $link) {
            $mappedLink = $this->buildAdminMenuLink($link);
            $mappedLinkGroup[$linkKey] = $mappedLink;
        }

        return $mappedLinkGroup;
    }

    /**
     * @param mixed $link
     * @return array
     */
    protected function buildAdminMenuLink($link): array
    {
        $path = $this->routeConverter->convertUri($link[3]);
        $path = str_replace('./#/', '/', $path);
        $mappedLink = [
            'category' => $link[0] ?? '',
            'titleKey' => $link[1] ?? '',
            'descriptionKey' => $link[2] ?? '',
            'link' => $path ?? '',
            'icon' => $link[4] ?? '',
        ];
        $query = parse_url($path, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            $mappedLink['params'] = $params;
            $path = str_replace('?' . $query, '', $path);
            $mappedLink['link'] = $path;
        }

        return $mappedLink;
    }
}
