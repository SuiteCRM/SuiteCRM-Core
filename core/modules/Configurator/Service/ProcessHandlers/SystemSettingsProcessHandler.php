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

namespace App\Module\Configurator\Service\ProcessHandlers;

use App\Module\Configurator\LegacyHandler\SystemSettingsHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `system-settings` process of the System Settings view
 * (SystemSettingsHandler). Admins only, as the legacy page. Every action returns the view data.
 *
 * Options, by action:
 * - load;
 * - save: values (form field => value), logo (optional, {content: base64 image}). On error,
 *   the message is the label key of the error (Configurator strings, or the application ones).
 */
class SystemSettingsProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = SystemSettingsHandler::HANDLER_KEY;

    protected const ACTIONS = ['load', 'save'];

    public function __construct(protected SystemSettingsHandler $settings, protected LoggerInterface $logger)
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
        $error = null;

        try {
            if ($options['action'] === 'save') {
                $error = $this->settings->save(
                    is_array($options['values'] ?? null) ? $options['values'] : [],
                    is_array($options['logo'] ?? null) ? $options['logo'] : null
                );
            }
            $data = $this->settings->load();
        } catch (\Throwable $throwable) {
            $this->logger->error('System Settings ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = [];
            $error = 'LBL_ACTION_ERROR';
        }

        $process->setStatus($error === null && !empty($data) ? 'success' : 'error');
        $process->setMessages($error !== null ? [$error] : []);
        $process->setData($data);
    }
}
