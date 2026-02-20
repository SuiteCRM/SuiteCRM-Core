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

namespace App\AsyncTask\Service\Router;

use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

class AsyncTaskRouter implements AsyncTaskRouterInterface
{
    public function __construct(
        protected array $transportMapConfig,
        protected string $transportMapEnv,
        #[Target('messengerLogger')]
        protected LoggerInterface $logger
    ) {
    }

    public function getTransports(string $module, string $taskKey): ?array
    {
        $this->logger->debug('AsyncTaskRouter: resolving transport.', [
            'module' => $module,
            'task_key' => $taskKey,
        ]);

        $envTransports = $this->getEnvTransports() ?? [];
        $transports = $this->getTaskTransports($module, $taskKey, $envTransports);

        if ($transports !== null) {
            $this->logger->debug('AsyncTaskRouter: transport resolved from env.', [
                'module' => $module,
                'task_key' => $taskKey,
                'transports' => $transports,
            ]);

            return $transports;
        }

        $configTransports = $this->getConfigTransports() ?? [];
        $transports = $this->getTaskTransports($module, $taskKey, $configTransports);

        if ($transports !== null) {
            $this->logger->debug('AsyncTaskRouter: transport resolved from config.', [
                'module' => $module,
                'task_key' => $taskKey,
                'transports' => $transports,
            ]);

            return $transports;
        }

        $this->logger->debug('AsyncTaskRouter: no transport found, falling back to Messenger default routing.', [
            'module' => $module,
            'task_key' => $taskKey,
        ]);

        return null;
    }

    /**
     * @return array|null
     */
    protected function getEnvTransports(): ?array
    {
        try {
            $transportsEnv = $this->transportMapEnv ?? '{}';
            $transports = json_decode($transportsEnv, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error('Failed to decode transport map from environment: ' . $e->getMessage(), ['exception' => $e]);
            return null;
        }

        if (!is_array($transports)) {
            return null;
        }

        return $transports;
    }

    /**
     * @return array|null
     */
    protected function getConfigTransports(): ?array
    {
        $transports = $this->transportMapConfig;

        if (!is_array($transports)) {
            return null;
        }

        return $transports;
    }

    protected function getTaskTransports(string $module, string $taskKey, array $map): ?array
    {
        if (empty($map)) {
            return null;
        }

        $modulesMap = $map['modules'] ?? [];
        $moduleTransports = $modulesMap[$module] ?? [];
        $transports = $moduleTransports[$taskKey] ?? '';

        if (!empty($transports)) {
            $this->logger->debug('AsyncTaskRouter: matched module-specific route.', [
                'module' => $module,
                'task_key' => $taskKey,
                'transports' => $transports,
            ]);

            return $this->normalizeTransports($transports);
        }

        $defaultMap = $map['default'] ?? [];
        $transports = $defaultMap[$taskKey] ?? '';

        if (!empty($transports)) {
            $this->logger->debug('AsyncTaskRouter: matched default route.', [
                'module' => $module,
                'task_key' => $taskKey,
                'transports' => $transports,
            ]);

            return $this->normalizeTransports($transports);
        }

        return null;
    }

    /**
     * @param mixed $transports
     * @return array|null
     */
    protected function normalizeTransports(mixed $transports): ?array
    {
        if (is_string($transports)) {
            return [$transports];
        }

        if (is_array($transports)) {
            return $transports;
        }

        return null;
    }
}
