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

class LegacyRouteHandler
{

    /**
     * @var LegacyApiRedirectHandler
     */
    private $legacyApiRedirectHandler;

    /**
     * @var LegacyNonViewActionRedirectHandler
     */
    private $legacyNonViewActionRedirectHandler;
    /**
     * @var LegacyAssetRedirectHandler
     */
    private $legacyAssetRedirectHandler;

    private LegacyEntryPointRedirectHandler $legacyEntryPointRedirectHandler;
    private string $currentDir;

    /**
     * LegacyRedirectListener constructor.
     * @param LegacyApiRedirectHandler $legacyApiRedirectHandler
     * @param LegacyNonViewActionRedirectHandler $legacyNonViewActionRedirectHandler
     * @param LegacyAssetRedirectHandler $legacyAssetRedirectHandler
     * @param LegacyEntryPointRedirectHandler $legacyEntryPointRedirectHandler
     */
    public function __construct(
        LegacyApiRedirectHandler $legacyApiRedirectHandler,
        LegacyNonViewActionRedirectHandler $legacyNonViewActionRedirectHandler,
        LegacyAssetRedirectHandler $legacyAssetRedirectHandler,
        LegacyEntryPointRedirectHandler $legacyEntryPointRedirectHandler
    ) {
        $this->legacyApiRedirectHandler = $legacyApiRedirectHandler;
        $this->legacyNonViewActionRedirectHandler = $legacyNonViewActionRedirectHandler;
        $this->legacyAssetRedirectHandler = $legacyAssetRedirectHandler;
        $this->legacyEntryPointRedirectHandler = $legacyEntryPointRedirectHandler;
    }

    /**
     * Re-direct user
     * @param Request $request
     * @return array
     */
    public function getLegacyRoute(Request $request): array
    {
        if ($this->isRedirectLoop($request)) {
            return [
                'access' => true
            ];
        }

        if ($this->isLegacyEntryPoint($request)) {
            return $this->legacyNonViewActionRedirectHandler->getIncludeFile($request);
        }

        if ($this->isLegacyEntryPointPath($request)) {
            $entryPoint = $this->isValidLegacyEntryPoint($request);
            if ($entryPoint['valid']) {
                if ($entryPoint['auth']) {
                    return $this->legacyEntryPointRedirectHandler->getIncludeFile($request);
                }

                return [
                    'access' => false
                ];
            }

            return [];
        }

        if ($this->isLegacyApi($request)) {
            return $this->legacyApiRedirectHandler->getIncludeFile($request);
        }

        if ($this->isLegacyEntryPointFile($request)) {
            return $this->legacyNonViewActionRedirectHandler->getEntrypointIncludeFile($request);
        }

        if ($this->isAssetRequest($request)) {
            return [];
        }

        if ($this->isLegacyNonViewActionRoute($request)) {
            return $this->legacyNonViewActionRedirectHandler->getIncludeFile($request);
        }


        return [];
    }

    public function setCurrentDir($dir) : void
    {
        $this->currentDir = $dir;
    }

    /**
     * Check if it is legacy entry point
     * @param Request $request
     * @return bool
     */
    protected function isRedirectLoop(Request $request): bool
    {
        return strpos($request->getPathInfo(), 'legacy/legacy') !== false;
    }

    /**
     * Check if it is legacy entry point
     * @param Request $request
     * @return bool
     */
    protected function isAssetRequest(Request $request): bool
    {
        return $this->legacyAssetRedirectHandler->isAssetRequest($request);
    }

    /**
     * Check if it is legacy entry point
     * @param Request $request
     * @return bool
     */
    protected function isLegacyEntryPoint(Request $request): bool
    {
        return $this->legacyNonViewActionRedirectHandler->isLegacyEntryPoint($request);
    }

    /**
     * Check if it is legacy entry point
     * @param Request $request
     * @return bool
     */
    protected function isLegacyEntryPointFile(Request $request): bool
    {
        return $this->legacyNonViewActionRedirectHandler->isLegacyEntryPointFile($request);
    }

    /**
     * Check if it is legacy api request
     * @param Request $request
     * @return bool
     */
    protected function isLegacyApi(Request $request): bool
    {
        return $this->legacyApiRedirectHandler->isApiRequest($request);
    }

    /**
     * Check if it is legacy non view request
     * @param Request $request
     * @return bool
     */
    protected function isLegacyNonViewActionRoute(Request $request): bool
    {
        return $this->legacyNonViewActionRedirectHandler->isMatch($request);
    }

    /**
     * Check if it is EntryPoint Path view request
     * @param Request $request
     * @return bool
     */
    protected function isLegacyEntryPointPath(Request $request): bool
    {
        return $this->legacyEntryPointRedirectHandler->isEntryPointRequest($request);
    }

    /**
     * Check if it is EntryPoint Path view request
     * @param Request $request
     * @return array
     */
    protected function isValidLegacyEntryPoint(Request $request): array
    {
        $this->legacyEntryPointRedirectHandler->setCurrentDir($this->currentDir);
        return $this->legacyEntryPointRedirectHandler->isValidEntryPoint($request);
    }


}
