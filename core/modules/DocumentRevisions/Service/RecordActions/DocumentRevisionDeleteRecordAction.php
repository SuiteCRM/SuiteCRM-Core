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

namespace App\Module\DocumentRevisions\Service\RecordActions;

use App\Data\Service\RecordDeletionServiceInterface;
use App\Module\Documents\LegacyHandler\DocumentsManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\RecordActions\DeleteRecordAction;

class DocumentRevisionDeleteRecordAction extends DeleteRecordAction
{

    protected const PROCESS_TYPE = 'document-revision-delete';

    public function __construct(
        ModuleNameMapperInterface $moduleNameMapper,
        RecordDeletionServiceInterface $recordDeletionProvider,
        protected DocumentsManagerInterface $documentsManager
    )
    {
        parent::__construct($moduleNameMapper, $recordDeletionProvider);
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        $recordId = $options['id'] ?? '';

        if (!$recordId) {
            $process->setStatus('error');
            $process->setMessages(['LBL_NO_RECORD_ID_PROVIDED']);
            return;
        }

        $documentId = $this->documentsManager->getDocumentIdByRevisionId($recordId);

        if (!$documentId) {
            $process->setStatus('error');
            $process->setMessages(['LBL_NO_DOCUMENT_ID_PROVIDED']);
            return;
        }

        $latestRevisionId = $this->documentsManager->getLatestRevisionId($documentId);

        if ($recordId === $latestRevisionId) {
            $process->setStatus('error');
            $process->setMessages(['LBL_CANNOT_DELETE_LATEST_REVISION']);

            return;
        }

        parent::run($process);
    }
}
