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

interface TemporaryFileManagerInterface
{
    /**
     * Returns the absolute path for a working file.
     * Creates parent directories if they do not exist.
     *
     * @param string $context Subsystem name (e.g. 'csv-export', 'pdf-export', 'thumbnail')
     * @param string $fileName File name within the context
     * @param string|null $parentId Optional scoping ID (e.g. task ID) — adds a subdirectory level
     * @return string Absolute filesystem path
     */
    public function getWorkingFilePath(string $context, string $fileName, ?string $parentId = null): string;

    /**
     * Opens a stream handle for a working file.
     * Creates the file and parent directories if needed.
     *
     * @param string $context Subsystem name
     * @param string $fileName File name within the context
     * @param string $mode PHP fopen() mode (default 'a' for append)
     * @param string|null $parentId Optional scoping ID
     * @return resource Stream handle — caller is responsible for fclose()
     * @throws \RuntimeException If the file cannot be opened
     */
    public function openStream(string $context, string $fileName, string $mode = 'a', ?string $parentId = null);

    /**
     * Checks if a working file exists on disk.
     *
     * @param string $context Subsystem name
     * @param string $fileName File name within the context
     * @param string|null $parentId Optional scoping ID
     * @return bool
     */
    public function exists(string $context, string $fileName, ?string $parentId = null): bool;

    /**
     * Removes working files for a context and optional parent scope.
     *
     * With parentId: removes {basePath}/{context}/{parentId}/ and all contents.
     * Without parentId: removes {basePath}/{context}/ and all contents.
     *
     * Safe to call multiple times — no error if the directory does not exist.
     *
     * @param string $context Subsystem name
     * @param string|null $parentId Optional scoping ID
     */
    public function cleanup(string $context, ?string $parentId = null): void;

    /**
     * Removes all working files across all contexts that are older than the given age.
     * Safety net for orphaned files from failed/crashed tasks.
     *
     * @param int $maxAgeSeconds Maximum file age in seconds (default 86400 = 24 hours)
     * @return int Number of files removed
     */
    public function cleanupExpired(int $maxAgeSeconds = 86400): int;
}
