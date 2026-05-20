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


namespace App\Engine\Service\Extensions;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ExtensionAssetCopy implements ExtensionAssetCopyInterface
{
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * InstallExtensionAssets constructor.
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }


    /**
     * @inheritDoc
     */
    public function copyAssets(): void
    {
        $filesystem = new Filesystem();

        $publicPath = $this->projectDir . '/public/';
        $extensionsPath = $this->projectDir . '/extensions/';
        $publicExtensionsPath = $this->projectDir . '/public/extensions';

        $this->cleanPublicExtensions($filesystem, $publicExtensionsPath);

        $this->copyFromPath($extensionsPath, $filesystem, $publicExtensionsPath, '/Resources/public');
        $this->copyFromPath($extensionsPath, $filesystem, $publicExtensionsPath, '/public');
    }

    /**
     * Get list of assets
     * @param $fullPath
     * @param string $publicFolderPath
     * @return SplFileInfo[]
     */
    protected function find($fullPath, string $publicFolderPath): iterable
    {
        if (!is_dir($fullPath)) {
            return [];
        }


        $finder = new Finder();
        $finder->files();


        $finder->in($fullPath . '*' . $publicFolderPath);

        return $finder->getIterator();
    }

    /**
     * @param Filesystem $filesystem
     * @param string $publicExtensionsPath
     */
    protected function cleanPublicExtensions(Filesystem $filesystem, string $publicExtensionsPath): void
    {
        $filesystem->remove($publicExtensionsPath);
        $filesystem->mkdir($publicExtensionsPath);
    }

    /**
     * @param string $extensionsPath
     * @param Filesystem $filesystem
     * @param string $publicExtensionsPath
     * @param string $publicFolderPath
     * @return void
     */
    protected function copyFromPath(
        string $extensionsPath,
        Filesystem $filesystem,
        string $publicExtensionsPath,
        string $publicFolderPath
    ): void {
        try {
            $it = $this->find($extensionsPath, $publicFolderPath);
        } catch (DirectoryNotFoundException $e) {
            $it = null;
        }

        if (empty($it)) {
            return;
        }

        foreach ($it as $file) {
            $path = $file->getPathname();

            $name = str_replace(array($extensionsPath, $publicFolderPath), '', $path);
            $filesystem->copy($path, $publicExtensionsPath . '/' . $name);
        }
    }
}
