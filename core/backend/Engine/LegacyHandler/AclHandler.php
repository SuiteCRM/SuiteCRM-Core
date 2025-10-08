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

namespace App\Engine\LegacyHandler;

use ACLController;
use App\Engine\Service\AclManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

class AclHandler extends LegacyHandler implements AclManagerInterface
{
    public const HANDLER_KEY = 'acl-handler';

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * AclHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session
    )
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->moduleNameMapper = $moduleNameMapper;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function checkAccess(
        string $module,
        string $action,
        bool $isOwner = false,
        $type = 'module',
        $in_group = false
    ): bool {
        $this->init();

        $this->startLegacyApp();

        $legacyName = $this->moduleNameMapper->toLegacy($module);

        $hasAccess = ACLController::checkAccess($legacyName, $action, $isOwner, $type, $in_group);

        if (check_default_module_access($legacyName) === false) {
            $hasAccess = false;
        }

        $this->close();

        return $hasAccess;
    }

    /**
     * @inheritDoc
     */
    public function getRecordAcls(SugarBean $bean): array
    {
        $acls = [];

        $actions = ['list', 'edit', 'view', 'delete', 'export', 'import'];

        foreach ($actions as $action) {
            $hasAccess = $bean->ACLAccess($action) ?? false;

            if ($hasAccess === true) {
                $acls[] = $action;
            }
        }

        return $acls;
    }

    /**
     * @inheritDoc
     */
    public function checkRecordAccess(string $module, string $action, string $record): bool
    {
        $this->init();
        $this->startLegacyApp();

        $legacyName = $this->moduleNameMapper->toLegacy($module);

        $bean = BeanFactory::getBean($legacyName, $record);

        if (!$bean) {
            return false;
        }

        $hasAccess = $bean->ACLAccess($action);

        $this->close();

        return $hasAccess;
    }
}
