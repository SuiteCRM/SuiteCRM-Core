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

class LegacyApiRedirectHandler extends LegacyRedirectHandler
{
    /**
     * @var string[]
     */
    private $legacyApiPaths;

    /**
     * @var array
     */
    private $legacyApiPathFiles;

    /**
     * LegacyApiRedirectHandler constructor.
     * @param array $legacyApiPaths
     * @param String $legacyPath
     * @param array $legacyApiPathFiles
     */
    public function __construct(array $legacyApiPaths, string $legacyPath, array $legacyApiPathFiles)
    {
        parent::__construct($legacyPath);
        $this->legacyApiPaths = $legacyApiPaths;
        $this->legacyApiPathFiles = $legacyApiPathFiles;
    }

    /**
     * Check if the given $request is a legacy api request
     *
     * @param Request $request
     * @return bool
     */
    public function isApiRequest(Request $request): bool
    {
        return $this->inPathList($request, array_keys($this->legacyApiPaths));
    }

    /**
     * Convert given $request route
     *
     * @param Request $request
     * @return string
     */
    public function convert(Request $request): string
    {
        $legacyPath = parent::convert($request);

        foreach ($this->legacyApiPaths as $path => $replace) {
            if ($this->inPath($request, $path)) {
                return str_replace($path, $replace, $legacyPath);
            }
        }

        return $legacyPath;
    }

    /**
     * Convert given $request route
     *
     * @param Request $request
     * @return array
     */
    public function getIncludeFile(Request $request): array
    {

        foreach ($this->legacyApiPathFiles as $path => $info) {
            if ($this->inPath($request, $path)) {

                $base = $_SERVER['BASE'] ?? $_SERVER['REDIRECT_BASE'] ?? '';

                $scriptName = $base . '/legacy/' . $info['dir'] . '/' . $info['file'];

                if (!empty($base)) {
                    $requestUri = str_replace($base, $base . '/legacy', $_SERVER['REQUEST_URI']);
                } else {
                    $requestUri = '/legacy' . $_SERVER['REQUEST_URI'];
                }

                $info['script-name'] = $scriptName;
                $info['request-uri'] = $requestUri;
                $info['access'] = true;

                return $info;
            }
        }

        return [
            'dir' => '',
            'file' => './index.php',
            'access' => true
        ];
    }
}
