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

namespace App\Process\Service\RecordActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Data\Service\RecordProviderInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\LegacyHandler\Pdf\BasePDFManager;
use Psr\Log\LoggerInterface;

/**
 * Record action "Email PDF" (quotes, invoices and contracts): the PDF of the
 * record with the template chosen in the select modal, attached to a new email
 * to the record contact, as the legacy "Email PDF" button.
 */
class EmailPdfAction extends BuildPDFEmailAction
{
    protected const RECORD_PROCESS_TYPE = 'record-email-pdf';

    public function __construct(
        RecordProviderInterface $recordProvider,
        BasePDFManager $pdfManager,
        MediaObjectManagerInterface $mediaObjectManager,
        LoggerInterface $logger,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct($recordProvider, $pdfManager, $mediaObjectManager, $logger);
    }

    public function getProcessType(): string
    {
        return self::RECORD_PROCESS_TYPE;
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $module = $options['module'] ?? '';

        $modalRecord = $options['params']['modalRecord'] ?? [];
        $modalRecordModule = $modalRecord['module'] ?? '';

        $acls = [
            $module => [
                [
                    'action' => 'view',
                    'record' => $options['id'] ?? '',
                ],
            ],
        ];

        if ($modalRecordModule !== '') {
            $acls[$modalRecordModule] = [
                [
                    'action' => 'view',
                    'record' => $modalRecord['id'] ?? '',
                ],
            ];
        }

        return $acls;
    }

    public function configure(Process $process): void
    {
        $process->setId(self::RECORD_PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options['module']) || empty($options['id'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $options['params'] = array_merge($options['params'] ?? [], [
            'module' => $this->moduleNameMapper->toLegacy((string)$options['module']),
            'id' => (string)$options['id'],
        ]);
        $process->setOptions($options);

        parent::run($process);
    }
}
