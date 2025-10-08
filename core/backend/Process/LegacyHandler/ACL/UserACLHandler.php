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

namespace App\Process\LegacyHandler\ACL;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ActionNameMapperInterface;
use App\Process\Service\BaseActionDefinitionProviderInterface;
use App\Process\Service\LegacyActionResolverInterface;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use UserACLService;

class UserACLHandler extends LegacyHandler implements ProcessHandlerInterface, LoggerAwareInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'user-acl';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * @var BaseActionDefinitionProviderInterface
     */
    private $baseActionDefinitionProvider;

    /**
     * @var LegacyActionResolverInterface
     */
    private $legacyActionResolver;

    /**
     * @var array
     */
    private $adminOnlyModuleActions;

    /**
     * @var ActionNameMapperInterface
     */
    private $actionNameMapper;

    /**
     * UserACLHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param BaseActionDefinitionProviderInterface $baseActionDefinitionProvider
     * @param LegacyActionResolverInterface $legacyActionResolver
     * @param array $adminOnlyModuleActions
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        ModuleNameMapperInterface $moduleNameMapper,
        BaseActionDefinitionProviderInterface $baseActionDefinitionProvider,
        LegacyActionResolverInterface $legacyActionResolver,
        ActionNameMapperInterface $actionNameMapper,
        array $adminOnlyModuleActions
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->moduleNameMapper = $moduleNameMapper;
        $this->baseActionDefinitionProvider = $baseActionDefinitionProvider;
        $this->legacyActionResolver = $legacyActionResolver;
        $this->actionNameMapper = $actionNameMapper;
        $this->adminOnlyModuleActions = $adminOnlyModuleActions;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function configure(
        Process $process
    ): void {
        //This process is synchronous
        //We aren't going to store a record on db
        //thus we will use process type as the id
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function validate(
        Process $process
    ): void {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $options = $process->getOptions();
        [
            'module' => $module
        ] = $options;

        if (empty($module)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process)
    {
        $this->init();
        $this->startLegacyApp();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/ACL/UserACLService.php';

        $options = $process->getOptions();
        [
            'module' => $module,
            'payload' => $payload
        ] = $options;

        $routeAction = $payload['routeAction'] ?? 'index';
        $routeRecord = $payload['record'] ?? '';
        $queryParams = $payload['queryParams'];

        $context = [
            'record' => $routeRecord
        ];

        $actionKey = $routeAction;

        $resolvedLegacyModule = $this->getResolvedLegacyModule($module, $actionKey, $queryParams);
        if (!empty($resolvedLegacyModule)) {
            $actionKey = $module;
            $module = $resolvedLegacyModule;
        }

        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);
        $frontEndModuleName = $this->moduleNameMapper->toFrontEnd($module);

        $hasAccess = false;
        if ($this->moduleNameMapper->isValidModule($legacyModuleName)
            && ($this->baseActionDefinitionProvider->isActionAccessible($frontEndModuleName, $actionKey, $context))
        ) {
            $hasAccess = true;
        }

        global $current_user;
        $isAdmin = is_admin($current_user);
        $isActionAdminOnly = $this->isAdminOnlyAction($frontEndModuleName, $legacyModuleName, $actionKey, $queryParams);

        if ($isActionAdminOnly && !$isAdmin) {
            $hasAccess = false;
        }

        $service = new UserACLService();
        $result = $service->run($legacyModuleName, $payload['routeURL'], $hasAccess);

        $process->setStatus('success');

        if ($result['status'] !== true) {
            $process->setStatus('error');
        }

        if (!empty($result['message'])) {
            $process->setMessages([
                $result['message']
            ]);
        }

        $this->close();

        $process->setData(['result' => $result['status']]);
    }

    /**
     * Get list of modules the user has access to
     * @param string $primaryAction
     * @param string $secondaryAction
     * @return string
     * @description Special case, when action is treated as module name by legacy
     * e.g. merge-records is an action but treated as a module by legacy
     * we need to identify the actual module(parent) name and the applicable acls in this case
     */
    protected function entryExistsInLegacyActionMapper(string $primaryAction, string $secondaryAction): string
    {
        $actionModuleIdentifierKey = $this->legacyActionResolver->get($primaryAction, $secondaryAction);

        if (empty($actionModuleIdentifierKey)) {
            return '';
        }

        return $actionModuleIdentifierKey;
    }

    /**
     * Get list of modules the user has access to
     * @param string $primaryAction
     * @param string $secondaryAction
     * @param array $queryParams
     * @return string
     */
    protected function getResolvedLegacyModule(
        string $primaryAction,
        string $secondaryAction,
        array $queryParams
    ): string {
        $actionModuleIdentifierKey = $this->entryExistsInLegacyActionMapper($primaryAction, $secondaryAction);

        if (empty($actionModuleIdentifierKey)) {
            return '';
        }

        return $queryParams[$actionModuleIdentifierKey] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $module
     * @param string $legacyModule
     * @param string $actionKey
     * @param array $queryParams
     * @return bool
     */
    protected function isAdminOnlyAction(string $module, string $legacyModule,  string $actionKey, array $queryParams): bool
    {
        if (!empty($queryParams['import_module']) && strtolower($module) === 'import') {
            $module = $this->moduleNameMapper->toFrontEnd($queryParams['import_module']);
            $legacyModule = $queryParams['import_module'];
            $actionKey = 'import';
        }

        $adminOnlyList = [];

        /* @noinspection PhpIncludeInspection */
        require_once 'include/modules.php';
        $legacyActionName = strtolower($this->actionNameMapper->toLegacy($actionKey));
        $adminOnlyActions = $adminOnlyList[$legacyModule] ?? [];
        $adminOnlyAction = $adminOnlyActions[$legacyActionName] ?? $adminOnlyActions['all'] ?? false;
        $isActionAdminOnly = !empty($adminOnlyAction) && $adminOnlyAction !== 'allow';

        if ($isActionAdminOnly) {
            return true;
        }

        $adminOnlyActions = $this->adminOnlyModuleActions[$module] ?? [];

        return $adminOnlyActions[strtolower($actionKey)] ?? $adminOnlyActions['*'] ?? false;
    }
}
