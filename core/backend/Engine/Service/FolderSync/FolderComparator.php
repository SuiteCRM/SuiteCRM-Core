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
use Symfony\Component\Finder\Finder;

/**
 * Class FolderCompare
 * @package App\Engine\Service\FolderSync
 */
class FolderComparator implements FolderComparatorInterface
{
    /**
     * @var string[]
     */
    protected $toKeep = [];

    /**
     * @var bool[]
     */
    protected $toKeepKeys = [];

    /**
     * @var string[]
     */
    protected $toKeepIgnore = [];

    /**
     * @var bool[]
     */
    protected $toKeepIgnoreKeys = [];

    /**
     * @var string[]
     */
    protected $toExpand = [];

    /**
     * @var bool[]
     */
    protected $toExpandKeys;

    /**
     * @var string[]
     */
    protected $toReAdd = [];

    /**
     * @var bool[]
     */
    protected $toReAddKeys;

    /**
     * FolderCompare constructor.
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function run(string $originPath, string $destinationPath): array
    {
        $manifest = [];
        $this->build($originPath, $destinationPath, $manifest);

        return $manifest;
    }

    /**
     * @param string $originPath
     * @param string $destinationPath
     * @param array $manifest
     * @param string $basePath
     */
    protected function build(
        string $originPath,
        string $destinationPath,
        array &$manifest,
        string $basePath = ''
    ): void {
        $this->addFolderFiles($originPath, $destinationPath, $manifest, $basePath);

        $this->addFolderDirectories($originPath, $destinationPath, $manifest, $basePath);

        $this->expandFolder($originPath, $destinationPath, $manifest, $basePath);
    }

    /**
     * Add folder files
     * @param string $originPath
     * @param string $destinationPath
     * @param array $manifest
     * @param string $basePath
     */
    protected function addFolderFiles(
        string $originPath,
        string $destinationPath,
        array &$manifest,
        string $basePath = ''
    ): void {
        $origin = $this->newFinder($originPath)->files();
        $destination = $this->newDestinationFinder($destinationPath);
        $destination->files();

        if (!$origin->hasResults()) {
            if ($destination->hasResults()) {
                $this->addEntriesToRemove($destination, $manifest, $basePath);
            }

            return;
        }

        $this->addEntriesToCopy($origin, $manifest, $basePath);

        if (!$destination->hasResults()) {
            return;
        }

        $this->addEntriesToRemove($destination, $manifest, $basePath);
    }

