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

namespace App\Module\Maps\Service\ProcessHandlers;

use App\Module\Maps\LegacyHandler\MapsHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `maps` process of the map widgets of the SuiteCRM 8 views (MapsHandler).
 *
 * Options, by action:
 * - config: the Google Maps settings (API key, default center, marker images);
 * - options: the modules, units and default distance of the quick radius map;
 * - markers: the markers of a map (record | relate_module, relate_id | quick_address; display_module,
 *   distance, unit_type | list_id);
 * - addToTargetList: listId, module, ids (records of the map).
 */
class MapsProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = MapsHandler::HANDLER_KEY;

    protected const ACTIONS = ['config', 'options', 'markers', 'addToTargetList'];

    public function __construct(protected MapsHandler $maps, protected LoggerInterface $logger)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

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

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        try {
            $data = match ($options['action']) {
                'options' => $this->maps->options(),
                'markers' => $this->maps->markers($options),
                'addToTargetList' => $this->maps->addToTargetList(
                    (string)($options['listId'] ?? ''),
                    (string)($options['module'] ?? ''),
                    is_array($options['ids'] ?? null) ? $options['ids'] : []
                ),
                default => $this->maps->config(),
            };
            $process->setStatus('success');
            $process->setMessages([]);
            $process->setData($data);
        } catch (\Throwable $throwable) {
            $this->logger->error('Maps ' . ($options['action'] ?? '') . ' failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
            $process->setData([]);
        }
    }
}
