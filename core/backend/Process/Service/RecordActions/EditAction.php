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

namespace App\Process\Service\RecordActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Throwable;

class EditAction implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';
    protected const PROCESS_TYPE = 'record-edit';

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * @var string
     */
    private $legacyDir;

    /**
     * EditAction constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param string $legacyDir
     */
    public function __construct(ModuleNameMapperInterface $moduleNameMapper, string $legacyDir)
    {
        $this->moduleNameMapper = $moduleNameMapper;
        $this->legacyDir = $legacyDir;
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
        $module = $options['payload']['recordModule'] ?? $options['module'] ?? '';

        $baseModule = $options['payload']['baseModule'] ?? '';
        $baseRecord = $options['payload']['baseRecordId'] ?? '';

        return [
            $module => [
                [
                    'action' => 'edit',
                    'record' => $options['id'] ?? ''
                ]
            ],
            $baseModule => [
                [
                    'action' => 'view',
                    'record' => $baseRecord
                ]
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function configure(Process $process): void
    {
        //This process is synchronous
        //We aren't going to store a record on db
        //thus we will use process type as the id
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

        if (empty($options['module']) || empty($options['action'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        if (empty($options['id'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        if (empty($options['payload']['linkField'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        if (empty($options['payload']['baseModule'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        if (empty($options['payload']['baseRecordId'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process)
    {
        $options = $process->getOptions();
        $relationshipEdit = $options['payload']['relationshipEdit'] ?? [];

        $modernResponse = $this->buildModernRelationshipEditResponseData($relationshipEdit);
        if (($modernResponse['handled'] ?? false) === true) {
            $process->setStatus($modernResponse['status'] ?? 'success');
            $process->setMessages($modernResponse['messages'] ?? []);
            $process->setData($modernResponse['data'] ?? null);
            return;
        }

        $responseData = $this->buildRelationshipEditResponseData($options, $relationshipEdit);
        if (empty($responseData)) {
            $responseData = $this->buildRecordEditResponseData($options);
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($responseData);
    }

    /**
     * Build modern relationship-edit save response.
     */
    protected function buildModernRelationshipEditResponseData(array $relationshipEdit): array
    {
        if (($relationshipEdit['enabled'] ?? false) !== true) {
            return ['handled' => false];
        }

        $modernConfig = $relationshipEdit['modern'] ?? [];
        if (($modernConfig['enabled'] ?? false) !== true) {
            return ['handled' => false];
        }

        if (!$this->isOpportunityContactRelationshipEdit($relationshipEdit)) {
            return ['handled' => false];
        }

        $values = $relationshipEdit['values'] ?? [];
        $roleField = $modernConfig['roleField'] ?? 'opportunity_role';
        if ($roleField === '' || !array_key_exists($roleField, $values)) {
            return ['handled' => false];
        }

        $relationshipRecordId = (string)($relationshipEdit['recordId'] ?? '');
        if ($relationshipRecordId === '') {
            return [
                'handled' => true,
                'status' => 'error',
                'messages' => ['LBL_ACTION_ERROR'],
                'data' => null
            ];
        }

        $contactRole = (string)$values[$roleField];
        if (!$this->saveOpportunityContactRole($relationshipRecordId, $contactRole)) {
            return [
                'handled' => true,
                'status' => 'error',
                'messages' => ['LBL_ACTION_ERROR'],
                'data' => null
            ];
        }

        return [
            'handled' => true,
            'status' => 'success',
            'messages' => [],
            'data' => [
                'reload' => true
            ]
        ];
    }

    /**
     * Build default subpanel record edit response.
     */
    protected function buildRecordEditResponseData(array $options): array
    {
        $baseModule = $this->moduleNameMapper->toLegacy($options['payload']['baseModule']);
        $baseRecordId = $options['payload']['baseRecordId'];
        $linkedModule = $options["payload"]["recordModule"];
        $linkedRecordId = $options['id'];

        return [
            'handler' => 'redirect',
            'params' => [
                'route' => $linkedModule . '/edit/' . $linkedRecordId,
                'queryParams' => [
                    'action_module' => $linkedModule,
                    'return_action' => 'DetailView',
                    'return_module' => $baseModule,
                    'return_id' => $baseRecordId
                ]
            ]
        ];
    }

    /**
     * Build relationship-edit redirect response, if supported by legacy action.
     */
    protected function buildRelationshipEditResponseData(array $options, array $relationshipEdit): array
    {
        if (($relationshipEdit['enabled'] ?? false) !== true) {
            return [];
        }

        $relationshipModule = $relationshipEdit['module'] ?? '';
        $relationshipAction = $relationshipEdit['action'] ?? '';
        $relationshipRecordId = $relationshipEdit['recordId'] ?? '';

        if ($relationshipModule === '' || $relationshipAction === '' || $relationshipRecordId === '') {
            return [];
        }

        if (!$this->isLegacyActionImplemented($relationshipModule, $relationshipAction)) {
            return [];
        }

        $baseModule = $this->moduleNameMapper->toLegacy($options['payload']['baseModule']);
        $baseRecordId = $options['payload']['baseRecordId'];

        return [
            'handler' => 'redirect',
            'params' => [
                'route' => $relationshipModule . '/' . $relationshipAction . '/' . $relationshipRecordId,
                'queryParams' => [
                    'action_module' => $relationshipModule,
                    'return_action' => 'DetailView',
                    'return_module' => $baseModule,
                    'return_id' => $baseRecordId
                ]
            ]
        ];
    }

    /**
     * Check if target legacy action has a backing php entry file.
     */
    protected function isLegacyActionImplemented(string $module, string $action): bool
    {
        $legacyModule = $this->moduleNameMapper->toLegacy($module);
        if (!$this->isSafeLegacyIdentifier($legacyModule) || !$this->isSafeLegacyIdentifier($action)) {
            return false;
        }

        $paths = [
            $this->legacyDir . '/custom/modules/' . $legacyModule . '/' . $action . '.php',
            $this->legacyDir . '/modules/' . $legacyModule . '/' . $action . '.php',
        ];

        foreach ($paths as $path) {
            if (is_readable($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate module/action values used to build legacy file paths.
     */
    protected function isSafeLegacyIdentifier(string $value): bool
    {
        return $value !== '' && preg_match('/^[A-Za-z0-9_]+$/', $value) === 1;
    }

    /**
     * Check if payload targets Contacts->Opportunities relationship role edit.
     */
    protected function isOpportunityContactRelationshipEdit(array $relationshipEdit): bool
    {
        $relationshipModule = strtolower((string)($relationshipEdit['module'] ?? ''));
        $relationshipAction = (string)($relationshipEdit['action'] ?? '');

        return $relationshipModule === 'contacts'
            && $relationshipAction === 'ContactOpportunityRelationshipEdit';
    }

    /**
     * Save contact role on opportunities_contacts relationship row.
     */
    protected function saveOpportunityContactRole(string $relationshipRecordId, string $contactRole): bool
    {
        $relationshipClassFile = $this->legacyDir . '/modules/Contacts/ContactOpportunityRelationship.php';
        if (!is_readable($relationshipClassFile)) {
            return false;
        }

        require_once $relationshipClassFile;
        if (!class_exists('ContactOpportunityRelationship')) {
            return false;
        }

        try {
            $relationship = new \ContactOpportunityRelationship();
            $relationship->retrieve($relationshipRecordId);
            if (empty($relationship->id)) {
                return false;
            }

            $relationship->contact_role = $contactRole;
            $relationship->save();
        } catch (Throwable $exception) {
            return false;
        }

        return true;
    }
}
