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

namespace App\PipelineBoard\Service;

use App\PipelineBoard\Model\BoardDefinition;

/**
 * The pipeline boards of the parameter `module.pipeline_boards`
 * (config/services/module/pipeline_boards.yaml), by front end module name.
 */
class BoardDefinitions
{
    public const DEFAULT_PAGE_SIZE = 25;
    public const MAX_PAGE_SIZE = 100;
    public const DEFAULT_STALE_DAYS = 90;

    /**
     * @var array<string, BoardDefinition>
     */
    protected array $definitions = [];

    /**
     * @param array<string, array<string, mixed>> $pipelineBoards
     */
    public function __construct(array $pipelineBoards)
    {
        foreach ($pipelineBoards as $board => $config) {
            $this->definitions[(string)$board] = $this->build((string)$board, is_array($config) ? $config : []);
        }
    }

    public function get(string $board): ?BoardDefinition
    {
        return $this->definitions[$board] ?? null;
    }

    /**
     * @return array<string, BoardDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    protected function build(string $board, array $config): BoardDefinition
    {
        $module = $this->name($config['module'] ?? '');
        $stageField = $this->name($config['stage_field'] ?? '');
        if ($board === '' || $module === '' || $stageField === '') {
            throw new \InvalidArgumentException(
                sprintf('Pipeline board "%s": "module" and "stage_field" are required', $board)
            );
        }

        $filters = [];
        foreach ((array)($config['filters'] ?? []) as $field) {
            $field = $this->name($field);
            if ($field !== '' && $field !== $stageField && !in_array($field, $filters, true)) {
                $filters[] = $field;
            }
        }

        $pageSize = (int)($config['page_size'] ?? self::DEFAULT_PAGE_SIZE);

        return new BoardDefinition(
            $board,
            $module,
            $stageField,
            $this->name($config['amount_field'] ?? ''),
            $this->name($config['subtitle_field'] ?? ''),
            $filters,
            array_values(array_unique(array_map('strval', (array)($config['closed_stages'] ?? [])))),
            max(0, (int)($config['stale_days'] ?? self::DEFAULT_STALE_DAYS)),
            $pageSize > 0 ? min($pageSize, self::MAX_PAGE_SIZE) : self::DEFAULT_PAGE_SIZE
        );
    }

    /**
     * A module or field name, '' when it is not one (the names go into SQL).
     */
    protected function name(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $value) ? $value : '';
    }
}
