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

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class LegacyRedirectHandler
{
    /**
     * @var String
     */
    protected $legacyPath;

    /**
     * LegacyRedirectHandler constructor.
     * @param String $legacyPath
     */
    public function __construct(string $legacyPath)
    {
        $this->legacyPath = $legacyPath;
    }

    /**
     * Convert given $request route
     *
     * @param Request $request
     * @return string
     */
    public function convert(Request $request): string
    {

        if (null !== $queryString = $request->getQueryString()) {
            $queryString = '?' . $queryString;
        }

        $baseUrl = $request->getBaseUrl();

        if ($request->getPathInfo() !== '/') {
            $baseUrl .= $this->legacyPath . $request->getPathInfo() . $queryString;

            return $baseUrl;
        }

        if (strpos($baseUrl, '.php') !== false) {
            $baseUrl = str_replace($request->getBasePath(), $request->getBasePath() . $this->legacyPath, $baseUrl);
        } else {
            $baseUrl .= $this->legacyPath . '/index.php';
        }

        return $baseUrl . $queryString;
    }

    /**
     * Convert given $request route
     *
     * @param Request $request
     * @return array
     */
    public function getIncludeFile(Request $request): array
    {
        $baseUrl = $request->getPathInfo();

        $baseUrl = substr($baseUrl, 1);

        if (strpos($baseUrl, '../') !== false) {
            throw new InvalidArgumentException('invalid path');
        }

        if (strpos($baseUrl, '.php') === false) {
            $baseUrl .= 'index.php';
        }

        return [
            'dir' => '',
            'file' => $baseUrl,
            'access' => true
        ];
    }

    /**
     * Check if given request falls into one of the given $paths
     *
     * @param Request $request
     * @param array $paths
     * @return bool
     */
    protected function inPathList(Request $request, array $paths): bool
    {
        if (empty($request->getPathInfo()) || $request->getPathInfo() === '/') {
            return false;
        }

        foreach ($paths as $path) {
            if ($this->inPath($request, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request falls into given path
     *
     * @param Request $request
     * @param $path
     * @return bool
     */
    protected function inPath(Request $request, $path): bool
    {
        return strpos($request->getPathInfo(), '/' . $path) === 0;
    }
}