    /**
     * Get new finder
     * @param string $filePath
     * @return Finder
     */
    protected function newFinder(string $filePath): Finder
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);
        $finder->depth('== 0')->in($filePath);

        return $finder;
    }

    /**
     * Add entries to copy:
     * - All paths on package and not marked as to keep
     * - All paths on package and not to expand (drill down)
     * @param Finder $origin
     * @param array $manifest
     * @param string $basePath
     */
    protected function addEntriesToCopy(Finder $origin, array &$manifest, $basePath = ''): void
    {
        foreach ($origin as $entry) {
            $path = $entry->getRelativePathname();
            $pathFromBase = $this->buildPath($basePath, $path);

            if ($this->isToKeep($pathFromBase) || $this->isToExpand($pathFromBase)) {
                continue;
            }

            $manifest[$pathFromBase] = $this->newManifestEntry($pathFromBase, 'copy', $entry->getType());
        }
    }

    /**
     * Create new manifest entry
     * @param string $filePath
     * @param string $action
     * @param string $type
     * @return FolderSyncManifestEntry
     */
    protected function newManifestEntry(
        string $filePath,
        string $action,
        string $type = 'file'
    ): FolderSyncManifestEntry {
        $entry = new FolderSyncManifestEntry();
        $entry->path = $filePath;
        $entry->action = $action;
        $entry->type = $type;

        return $entry;
    }

    /**
     * Get new destination finder with ingores
     * @param string $filePath
     * @return Finder
     */
    protected function newDestinationFinder(string $filePath): Finder
    {
        $finder = $this->newFinder($filePath);
        $finder->ignoreVCS(true);

        if ((new Filesystem())->exists($filePath . '/.gitignore')) {
            $finder->ignoreVCSIgnored(true);
        }

        return $finder;
    }

    /**
     * Do diff. Mark to delete:
     * - All paths not on package and not marked as to keep
     * - All paths not on package and that parent is not marked as to keep
     * - All paths not on package and not to expand (drill down)
     * @param Finder $destination
     * @param array $manifest
     * @param string $basePath
     */
    protected function addEntriesToRemove(Finder $destination, array &$manifest, $basePath = ''): void
    {
        foreach ($destination as $entry) {

            $path = $entry->getRelativePathname();
            $pathFromBase = $this->buildPath($basePath, $path);

            if ($this->isToExpand($pathFromBase)) {
                continue;
            }

            if ($this->isToKeepPath($basePath, $pathFromBase)) {
                continue;
            }

            if (empty($manifest[$pathFromBase])) {
                $manifest[$pathFromBase] = $this->newManifestEntry($pathFromBase, 'delete', $entry->getType());
            }
        }
    }

    /**
     * Get Files to keep even if not on package
     * @return array
     */
    public function getToKeep(): array
    {
        return $this->toKeep;
    }

    /**
     * Set files to keep
     * @param array $toKeep
     */
    public function setToKeep(array $toKeep): void
    {
        $this->toKeep = $toKeep;
        $this->toKeepKeys = [];

        foreach ($this->toKeep as $item) {
            $this->toKeepKeys[$item] = true;
        }
    }

    /**
     * Get Files to keep even if not on package
     * @return array
     */
    protected function getToKeepKeys(): array
    {
        return $this->toKeepKeys;
    }


    /**
     * Get Files to always look at origin, even if to keep
     * @return array
     */
    public function getToKeepIgnore(): array
    {
        return $this->toKeepIgnore;
    }

    /**
     * Set to keepIgnore
     * @param array $toKeepIgnore
     */
    public function setToKeepIgnore(array $toKeepIgnore): void
    {
        $this->toKeepIgnore = $toKeepIgnore;
        $this->toKeepIgnoreKeys = [];

        foreach ($this->toKeepIgnore as $item) {
            $this->toKeepIgnoreKeys[$item] = true;
        }
    }

    /**
     * Get Files to always look at origin, even if to keep
     * @return array
     */
    protected function getToKeepIgnoreKeys(): array
    {
        return $this->toKeepIgnoreKeys;
    }

    /**
     * Set paths to re-add
     * @param array $toReAdd
     */
    public function setToReAdd(array $toReAdd): void
    {
        $this->toReAdd = $toReAdd;
        $this->toReAddKeys = [];

        foreach ($this->toReAdd as $item) {
            $this->toReAddKeys[$item] = true;
        }
    }

    /**
     * Get paths to re-add
     * @return array
     */
    public function getToReAdd(): array
    {
        return $this->toReAdd;
    }

    /**
     * Get paths to re-add keys
     * @return array
     */
    public function getToReAddKeys(): array
    {
        return $this->toReAddKeys;
    }

    /**
     * Add folder directories
     * @param string $originPath
     * @param string $destinationPath
     * @param array $manifest
     * @param string $basePath
     */
    protected function addFolderDirectories(
        string $originPath,
        string $destinationPath,
        array &$manifest,
        string $basePath = ''
    ): void {
        $origin = $this->newFinder($originPath);
        $origin->directories();
        $destination = $this->newDestinationFinder($destinationPath);
        $destination->directories();

        if (!$origin->hasResults()) {
            if ($destination->hasResults()) {
                $this->addEntriesToRemove($destination, $manifest, $basePath);
            }

            return;
        }

        $this->addEntriesToCopy($origin, $manifest, $basePath);

        if (!$destination->hasResults()) {
            return;
        }

        $this->addEntriesToRemove($destination, $manifest, $basePath);
    }

    /**
     * Set paths to expand
     * @param array $toExpand
     * @return void
     */
    public function setPathsToExpand(array $toExpand): void
    {
        $this->toExpand = $toExpand;
        $this->toExpandKeys = [];

        foreach ($this->toExpand as $item) {
            $this->toExpandKeys[$item] = true;
        }
    }

    /**
     * Get Paths drill down to
     * @return array
     */
    public function getPathsToExpand(): array
    {
        return $this->toExpand;
    }

    /**
     * Get Paths drill down to
     * @return array
     */
    public function getPathsToExpandKeys(): array
    {
        return $this->toExpandKeys;
    }

    /**
     * Drill down and compare sub folder
     * @param string $originPath
     * @param string $destinationPath
     * @param array $manifest
     * @param string $basePath
     */
    protected function expandFolder(
        string $originPath,
        string $destinationPath,
        array &$manifest,
        string $basePath = ''
    ): void {
        $origin = $this->newFinder($originPath);
        $origin->directories();

        if (!$origin->hasResults()) {
            return;
        }

        foreach ($origin as $dir) {
            $dirPath = $dir->getRelativePathname();
            $pathFromBase = $this->buildPath($basePath, $dirPath);

            if (!$this->isToExpand($pathFromBase)) {
                continue;
            }

            $subOriginPath = $this->buildPath($originPath, $dirPath);
            $subDestinationPath = $this->buildPath($destinationPath, $dirPath);

            $this->build($subOriginPath, $subDestinationPath, $manifest, $pathFromBase);
        }
    }

    /**
     * Build path
     * @param string $basePath
     * @param string $subPath
     * @return string
     */
    protected function buildPath(string $basePath, string $subPath): string
    {
        if (empty($basePath)) {
            return $subPath;
        }

        return implode('/', [$basePath, $subPath]);
    }

    /**
     * Check if path is to keep
     * @param string $path
     * @return bool
     */
    protected function isToKeep(string $path): bool
    {
        return !empty($this->getToKeepKeys()[$path]);
    }

    /**
     * Check if path is to ignore toKeep
     * @param string $path
     * @return bool
     */
    protected function isToKeepIgnore(string $path): bool
    {
        return !empty($this->getToKeepIgnoreKeys()[$path]);
    }

    /**
     * Check if path is to expand
     * @param string $path
     * @return bool
     */
    protected function isToExpand(string $path): bool
    {
        return !empty($this->getPathsToExpandKeys()[$path]);
    }

    /**
     * @param $basePath
     * @param string $pathFromBase
     * @return bool
     */
    protected function isToKeepPath($basePath, string $pathFromBase): bool
    {
        if ($this->isToKeepIgnore($pathFromBase)) {
            return false;
        }

        return $this->isToKeep($pathFromBase) || $this->isToKeep($basePath);
    }
}
