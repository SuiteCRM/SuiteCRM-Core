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

namespace App\Process\Service\RecordValidators;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Data\LegacyHandler\PresetDataHandlers\RecordIdsDataHandler;
use App\Data\Service\Record\DuplicateCheck\DuplicateCheckProviderRegistry;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Record save validator warning about possible duplicates, like the legacy
 * create screens of Accounts, Contacts and Leads (ShowDuplicates).
 *
 * The module duplicate check provider looks for records matching the values
 * being saved. When some are found, the save is held and they are listed in a
 * confirmation modal: Cancel goes back to the record, Proceed saves anyway.
 */
class RecordDuplicateCheckHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'record-duplicate-check';

    protected const MSG_MODULE_NOT_SUPPORTED = 'Duplicate check is not enabled for this module';
    protected const MAX_DUPLICATES = 50;

    /**
     * @param DuplicateCheckProviderRegistry $duplicateCheckProviderRegistry
     */
    public function __construct(protected DuplicateCheckProviderRegistry $duplicateCheckProviderRegistry)
    {
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
                ['action' => 'edit'],
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
        $options = $process->getOptions();
        $module = $options['module'] ?? '';

        if (!is_string($module) || $this->duplicateCheckProviderRegistry->get($module) === null) {
            throw new InvalidArgumentException(self::MSG_MODULE_NOT_SUPPORTED);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = $options['module'];
        $attributes = $options['attributes'] ?? [];
        $originalAttributes = $options['originalAttributes'] ?? [];
        $recordId = (string)($attributes['id'] ?? $originalAttributes['id'] ?? '');

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData([]);

        if ($recordId !== '' && !$this->duplicateCheckProviderRegistry->checkOnEdit()) {
            return;
        }

        $provider = $this->duplicateCheckProviderRegistry->get($module);
        $ids = $provider->getDuplicateIds($attributes, $originalAttributes, $recordId);
        $ids = array_slice(array_values(array_unique($ids)), 0, self::MAX_DUPLICATES);

        if (empty($ids)) {
            return;
        }

        $process->setStatus('error');
        $process->setData([
            'displayListConfirmation' => true,
            'confirmationTitle' => 'LBL_DUPLICATE_CHECK_TITLE',
            'confirmationLabel' => 'LBL_DUPLICATE_CHECK_MESSAGE',
            'module' => $module,
            'preset' => [
                'type' => RecordIdsDataHandler::PRESET_TYPE,
                'params' => ['ids' => $ids],
            ],
        ]);
    }
}
