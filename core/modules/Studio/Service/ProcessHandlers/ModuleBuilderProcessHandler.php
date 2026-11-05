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

namespace App\Module\Studio\Service\ProcessHandlers;

use App\Module\Studio\LegacyHandler\ModuleBuilderPackages;
use App\Module\Studio\LegacyHandler\Studio;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `Module-builder` process of the Module Builder page (ModuleBuilderPackages), for
 * the admins as the legacy Module Builder. Options, by action:
 * - packages → the packages and their modules, the types of a module, labels and help;
 * - package: package ('' for a new one) → its properties and modules;
 * - savePackage: original ('' for a new one), duplicate, values (name, key, author, description,
 *   readme) → its name, or error;
 * - deletePackage: package;
 * - publishPackage: package; exportPackage: package, values (author, description, readme) → the
 *   zip (file name, content in base64);
 * - module: package, module ('' for a new one) → its properties;
 * - saveModule: package, original ('' for a new one), duplicate, values (name, label, type,
 *   importable, hasTab) → its name, or error;
 * - deleteModule: package, module.
 */
class ModuleBuilderProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = ModuleBuilderPackages::HANDLER_KEY;

    protected const ACTIONS = [
        'packages', 'package', 'savePackage', 'deletePackage', 'publishPackage', 'exportPackage', 'module', 'saveModule', 'deleteModule',
    ];

    public function __construct(protected ModuleBuilderPackages $packages, protected LoggerInterface $logger)
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
        foreach (['package', 'module', 'original'] as $key) {
            if (!preg_match('/^\w*$/', (string)($options[$key] ?? ''))) {
                throw new \InvalidArgumentException('Process options are not defined');
            }
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $package = (string)($options['package'] ?? '');
        $module = (string)($options['module'] ?? '');
        $original = (string)($options['original'] ?? '');
        $values = is_array($options['values'] ?? null) ? $options['values'] : [];
        $duplicate = !empty($options['duplicate']);

        try {
            $data = match ($options['action']) {
                'packages' => $this->packages->getPackages(),
                'package' => $this->packages->getPackage($package),
                'savePackage' => $this->packages->savePackage($original, $values, $duplicate),
                'deletePackage' => $this->packages->deletePackage($package),
                'publishPackage' => $this->packages->downloadPackage($package, false),
                'exportPackage' => $this->packages->downloadPackage($package, true, $values),
                'module' => $this->packages->getModule($package, $module),
                'saveModule' => $this->packages->saveModule($package, $original, $values, $duplicate),
                'deleteModule' => $this->packages->deleteModule($package, $module),
            };
            $success = empty($data['error']);
        } catch (\Throwable $throwable) {
            $this->logger->error('Module Builder: ' . $throwable->getMessage(), ['exception' => $throwable]);
            $data = [];
            $success = false;
        }

        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages($success ? [] : ['LBL_ACTION_ERROR']);
        $process->setData($data);
    }
}
