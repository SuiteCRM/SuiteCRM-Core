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

namespace App\Process\LegacyHandler\Pdf;

use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Entity\MediaObjectInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use Psr\Log\LoggerInterface;
use SugarBean;
use SuiteCRM\Exception\Exception;
use SuiteCRM\PDF\Exceptions\PDFException;
use SuiteCRM\PDF\PDFEngine;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;
use Vich\UploaderBundle\Handler\DownloadHandler;

class BasePDFManager extends LegacyHandler
{

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected RecordProviderInterface $recordProvider,
        protected LoggerInterface $logger,
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected CreatePDFServiceHandler $pdfLegacyHandler,
        protected DownloadHandler $downloadHandler,
        protected ModuleNameMapperInterface $moduleNameMapper,
    )
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return 'base-pdf-manager';
    }

    public function createNote(SugarBean $bean, $fileName): Record
    {
        $currentUser = $this->getCurrentUser();
        $note = new Record();
        $note->setModule('Notes');
        $note->setAttributes([
            'modified_user_id' => $currentUser->id,
            'created_by' => $currentUser->id,
            'name' => $fileName,
            'parent_type' => $bean->module_dir,
            'parent_id' => $bean->id,
            'file_mime_type' => 'application/pdf',
            'filename' => $fileName,
        ]);

        if ($bean->module_dir !== 'Contacts') {
            return $this->recordProvider->saveRecord($note);
        }

        $note->setAttributes(['contact_id' => $bean->id]);
        $note->setAttributes(['parent_type' => 'Accounts']);
        $note->setAttributes(['parent_id' => $bean->account_id]);
        return $this->recordProvider->saveRecord($note);
    }

    /**
     * @param SugarBean|null $moduleBean
     * @param string $fileName
     * @param array $pdfConfig
     * @param mixed $header
     * @param mixed $footer
     * @param mixed $printable
     * @return MediaObjectInterface|null
     */
    public function createPDFMediaObject(
        ?SugarBean $moduleBean,
        string $fileName,
        array $pdfConfig,
        array $pdfContent,
        bool $temp = true
    ): ?Record
    {
        $storageType = $this->getStorageType();
        if (!$storageType) {
            return null;
        }
        $note = $this->createNote($moduleBean, $fileName);
        $recordPdf = $this->pdfLegacyHandler->createPdf($pdfConfig);
        $recordPdf = $this->pdfLegacyHandler->writePDF($recordPdf, $pdfContent);
        return $this->createMediaObjectRecord($note, $storageType, $recordPdf, $fileName, $temp);
    }

    /**
     * @param SugarBean|null $moduleBean
     * @return array
     */
    protected function setObjectArray(?SugarBean $moduleBean): array
    {
        $object_arr = [];
        $object_arr[$moduleBean->module_dir] = $moduleBean->id;

        if ($moduleBean->module_dir === 'Contacts') {
            $object_arr['Accounts'] = $moduleBean->account_id;
        }

        return $object_arr;
    }

    /**
     * @throws \Exception
     */
    protected function getRecord(string $module, string $id): Record
    {
        return $this->recordProvider->getRecord($module, $id);
    }

    /**
     * @throws Exception
     */
    public function generateBulkPdf(string $module, array $ids, string $templateId): ?Record
    {
        $validationResult = $this->validateBulkPdfInputs($templateId, $module);
        if ($validationResult === null) {
            return null;
        }

        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);
        $moduleBean = $this->getBean($legacyModuleName);
        $templateBean = $this->getBean('AOS_PDF_Templates', $templateId);

        $pdfConfig = $this->pdfLegacyHandler->buildPDFConfig($templateBean);
        $basePdf = $this->pdfLegacyHandler->createPdf($pdfConfig);
        $fileName = $this->getPdfName($templateBean->name);

        $storageType = $this->getStorageType();

        $count = 0;
        foreach ($ids as $id) {
            $moduleBean->retrieve($id);
            if (empty($moduleBean->id)) {
                $this->logger->error('Record not found', ['module' => $module, 'id' => $id]);
                continue;
            }

            $object_arr = $this->setObjectArray($moduleBean);

            $_REQUEST['entryPoint'] = 'formLetter';

            [$header, $footer, $printable] = $this->pdfLegacyHandler->parseTemplate($templateBean, $object_arr);

            $pdfContent = [
                'header' => $header,
                'footer' => $footer,
                'printable' => $printable,
            ];

            try {
                $this->createPDFMediaObject($moduleBean, $fileName, $pdfConfig, $pdfContent, false);

                if ($count > 0) {
                    $basePdf->writeBlankPage();
                }

                $basePdf = $this->pdfLegacyHandler->writePDF($basePdf, $pdfContent);
                $count++;
            } catch (PDFException $e) {
                $this->logger->error('PDFException: ' . $e->getMessage());
            }
        }

        return $this->createMediaObjectRecord(null, $storageType, $basePdf, $fileName);
    }

    protected function validateBulkPdfInputs(string $templateId, string $module, $moduleId = null): ?array
    {
        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);
        $moduleBean = $this->getBean($legacyModuleName, $moduleId);
        if (!$moduleBean) {
            $this->logger->error('Invalid Module', ['module' => $module]);
            return null;
        }

        $templateBean = $this->getBean('AOS_PDF_Templates', $templateId);
        if (!$templateBean) {
            $this->logger->error('Invalid Template', ['templateId' => $templateId]);
            return null;
        }

        return [];
    }

    protected function getBean(string $module, $id = null): ?SugarBean
    {
        $this->init();
        $bean = BeanFactory::getBean($module, $id);
        $this->close();

        return $bean;
    }

    protected function getCurrentUser(): ?SugarBean
    {
        $this->init();
        global $current_user;
        $this->close();

        return $current_user;
    }

    protected function getStorageType(): ?string
    {
        $noteFieldDef = $this->fieldDefinitionsProvider->getFieldDefinition('notes', 'file');
        if (!$noteFieldDef) {
            return false;
        }

        if (!isset($noteFieldDef['metadata'], $noteFieldDef['metadata']['storage_type'])) {
            return false;
        }

        return $noteFieldDef['metadata']['storage_type'];
    }

    protected function createMediaObjectRecord(?Record $note, string $storageType, PDFEngine $pdfEngine, string $fileName, bool $temp = true): Record
    {
        $id = create_guid();

        $tempFileName = $this->getUploadDir() . $id;
        $pdfEngine->outputPDF($tempFileName, 'F', $fileName);
        $uploadedFile = new ReplacingFile($this->projectDir . '/public/legacy/upload/' . $id);

        $parentType = $note?->getModule() ?? null;

        if ($parentType) {
            $parentType = $this->moduleNameMapper->toCore($parentType);
        }

        $mediaObjectAttributes = [
            'file' => $uploadedFile,
            'parent_field' => 'file',
            'parent_id' => $note?->getId() ?? null,
            'parent_type' => $parentType,
            'mime_type' => 'application/pdf',
            'name' => $fileName,
            'original_name' => $fileName,
            'temporary' => $temp,
        ];

        $mediaObject = $this->mediaObjectManager->createMediaObjectFromAttributes($storageType, $mediaObjectAttributes);

        $this->mediaObjectManager->saveMediaObject($storageType, $mediaObject);

        $this->deleteTempFile($tempFileName);

        return $this->mediaObjectManager->mapToRecord($storageType, $mediaObject);
    }

    protected function deleteTempFile(string $filePath): void
    {
        $this->pdfLegacyHandler->deleteTempFile($filePath);
    }

    protected function getUploadDir(): string
    {
        return $this->pdfLegacyHandler->getUploadDir();
    }

    protected function getError(): array
    {
        return [
            'error' => 'LBL_PDF_GENERATION_FAILED'
        ];
    }

    protected function getPdfName(string $name): string
    {
        return str_replace(" ", "_", $name) . ".pdf";
    }
}
