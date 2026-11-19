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

use App\PipelineBoard\LegacyHandler\PipelineBoardHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * CRM-469 — `pipeline-board` process of the SuiteCRM 8 pipeline board page
 * (PipelineBoardHandler).
 *
 * Options, by action (board: front end name of the module, filters: values by field):
 * - load: board, filters → the filters, the columns with their totals and first cards;
 * - cards: board, filters, stage, offset → the next cards of a column;
 * - move: board, id, stage → moves a record to a column (result: moved, denied or
 *   invalid, with its card when moved);
 * - fold: board, stage, folded → keeps a column folded or unfolded for the user.
 */
class PipelineBoardProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'pipeline-board';

    protected const ACTIONS = ['load', 'cards', 'move', 'fold'];

    public function __construct(
        protected PipelineBoardHandler $board,
        protected LoggerInterface $logger
    ) {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     * The module depends on the board: the access to its list and to each record is
     * checked by the board, with the roles and the security groups.
     */
    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)
            || !is_string($options['board'] ?? null)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        try {
            $data = $this->runAction($options);
        } catch (\Throwable $throwable) {
            $this->logger->error('Pipeline board ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = null;
        }

        $done = $data !== null
            && ($options['action'] !== 'move' || $data['result'] === PipelineBoardHandler::MOVED)
            && ($options['action'] !== 'fold' || $data['done']);

        $process->setStatus($done ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($data ?? ['error' => true]);
    }

    protected function runAction(array $options): ?array
    {
        $board = (string)$options['board'];
        $stage = is_scalar($options['stage'] ?? null) ? (string)$options['stage'] : '';

        return match ($options['action']) {
            'load' => $this->board->load($board, $this->filters($options)),
            'cards' => $this->board->more($board, $this->filters($options), $stage, (int)($options['offset'] ?? 0)),
            'move' => $this->board->move($board, is_scalar($options['id'] ?? null) ? (string)$options['id'] : '', $stage),
            'fold' => ['done' => $this->board->fold($board, $stage, !empty($options['folded']))],
        };
    }

    /**
     * @return array<string, string>
     */
    protected function filters(array $options): array
    {
        $filters = [];
        foreach (is_array($options['filters'] ?? null) ? $options['filters'] : [] as $field => $value) {
            if (is_scalar($value)) {
                $filters[(string)$field] = (string)$value;
            }
        }

        return $filters;
    }
}
