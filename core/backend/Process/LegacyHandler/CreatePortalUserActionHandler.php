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

class CreatePortalUserActionHandler extends LegacyHandler implements ProcessHandlerInterface, LoggerAwareInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-create-portal-user';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * CreatePortalUserActionHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $requestStack
     * @param ModuleNameMapperInterface $moduleNameMapper
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
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
        $module = $options['module'] ?? '';


        return [
            $module => [
                [
                    'action' => 'edit',
                    'record' => $options['id'] ?? ''
                ]
            ],
        ];

    }

    /**
     * @inheritDoc
     */
    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $options = $process->getOptions();

        if (empty($options['id'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    public function run(Process $process): void
    {
        $this->init();

        $options = $process->getOptions();

        global $sugar_config, $mod_strings;
        $contact = \BeanFactory::getBean('Contacts', $options['id']);

        if (!array_key_exists("aop", $sugar_config)) {
            $this->logger->warning('LBL_ERROR_AOP_NOT_CONFIGURED');
            $process->setMessages(['LBL_ERROR_AOP_NOT_CONFIGURED']);
            $process->setStatus('error');
            return;
        }
        if (empty($sugar_config['aop']['enable_portal'])) {
            $this->logger->warning('LBL_ERROR_PORTAL_NOT_ENABLED');
            $process->setMessages(['LBL_ERROR_PORTAL_NOT_ENABLED']);
            $process->setStatus('error');
            return;
        }
        if (empty($sugar_config['aop']['enable_aop'])) {
            $this->logger->warning('LBL_ERROR_AOP_NOT_ENABLED');
            $process->setMessages(['LBL_ERROR_AOP_NOT_ENABLED']);
            $process->setStatus('error');
            return;
        }
        if (empty($sugar_config['aop']['joomla_url'])) {
            $this->logger->warning('LBL_ERROR_JOOMLA_URL_MISSING');
            $process->setMessages(['LBL_ERROR_JOOMLA_URL_MISSING']);
            $process->setStatus('error');
            return;
        }
        if (empty($contact) || !$contact->id || !$contact->email1) {
            $this->logger->error($mod_strings['LBL_ERROR_CONTACT_ID_OR_EMAIL_EMPTY']);
            $process->setMessages(['LBL_ERROR_CONTACT_ID_OR_EMAIL_EMPTY']);
            $process->setStatus('error');
            return;
        }

        $portalURL = $sugar_config['aop']['joomla_url'];
        $apiEndpoint = $portalURL . '/index.php?option=com_advancedopenportal&task=create&sug=' . $contact->id;
        $apiResponse = file_get_contents($apiEndpoint);

        if ($apiResponse === false) {
            $this->logger->error($mod_strings['LBL_FAILED_TO_CONNECT_JOOMLA']);
            $process->setStatus('error');
            $process->setMessages(['LBL_CREATE_PORTAL_USER_FAILED']);
            return;
        }

        $decodedResponse = json_decode($apiResponse, false, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);

        if (empty($decodedResponse) || !$decodedResponse->success) {
            $msg = $decodedResponse->error ?: $mod_strings['LBL_CREATE_PORTAL_USER_FAILED'];
            $this->logger->error($msg);
            $this->logger->error('JSON error: ' . json_last_error_msg());
            $process->setStatus('error');
            $process->setMessages([$msg]);
            return;
        }

        $process->setMessages(['LBL_CREATE_PORTAL_USER_SUCCESS']);
        $process->setStatus('success');
        $process->setData(['reload' => true]);

        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }


}
