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

namespace App\Module\Currencies\Service\ProcessHandlers;

use App\Module\Currencies\LegacyHandler\CurrenciesHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `currencies` process of the Currencies view of the administration (CurrenciesHandler). Admins
 * only, as the legacy page. Every action returns the view data.
 *
 * Options, by action:
 * - load;
 * - save: id (empty for a new currency), name, iso4217, symbol, conversion_rate, status;
 * - delete: id;
 * - merge: ids (the currencies to merge), mergeTo.
 */
class CurrenciesProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = CurrenciesHandler::HANDLER_KEY;

    protected const ACTIONS = ['load', 'save', 'delete', 'merge'];

    public function __construct(protected CurrenciesHandler $currencies, protected LoggerInterface $logger)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_ADMIN';
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
                'save' => $this->currencies->save((string)($options['id'] ?? ''), $options),
                'delete' => $this->currencies->delete((string)($options['id'] ?? '')),
                'merge' => $this->currencies->merge(
                    is_array($options['ids'] ?? null) ? $options['ids'] : [],
                    (string)($options['mergeTo'] ?? '')
                ),
                default => $this->currencies->load(),
            };
            $process->setStatus('success');
            $process->setMessages([]);
            $process->setData($data);
        } catch (\InvalidArgumentException $exception) {
            $process->setStatus('error');
            $process->setMessages([$exception->getMessage()]);
            $process->setData([]);
        } catch (\Throwable $throwable) {
            $this->logger->error('Currencies ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
            $process->setData([]);
        }
    }
}
