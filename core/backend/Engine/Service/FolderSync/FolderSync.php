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

namespace App\Engine\Service\FolderSync;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FolderSync
 * @package App\Engine\Service\FolderSync
 */
class FolderSync
{

    /**
     * Move Files
     * @param string $origin
     * @param string $destination
     * @param FolderSyncManifestEntry[] $manifest
     */
    public function run(string $origin, string $destination, array $manifest): void
    {
        if (!$origin || !$destination || empty($manifest)) {
            return;
        }

        $filesystem = new Filesystem();

        foreach ($manifest as $item) {
            $path = $item->path ?? '';

            if ($item->action === 'delete') {
                $this->remove($destination, $path, $filesystem);
                continue;
            }

            if ($item->type === 'dir') {

                $this->copyDir($origin, $destination, $path, $filesystem);
            } else {

                $this->copyFile($origin, $destination, $path, $filesystem);
            }
        }
    }

    /**
     * Has sufficient permissions
     * @param string $origin
     * @param string $path
     * @return array
     */
    public function checkOriginPermissions(string $origin, string $path): array
    {
        $fileOrigin = $this->buildPath([$origin, $path]);

        return [
            'path' => $fileOrigin,
            'allowed' => is_readable($fileOrigin)
        ];
    }

    /**
     * Has sufficient permissions
     * @param string $destination
     * @param string $path
     * @return array
     */
    public function checkDestinationPermissions(string $destination, string $path): array
    {
        $fileDestination = $this->buildPath([$destination, $path]);

        if (is_writable($fileDestination)) {
            return [
                'path' => $fileDestination,
                'allowed' => true
            ];
        }

        if (!file_exists($fileDestination)) {
            return [
                'path' => $fileDestination,
                'allowed' => true
            ];
        }

        return [
            'path' => $fileDestination,
            'allowed' => false
        ];
    }

    /**
     * Remove file
     * @param string $base
     * @param string $path
     * @param Filesystem $filesystem
     */
    protected function remove(string $base, string $path, Filesystem $filesystem): void
    {
        if (!$path) {
            return;
        }

        $fullPath = $this->buildPath([$base, $path]);

        if (empty($fullPath)) {
            return;
        }

        if (!$filesystem->exists($fullPath)) {
            return;
        }

        $filesystem->remove($fullPath);
    }

    /**
     * Build path
     * @param array $parts
     * @return string
     */
    protected function buildPath(array $parts): string
    {
        if (empty($parts)) {
            return '';
        }

        return implode('/', $parts);
    }

    /**
     * Copy Directory
     * @param string $origin
     * @param string $destination
     * @param string $path
     * @param Filesystem $filesystem
     */
    protected function copyDir(string $origin, string $destination, string $path, Filesystem $filesystem): void
    {
        if (!$path) {
            return;
        }

        $fileOrigin = $this->buildPath([$origin, $path]);
        $fileDestination = $this->buildPath([$destination, $path]);

        if (empty($fileOrigin) || empty($fileDestination)) {
            return;
        }

        if (!$filesystem->exists($fileOrigin)) {
            return;
        }

        $filesystem->mirror($fileOrigin, $fileDestination, null, ['override' => true, 'delete' => true]);
    }

    /**
     * Copy file
     * @param string $origin
     * @param string $destination
     * @param string $path
     * @param Filesystem $filesystem
     */
    protected function copyFile(string $origin, string $destination, string $path, Filesystem $filesystem): void
    {
        if (!$path) {
            return;
        }

        $fileOrigin = $this->buildPath([$origin, $path]);
        $fileDestination = $this->buildPath([$destination, $path]);

        if (empty($fileOrigin) || empty($fileDestination)) {
            return;
        }

        if (!$filesystem->exists($fileOrigin)) {
            return;
        }

        $filesystem->copy($fileOrigin, $fileDestination, true);
    }
}
