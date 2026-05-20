<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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


namespace App\Module\Emails\Service\RecordActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use BeanFactory;
use Symfony\Component\HttpFoundation\RequestStack;

class InsertEmailTemplateAction extends LegacyHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-insert-email-template';

    protected ModuleNameMapperInterface $moduleNameMapper;

    /**
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
        ModuleNameMapperInterface $moduleNameMapper,
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
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function getHandlerKey(): string
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

        $modalRecord = $options['params']['modalRecord'] ?? [];
        $modalRecordModule = $modalRecord['module'] ?? '';
        $modalRecordId = $modalRecord['id'] ?? '';

        $acls = [
            $module => [
                [
                    'action' => 'view',
                    'record' => $options['id'] ?? ''
                ],
                [
                    'action' => 'export',
                    'record' => $options['id'] ?? ''
                ]
            ],
        ];

        if ($modalRecordModule !== '') {
            $acls[$modalRecordModule] = [
                [
                    [
                        'action' => 'view',
                        'record' => $modalRecordId
                    ]
                ]
            ];
        }

        return $acls;
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
     *
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();
        [
            'module' => $baseModule,
            'id' => $id
        ] = $options;

        ['modalRecord' => $modalRecord] = $options['params'];
        [
            'module' => $modalModule,
            'id' => $modalId
        ] = $modalRecord;

        if (empty($modalModule) || $modalModule !== 'email-templates') {
            $process->setStatus('error');
            $process->setMessages(['LBL_WRONG_MODULE_PROVIDED']);
            return;
        }

        if (empty($modalId)) {
            $process->setStatus('error');
            $process->setMessages(['LBL_NO_TEMPLATE_ID_PROVIDED']);
            return;
        }

        $module = $this->moduleNameMapper->toLegacy($modalModule);
        $id = $modalId;

        $this->init();

        $bean = BeanFactory::getBean($module, $id);

        if (empty($bean)) {
            $process->setStatus('error');
            $process->setMessages(['LBL_TEMPLATE_NOT_FOUND']);
            $this->close();
            return;
        }

        $this->close();

        $params = $options['params'];
        $setFieldSubject = $params['setFieldSubject'] ?? '';
        $setFieldBody = $params['setFieldBody'] ?? '';

        $responseData = [
            'handler' => 'update-fields',
            'params' => [
                'values' => [
                    $setFieldSubject => ['value' => $bean->subject ?? ''],
                    $setFieldBody => ['value' => html_entity_decode($bean->body_html) ?? ''],
                ]
            ]
        ];

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($responseData);
    }
}
