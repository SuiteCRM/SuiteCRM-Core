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


namespace App\Module\Contacts\Service\BulkActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\LegacyHandler\Pdf\BasePDFManager;
use App\Process\Service\ProcessHandlerInterface;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Psr\Log\LoggerInterface;
use SuiteCRM\Exception\Exception;

class PrintAsPdfBulkAction implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'bulk-contacts-print-as-pdf';

    /**
     * PrintAsPdfBulkAction constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param BasePDFManager $pdfManager
     * @param LoggerInterface $logger
     * @param SystemConfigProviderInterface $systemConfigProvider
     */
    public function __construct(
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected BasePDFManager $pdfManager,
        protected LoggerInterface $logger,
        protected SystemConfigProviderInterface $systemConfigProvider
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
        $ids = $options['ids'] ?? [];


        return [
            $module => [
                [
                    'action' => 'view'
                ],
                [
                    'action' => 'export',
                    'ids' => $ids
                ]
            ]
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
        [
            'module' => $baseModule,
            'ids' => $baseIds
        ] = $options;

        ['modalRecord' => $modalRecord] = $options;
        [
            'module' => $modalModule,
            'id' => $modalId
        ] = $modalRecord;

        if (empty($baseModule) || empty($baseIds) || empty($modalModule) || empty($modalId)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process): void
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
     */
    protected function getDownloadData(?array $options): array
    {

        ['modalRecord' => $modalRecord] = $options;
        [
            'id' => $modalId
        ] = $modalRecord;

        $ids = $options['ids'] ?? [];
        $baseModule = $options['module'] ?? '';

        $options = [
            'noteFieldsMap' => [
                'contact_id' => 'id',
                'parent_id' => 'account_id',
            ],
            'noteFields' => [
                'parent_type' => 'Accounts',
            ]
        ];

        try {
            $record = $this->pdfManager->generateBulkPdf($baseModule, $ids, $modalId, $options);
        } catch (Exception $e) {
            $this->logger->error('Error generating PDF: ' . $e->getMessage());
            return $this->getError();
        }

        if ($record === null) {
            return $this->getError();
        }

        $url = '';
        $siteUrl = $this->systemConfigProvider->getSystemConfig('site_url')->getValue();

        if (isset($record->getAttributes()['contentUrl'])) {
            $url = $siteUrl . $record->getAttributes()['contentUrl'];
        }

        if (empty($url)) {
            return $this->getError();
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

    /**
     * @return array[]
     */
    public function getError(): array
    {
        return [
            'error' => ['LBL_PDF_GENERATION_FAILED'],
        ];
    }

}
