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

namespace App\Logging\LogViewer\Source;

/**
 * Finds the log files of a directory, and names them for the page.
 */
class LogFileFinder
{
    public function __construct(protected string $projectDir)
    {
    }

    /**
     * The readable files of a directory (not of its sub-directories) whose name matches.
     *
     * @param string $pattern regular expression on the file name
     * @return string[] paths, sorted by name
     */
    public function find(string $directory, string $pattern = '/\.log$/i'): array
    {
        $directory = rtrim($directory, '/');
        if ($directory === '' || !is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (scandir($directory) ?: [] as $name) {
            $path = $directory . '/' . $name;
            if (preg_match($pattern, $name) === 1 && is_file($path) && is_readable($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * The path of a file as shown: from the folder of the application when it is in it.
     */
    public function getLabel(string $path): string
    {
        $path = realpath($path) ?: $path;
        $root = rtrim(realpath($this->projectDir) ?: $this->projectDir, '/') . '/';

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
