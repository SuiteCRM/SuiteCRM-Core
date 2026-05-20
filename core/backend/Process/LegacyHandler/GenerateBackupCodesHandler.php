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

use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\Security\TwoFactor\BackupCodeGenerator;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class GenerateBackupCodesHandler extends LegacyHandler implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'generate-backup-codes';

    protected Security $security;

    protected BackupCodeGenerator $backupCodeGenerator;

    private PreparedStatementHandler $preparedStatementHandler;

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        Security $security,
        BackupCodeGenerator $backupCodeGenerator,
        PreparedStatementHandler $preparedStatementHandler
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
        $this->security = $security;
        $this->backupCodeGenerator = $backupCodeGenerator;
        $this->preparedStatementHandler = $preparedStatementHandler;
    }

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
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
    }

    /**
     * @param Process $process
     * @return void
     * @throws Exception
     */
    public function run(Process $process): void
    {
        $user = $this->security->getToken()?->getUser();
        $backupCodes = $this->backupCodeGenerator->generate();
        $user?->setBackupCodes($backupCodes);
        $this->setupBackupCodes($user, $backupCodes);


        $process->setStatus('success');
        $process->setMessages(['LBL_BACKUP_CODES_GENERATED']);
        if (!$backupCodes) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);

            return;
        }

        $responseData = [
            'backupCodes' => $backupCodes,
        ];

        $process->setData($responseData);
    }

    /**
     * @param UserInterface|null $user
     * @param array $backupCodes
     * @return void
     * @throws Exception
     */
    protected function setupBackupCodes(?UserInterface $user, array $backupCodes): void
    {
        $this->preparedStatementHandler->update("UPDATE users SET backup_codes = :backup_codes WHERE id = :id",
            ['id' => $user->getId(), 'backup_codes' => $backupCodes],
            [['param' => 'id', 'type' => 'string'], ['param' => 'backup_codes', 'type' => 'json']]
        );
    }
}
