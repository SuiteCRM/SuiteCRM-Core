<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

use App\EntryPoint\LegacyHandler\EntryPointHandler;
use Symfony\Component\HttpFoundation\Request;

class LegacyEntryPointRedirectHandler extends LegacyRedirectHandler
{
    private array $legacyEntrypointPaths;
    private EntryPointHandler $entryPointHandler;
    private string $currentDir;

    /**
     * LegacyEntryPointRedirectHandler constructor.
     * @param array $legacyEntrypointPaths
     * @param String $legacyPath
     */
    public function __construct(EntryPointHandler $entryPointHandler, array $legacyEntrypointPaths, string $legacyPath)
    {
        parent::__construct($legacyPath);
        $this->entryPointHandler = $entryPointHandler;
        $this->legacyEntrypointPaths = $legacyEntrypointPaths;
    }

    /**
     * Check if the given $request is a legacy entryPoint request
     *
     * @param Request $request
     * @return bool
     */
    public function isEntryPointRequest(Request $request): bool
    {
        return $this->inPathList($request, array_keys($this->legacyEntrypointPaths));
    }

    public function setCurrentDir(string $dir): void
    {
        $this->currentDir = $dir;
    }

    /**
     * Check if the given $request is a valid legacy entryPoint
     *
     * @param Request $request
     * @return array
     */
    public function isValidEntryPoint(Request $request): array
    {
        $entryPoint = $this->getEntryPoint($request);
        if (!empty($entryPoint)) {
            $this->entryPointHandler->setProjectDir($this->currentDir);
            return $this->entryPointHandler->validateEntryPoint($entryPoint['name']);
        }

        return ['valid' => false];
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

        foreach ($this->legacyEntrypointPaths as $path => $replace) {
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
        $entryPoint = $this->getEntryPoint($request);
        if (!empty($entryPoint)) {

            $base = $_SERVER['BASE'] ?? $_SERVER['REDIRECT_BASE'] ?? '';

            $scriptName = $base . '/legacy/' . $entryPoint['file'];
            $requestUri = $base . '/legacy/' . $entryPoint['file'] . $entryPoint['params'];

            $_REQUEST['entryPoint'] = $entryPoint['name'];

            $info['dir'] = '';
            $info['file'] = $entryPoint['file'];
            $info['script-name'] = $scriptName;
            $info['request-uri'] = $requestUri;
            $info['access'] = true;

            return $info;
        }

        return [
            'dir' => '',
            'file' => './index.php',
            'access' => false
        ];
    }

    protected function getEntryPoint(Request $request): array
    {
        foreach ($this->legacyEntrypointPaths as $path => $file) {
            if ($this->inPath($request, $path)) {
                $epCheck = explode('/' . $path . '/', $request->getRequestUri());
                if (!empty($epCheck[1])) {
                    $entryPoint = explode('?', $epCheck[1]);

                    return [
                        'name' => $entryPoint[0],
                        'params' => !empty($entryPoint[1]) ? '?' . $entryPoint[1] : '',
                        'path' => $path,
                        'file' => $file,
                    ];
                }
            }
        }

        return [];
    }

}
