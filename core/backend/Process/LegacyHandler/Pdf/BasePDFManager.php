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

    public function createNote(SugarBean $bean, $fileName, array $options = []): Record
    {
        $currentUser = $this->getCurrentUser();
        $note = new Record();
        $note->setModule('Notes');

        $attributes = [
            'modified_user_id' => $currentUser->id,
            'created_by' => $currentUser->id,
            'name' => $fileName,
            'parent_type' => $bean->module_dir,
            'parent_id' => $bean->id,
            'file_mime_type' => 'application/pdf',
            'filename' => $fileName,
        ];

        foreach (($options['noteFields'] ?? []) as $field => $value) {
            $attributes[$field] = $value;
        }

        foreach (($options['noteFieldsMap'] ?? []) as $key => $value) {
            $attributes[$key] = $bean->$value;
        }

        $note->setAttributes($attributes);

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
        ?Record $parentRecord,
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
        $recordPdf = $this->pdfLegacyHandler->createPdf($pdfConfig);
        $recordPdf = $this->pdfLegacyHandler->writePDF($recordPdf, $pdfContent);
        return $this->createMediaObjectRecord($parentRecord, $storageType, $recordPdf, $fileName, $temp);
    }

    /**
     * @param SugarBean|null $moduleBean
     * @return array
     */
    protected function setObjectArray(?SugarBean $moduleBean): array
    {
        $objectArr = [];
        $objectArr[$moduleBean->module_dir] = $moduleBean->id;
        $objectArr['Accounts'] = $moduleBean->billing_account_id ?? '';

        if ($moduleBean->module_dir === 'Contacts') {
            $objectArr['Accounts'] = $moduleBean->account_id;
        }

        $objectArr['Contacts'] = $moduleBean->billing_contact_id ?? '';
        $objectArr['Users'] = $moduleBean->assigned_user_id ?? '';
        $objectArr['Currencies'] = $moduleBean->currency_id ?? '';

        return $objectArr;
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
    public function generateBulkPdf(string $module, array $ids, string $templateId, array $options = []): ?Record
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
            $moduleBean = $this->retrieveBean($moduleBean, $id);
            if (empty($moduleBean->id)) {
                $this->logger->error('Record not found', ['module' => $module, 'id' => $id]);
                continue;
            }

            $objectArr = $this->setObjectArray($moduleBean);

            [$header, $footer, $printable] = $this->pdfLegacyHandler->parseTemplate($moduleBean, $templateBean, $objectArr, true);

            $pdfContent = [
                'header' => $header,
                'footer' => $footer,
                'printable' => $printable,
            ];

            try {
                $note = $this->createNote($moduleBean, $fileName, $options);
                $this->createPDFMediaObject($note, $fileName, $pdfConfig, $pdfContent, false);

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

    public function generatePdf(string $module, string $id, string $templateId, $options = [], $temp = false): ?Record
    {
        $validationResult = $this->validateBulkPdfInputs($templateId, $module, $id);
        if ($validationResult === null) {
            return null;
        }

        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);
        $moduleBean = $this->getBean($legacyModuleName, $id);
        $templateBean = $this->getBean('AOS_PDF_Templates', $templateId);

        $pdfConfig = $this->pdfLegacyHandler->buildPDFConfig($templateBean);
        $fileName = $this->getPdfName($templateBean->name);
        $objectArr = $this->setObjectArray($moduleBean);

        [$header, $footer, $printable] = $this->pdfLegacyHandler->parseTemplate($moduleBean, $templateBean, $objectArr, true);

        $pdfContent = [
            'header' => $header,
            'footer' => $footer,
            'printable' => $printable,
        ];

        $parentRecord = $options['parentRecord'] ?? null;

        if ($parentRecord !== null) {
            return $this->createPDFMediaObject($parentRecord, $fileName, $pdfConfig, $pdfContent, $temp);
        }

        if (isset($options['createNote']) && $options['createNote'] === true) {
            $note = $this->createNote($moduleBean, $fileName, $options);
            return $this->createPDFMediaObject($note, $fileName, $pdfConfig, $pdfContent, $temp);
        }

        return $this->createPDFMediaObject(null, $fileName, $pdfConfig, $pdfContent, $temp);
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
            return null;
        }

        if (!isset($noteFieldDef['metadata'], $noteFieldDef['metadata']['storage_type'])) {
            return null;
        }

        return $noteFieldDef['metadata']['storage_type'];
    }

    protected function createMediaObjectRecord(?Record $parentRecord, string $storageType, PDFEngine $pdfEngine, string $fileName, bool $temp = true): Record
    {
        $id = create_guid();

        $tempFileName = $this->getUploadDir() . $id;
        $pdfEngine->outputPDF($tempFileName, 'F', $fileName);
        $uploadedFile = new ReplacingFile($this->projectDir . '/public/legacy/upload/' . $id);

        $parentType = $parentRecord?->getModule() ?? null;

        if ($parentType) {
            $parentType = $this->moduleNameMapper->toCore($parentType);
        }

        $mediaObjectAttributes = [
            'file' => $uploadedFile,
            'parent_field' => 'file',
            'parent_id' => $parentRecord?->getId() ?? null,
            'parent_type' => $parentType,
            'mime_type' => 'application/pdf',
            'name' => $fileName,
            'original_name' => $fileName,
            'temporary' => $temp,
        ];

        $mediaObject = $this->mediaObjectManager->createMediaObjectFromAttributes($storageType, $mediaObjectAttributes);

        $this->mediaObjectManager->saveMediaObjectWithOriginalName($storageType, $mediaObject, $fileName);

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

    protected function retrieveBean(SugarBean $moduleBean, string $id): ?SugarBean
    {
        $this->init();
        $bean = $moduleBean->retrieve($id);
        $this->close();

        return $bean;
    }
}
