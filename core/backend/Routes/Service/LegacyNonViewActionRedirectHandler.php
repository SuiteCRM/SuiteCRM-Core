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


namespace App\Routes\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

class LegacyNonViewActionRedirectHandler extends LegacyRedirectHandler
{
    /**
     * @var RouteConverterInterface
     */
    private $routeConverter;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var array
     */
    private $legacyEntrypointFiles;

    /**
     * LegacyNonViewActionRedirectHandler constructor.
     * @param RouteConverterInterface $routeConverter
     * @param RouterInterface $router
     * @param String $legacyPath
     * @param array $legacyEntrypointFiles
     */
    public function __construct(
        RouteConverterInterface $routeConverter,
        RouterInterface $router,
        string $legacyPath,
        array $legacyEntrypointFiles
    ) {
        parent::__construct($legacyPath);
        $this->routeConverter = $routeConverter;
        $this->router = $router;
        $this->legacyEntrypointFiles = $legacyEntrypointFiles;
    }

    /**
     * Check if the given $request is a non view api request
     *
     * @param Request $request
     * @return bool
     */
    public function isMatch(Request $request): bool
    {
        if ($this->routeConverter->isLegacyViewRoute($request)) {
            return false;
        }

        if ($this->routeConverter->isLegacyRoute($request)) {
            return true;
        }

        if ($request->getPathInfo() === '/_fragment') {
            return false;
        }

        $isRegistered = true;
        try {
            $this->router->matchRequest($request);
        } catch (NoConfigurationException | ResourceNotFoundException | MethodNotAllowedException $e) {
            $isRegistered = false;
        }

        return !($isRegistered === true);
    }

    /**
     * Check if is legacy entry point file
     *
     * @param Request $request
     * @return bool
     */
    public function isLegacyEntryPointFile(Request $request): bool
    {
        if (strpos($request->getPathInfo(), 'index.php') !== false) {
            return false;
        }

        if (preg_match('/^\\[^\\^\/]*\.php/', $request->getPathInfo())) {
            return true;
        }

        return false;
    }

    /**
     * Get include file
     *
     * @param Request $request
     * @return array
     */
    public function getEntrypointIncludeFile(Request $request): array
    {
        $includeFile = [
            'access' => false
        ];

        foreach ($this->legacyEntrypointFiles as $key => $config) {
            if (strpos($request->getPathInfo(), '/' . $key) !== false) {
                $includeFile = $config;
                $includeFile['access'] = true;
                break;
            }
        }

        return $includeFile;
    }

    /**
     * Check if is legacy entry point
     *
     * @param Request $request
     * @return bool
     */
    public function isLegacyEntryPoint(Request $request): bool
    {
        $isEntryPoint = false;

        if (strpos($request->getUri(), '/index.php') !== false &&
            strpos($request->getUri(), 'entryPoint=') !== false
        ) {
            $isEntryPoint = true;
        }

        return $isEntryPoint;
    }
}
