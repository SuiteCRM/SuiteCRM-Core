<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\MediaObjects\Service\TemporaryFile;

use Psr\Log\LoggerInterface;

class DefaultTemporaryFileManager implements TemporaryFileManagerInterface
{
    protected string $resolvedBaseDir;

    public function __construct(
        protected string $temporaryFileBaseDir,
        protected string $projectDir,
        protected LoggerInterface $logger
    ) {
        $this->resolvedBaseDir = $this->resolveBaseDir($temporaryFileBaseDir);
    }

    /**
     * Resolve the base directory path.
     * - Empty string falls back to {projectDir}/tmp
     * - Relative paths are resolved against the project directory
     * - Absolute paths are used as-is
     */
    protected function resolveBaseDir(string $baseDir): string
    {
        $baseDir = trim($baseDir);

        if ($baseDir === '') {
            return $this->projectDir . DIRECTORY_SEPARATOR . 'tmp';
        }

        if (!str_starts_with($baseDir, DIRECTORY_SEPARATOR)) {
            return $this->projectDir . DIRECTORY_SEPARATOR . $baseDir;
        }

        return $baseDir;
    }

    /**
     * @inheritDoc
     */
    public function getWorkingFilePath(string $context, string $fileName, ?string $parentId = null): string
    {
        $dir = $this->buildDirectoryPath($context, $parentId);
        $this->ensureDirectoryExists($dir);

        return $dir . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @inheritDoc
     */
    public function openStream(string $context, string $fileName, string $mode = 'a', ?string $parentId = null)
    {
        $path = $this->getWorkingFilePath($context, $fileName, $parentId);

        $handle = @fopen($path, $mode);

        if ($handle === false) {
            throw new \RuntimeException(
                sprintf('Could not open working file "%s" with mode "%s"', $path, $mode)
            );
        }

        return $handle;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $context, string $fileName, ?string $parentId = null): bool
    {
        $dir = $this->buildDirectoryPath($context, $parentId);
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;

        return file_exists($path) && is_file($path);
    }

    /**
     * @inheritDoc
     */
    public function cleanup(string $context, ?string $parentId = null): void
    {
        $dir = $this->buildDirectoryPath($context, $parentId);

        if (!is_dir($dir)) {
            return;
        }

        $this->removeDirectory($dir);
    }

    /**
     * @inheritDoc
     */
    public function cleanupExpired(int $maxAgeSeconds = 86400): int
    {
        $baseDir = rtrim($this->resolvedBaseDir, DIRECTORY_SEPARATOR);

        if (!is_dir($baseDir)) {
            return 0;
        }

        $cutoff = time() - $maxAgeSeconds;
        $removed = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            if ($fileInfo->getMTime() >= $cutoff) {
                continue;
            }

            if (@unlink($fileInfo->getPathname())) {
                $removed++;
            } else {
                $this->logger->warning('Could not remove expired working file: ' . $fileInfo->getPathname());
            }
        }

        $this->removeEmptyDirectories($baseDir);

        return $removed;
    }

    /**
     * Build the directory path for a context and optional parent.
     */
    protected function buildDirectoryPath(string $context, ?string $parentId = null): string
    {
        $baseDir = rtrim($this->resolvedBaseDir, DIRECTORY_SEPARATOR);
        $path = $baseDir . DIRECTORY_SEPARATOR . $context;

        if ($parentId !== null && $parentId !== '') {
            $path .= DIRECTORY_SEPARATOR . $parentId;
        }

        return $path;
    }

    /**
     * Create the directory if it does not exist.
     */
    protected function ensureDirectoryExists(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Could not create directory "%s"', $dir));
        }
    }

    /**
     * Recursively remove a directory and all its contents.
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                @rmdir($fileInfo->getPathname());
            } else {
                @unlink($fileInfo->getPathname());
            }
        }

        @rmdir($dir);
    }

    /**
     * Remove empty directories left behind after file cleanup.
     * Does not remove the base directory itself.
     */
    protected function removeEmptyDirectories(string $baseDir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isDir()) {
                continue;
            }

            $dirPath = $fileInfo->getPathname();

            // Only remove if empty (scandir returns . and .. for empty dirs)
            $contents = @scandir($dirPath);
            if ($contents !== false && count($contents) === 2) {
                @rmdir($dirPath);
            }
        }
    }
}
