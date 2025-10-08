<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Process\DataPersister;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Engine\Service\AclManagerInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\Process\Service\ProcessHandlerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProcessProcessor implements ProcessorInterface
{
    /**
     * @var ProcessHandlerRegistry
     */
    private $registry;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var AclManagerInterface
     */
    private $acl;

    /**
     * ProcessProcessor constructor.
     * @param ProcessHandlerRegistry $registry
     * @param Security $security
     * @param AclManagerInterface $acl
     */
    public function __construct(ProcessHandlerRegistry $registry, Security $security, AclManagerInterface $acl)
    {
        $this->registry = $registry;
        $this->security = $security;
        $this->acl = $acl;
    }

    /**
     * Handle Process create / update request
     * @param Process $process
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return Process|void
     */
    public function process(mixed $process, Operation $operation, array $uriVariables = [], array $context = []): ?Process
    {
        $processHandler = $this->registry->get($process->getType());

        $this->checkAuthentication($processHandler);

        $processHandler->validate($process);

        $hasAccess = $this->checkACLAccess($processHandler, $process);

        $processHandler->configure($process);

        if (!$hasAccess) {
            $process->setMessages(['LBL_ACCESS_DENIED']);
            $process->setStatus('error');

            return $process;
        }

        if ($process->getAsync() === true) {
            // Store process for background processing
            // Not supported yet
        } else {
            $processHandler->run($process);
        }

        return $process;
    }

    /**
     * Check if user has the needed role
     * @param ProcessHandlerInterface $processHandler
     */
    protected function checkAuthentication(ProcessHandlerInterface $processHandler): void
    {
        if (empty($processHandler->requiredAuthRole())) {
            return;
        }

        if ($this->security->isGranted($processHandler->requiredAuthRole()) === true) {
            return;
        }

        throw new AccessDeniedException();
    }

    /**
     * Check acl access
     * @param ProcessHandlerInterface $processHandler
     * @param Process $process
     */
    protected function checkACLAccess(ProcessHandlerInterface $processHandler, Process $process): bool
    {
        $modulesACLs = $processHandler->getRequiredACLs($process) ?? [];
        if (empty($modulesACLs)) {
            return true;
        }

        $hasAccess = true;
        foreach ($modulesACLs as $module => $requiredACLs) {
            if (empty($requiredACLs)) {
                continue;
            }
            if (empty($module)) {
                continue;
            }

            if ($hasAccess === false) {
                return false;
            }

            foreach ($requiredACLs as $requiredACL) {
                $record = $requiredACL['record'] ?? '';
                $ids = $requiredACL['ids'] ?? '';
                $action = $requiredACL['action'] ?? '';

                if (empty($action)) {
                    continue;
                }

                if ($hasAccess === false) {
                    return false;
                }

                if (empty($record) && empty($ids)) {
                    $hasAccess &= $this->acl->checkAccess($module, $action, true, 'module', true);
                    continue;
                }

                if (!empty($record)) {
                    $hasAccess &= $this->acl->checkRecordAccess($module, $action, $record);
                    continue;
                }

                if (!empty($ids)) {
                    $hasAccess &= $this->checkRecordsAccess($ids, $module, $action);
                }
            }
        }

        return $hasAccess;
    }

    /**
     * @param array $ids
     * @param string $module
     * @param string $action
     * @return bool
     */
    protected function checkRecordsAccess(array $ids, string $module, string $action): bool
    {
        if (empty($ids)) {
            return true;
        }

        foreach ($ids as $id) {
            $hasAccess = $this->acl->checkRecordAccess($module, $action, $id);
            if ($hasAccess === false) {
                return false;
            }
        }

        return true;
    }
}
