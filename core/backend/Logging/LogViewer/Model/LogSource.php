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

namespace App\Logging\LogViewer\Model;

/**
 * A log offered by the log viewer: one file, or a file followed by its rotated files,
 * read as one.
 */
class LogSource
{
    public const PART_SYMFONY = 'symfony';
    public const PART_LEGACY = 'legacy';

    /**
     * @param string $key what the page sends back to read the source (it never sends a path)
     * @param string $part the part of the page the source belongs to
     * @param string $label the name shown
     * @param string[] $files the paths of the files, the newest first
     * @param bool $main whether the source is the one of its part selected at first
     */
    public function __construct(
        public readonly string $key,
        public readonly string $part,
        public readonly string $label,
        public readonly array $files,
        public readonly bool $main = false
    ) {
    }
}
