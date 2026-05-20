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


namespace App\Module\Contacts\Service\RecordActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Data\Service\RecordProviderInterface;
use App\Process\Entity\Process;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\LegacyHandler\Pdf\BasePDFManager;
use App\Process\Service\ProcessHandlerInterface;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Psr\Log\LoggerInterface;

class PrintAsPdfAction implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-contact-print-as-pdf';

    /**
     * PrintAsPdfAction constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param BasePDFManager $pdfManager
     * @param LoggerInterface $logger
     * @param SystemConfigProviderInterface $systemConfigProvider
     * @param RecordProviderInterface $recordProvider
     */
    public function __construct(
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected BasePDFManager $pdfManager,
        protected LoggerInterface $logger,
        protected SystemConfigProviderInterface $systemConfigProvider,
        protected RecordProviderInterface $recordProvider
    )
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

        if (empty($baseModule) || empty($id) || empty($modalModule) || empty($modalId)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process)
    {
        $options = $process->getOptions();

        $responseData = $this->getDownloadData($options);

        if (isset($responseData['error'])) {
            $process->setStatus('error');
            $process->setMessages([$responseData['error']]);
            $process->setData([]);
            return;
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($responseData);
    }

    /**
     * @param array|null $options
     * @return array
     * @throws \Exception
     */
    protected function getDownloadData(?array $options): array
    {

        ['modalRecord' => $modalRecord] = $options['params'];
        [
            'id' => $modalId
        ] = $modalRecord;

        $recordId = $options['id'] ?? null;
        $module = $options['module'] ?? null;

        if ($recordId === null) {
            return [
                'error' => 'LBL_UNABLE_TO_GET_ID'
            ];
        }

        $record = $this->recordProvider->getRecord($module, $recordId);

        $pdfOptions = array_merge($options['params'] ?? [], [
            'noteFields' => [
                'contact_id' => $record?->getAttributes()['id'] ?? '',
                'parent_type' => 'Accounts',
                'parent_id' => $record?->getAttributes()['account_id'] ?? '',
            ]
        ]);

        $pdf = $this->pdfManager->generatePdf($module, $recordId, $modalId, $pdfOptions);

        if ($pdf === null) {
            return [
                'error' => 'LBL_PDF_GENERATION_FAILED'
            ];
        }

        $url = '';
        $siteUrl = $this->systemConfigProvider->getSystemConfig('site_url')->getValue();

        if (isset($pdf->getAttributes()['contentUrl'])) {
            $url = $siteUrl . $pdf->getAttributes()['contentUrl'];
        }

        if (empty($url)) {
            return [
                'error' => 'LBL_PDF_GENERATION_FAILED'
            ];
        }

        return [
            'handler' => 'export',
            'params' => [
                'url' => $url,
                'method' => 'GET',
                'formData' => []
            ]
        ];
    }

}
