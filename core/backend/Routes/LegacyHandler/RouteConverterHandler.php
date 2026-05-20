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

namespace App\Routes\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Process\Service\ActionNameMapperInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Routes\Service\RouteConverterInterface;
use InvalidArgumentException;
use RouteConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RouteConverter
 *
 * @package App\Legacy
 */
class RouteConverterHandler extends LegacyHandler implements RouteConverterInterface
{
    public const HANDLER_KEY = 'route-converter';
    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;
    /**
     * Lazy initialized mapper
     * @var RouteConverter
     */
    protected $converter;
    /**
     * @var ActionNameMapperInterface
     */
    private $actionNameMapper;

    /**
     * SystemConfigHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param ActionNameMapperInterface $actionNameMapper
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        ActionNameMapperInterface $actionNameMapper,
        RequestStack $session
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
        $this->moduleNameMapper = $moduleNameMapper;
        $this->actionNameMapper = $actionNameMapper;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Check if the given $request route is a Legacy route
     *
     * @param Request $request
     * @return bool
     */
    public function isLegacyRoute(Request $request): bool
    {
        if ($this->isLegacyViewRoute($request)) {
            return true;
        }

        if ($request->getPathInfo() !== '/') {
            return false;
        }

        $valid = false;
        $valid |= !empty($request->get('module'));
        $valid |= !empty($request->get('action'));
        $valid |= !empty($request->get('entryPoint'));

        return $valid;
    }

    /**
     * Check if the given $request route can be converted
     *
     * @param Request $request
     * @return bool
     */
    public function isLegacyViewRoute(Request $request): bool
    {
        if (!empty($request->getPathInfo()) && $request->getPathInfo() !== '/') {
            return false;
        }

        $module = $request->query->get('module');

        if (empty($module)) {
            return false;
        }

        if (!$this->moduleNameMapper->isValidModule($module)) {
            return false;
        }

        $action = $request->query->get('action');

        return $this->actionNameMapper->isValidAction($action);
    }

    /**
     * Convert given $uri route
     *
     * @param string $uri
     * @return string
     */
    public function convertUri(string $uri): string
    {
        $request = Request::create($uri);

        return $this->convert($request);
    }

    /**
     * Convert given $request route
     *
     * @param Request $request
     * @return string
     */
    public function convert(Request $request): string
    {
        $this->init();

        $module = $request->query->get('module');
        $action = $request->query->get('action');
        $record = $request->query->get('record');

        if (empty($module)) {
            throw new InvalidArgumentException('No module defined');
        }

        $queryParams = [];
        if (null !== $request->getQueryString()) {
            $queryParams = $request->query->all();
        }

        $converter = $this->getConverter();

        $result = $converter->convert($module, $action, $record, $queryParams);

        $this->close();

        return $result;
    }

    /**
     * Get mapper. Initialize it if needed
     * @return RouteConverter
     */
    protected function getConverter(): RouteConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/RouteConverter.php';

        $this->converter = new RouteConverter();

        return $this->converter;
    }

    /**
     * Parse given $uri route
     *
     * @param string $uri
     * @return array
     */
    public function parseUri(string $uri): array
    {
        if (strpos($uri, '/#/') !== false) {
            $anchor = parse_url($uri, PHP_URL_FRAGMENT);
            $query = parse_url($uri, PHP_URL_QUERY);
            parse_str($query, $params);

            return [
                'route' => './#' . $anchor,
                'params' => $params
            ];
        }

        $this->init();

        $request = Request::create($uri);

        $module = $request->query->get('module');
        $action = $request->query->get('action');
        $record = $request->query->get('record');

        if (empty($module)) {
            throw new InvalidArgumentException('No module defined');
        }

        $queryParams = [];

        $converter = $this->getConverter();

        $route = $converter->convert($module, $action, $record, $queryParams);

        $result = [
            'route' => $route,
            'params' => $converter->excludeParams($request->query->all(), ['module', 'action', 'record']),
            'module' => $module
        ];

        $this->close();

        return $result;
    }
}
