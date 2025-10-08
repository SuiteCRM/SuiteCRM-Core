<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

namespace App\Process\LegacyHandler;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use UnlinkService;

class UnlinkRelationHandler extends LegacyHandler implements ProcessHandlerInterface, LoggerAwareInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-unlink';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * UnlinkRelationHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        ModuleNameMapperInterface $moduleNameMapper
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
        $options = $process->getOptions();
        $payload = $options['payload'] ?? [];
        $baseModule = $payload['baseModule'] ?? '';
        $relateModule = $payload['recordModule'] ?? '';
        $relateModuleIds = $payload['relateRecordIds'] ?? [];
        $relateModuleId = $payload['relateRecordIds'] ?? '';

        if (!empty($relateModuleId)){
            $relateModuleIds = [...$relateModuleIds, $relateModuleId];
        }

        $acls = [
            $baseModule => [
                [
                    'action' => 'view',
                    'record' => $payload['baseRecordId'] ?? ''
                ],
                [
                    'action' => 'edit'
                ],
            ],
            $relateModule => [
                [
                    'action' => 'view',
                    'ids' => $relateModuleIds
                ]
            ]
        ];

        if (!empty($relateModule) && !empty($relateModuleIds)) {
            $acls[$relateModule] = [
                [
                    'action' => 'view',
                    'ids' =>  $relateModuleIds
                ]
            ];
        }

        return $acls;
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

        ['payload' => $payload] = $process->getOptions();

        $baseModule = $payload['baseModule'] ?? '';
        $baseRecordId = $payload['baseRecordId'];
        $linkField = $payload['linkField'] ?? '';
        $relateRecordIds = $payload['relateRecordIds'] ?? [];
        $relateRecordId = $payload['relateRecordId'] ?? '';

        if (!empty($relateRecordId)){
            $relateRecordIds = [...$relateRecordIds ?? [], $relateRecordId];
        }

        if (empty($payload) || empty($baseModule) || empty($baseRecordId) || empty($linkField) || empty($relateRecordIds)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process)
    {
        $this->init();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/Relationships/UnlinkService.php';

        ['payload' => $payload] = $process->getOptions();

        $baseModule = $payload['baseModule'] ?? '';
        $baseRecordId = $payload['baseRecordId'];
        $linkField = $payload['linkField'] ?? '';
        $relateRecordIds = $payload['relateRecordIds'] ?? [];
        $relateRecordId = $payload['relateRecordId'] ?? '';

        $baseModule = $this->moduleNameMapper->toLegacy($baseModule);

        $service = new UnlinkService();

        $result = $service->run($baseModule, $baseRecordId, $linkField, $relateRecordId, $relateRecordIds);

        $process->setStatus('success');
        if ($result['success'] !== true) {
            $process->setStatus('failure');
        }

        if (!empty($result['message'])) {
            $process->setMessages([
                $result['message']
            ]);
        }

        $process->setData(['reload' => true]);

        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
