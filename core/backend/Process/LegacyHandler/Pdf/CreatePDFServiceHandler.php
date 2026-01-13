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

use App\Engine\LegacyHandler\LegacyHandler;
use CreatePDFService;
use SugarBean;
use SuiteCRM\PDF\PDFEngine;

class CreatePDFServiceHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'create-pdf-service-handler';

    protected ?CreatePDFService $mapper = null;

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    public function buildPDFConfig(SugarBean $template): array
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->buildPDFConfig($template);

        $this->close();

        return $result;
    }

    /**
     * Get mapper. Initialize it if needed
     * @return CreatePDFService
     */
    protected function getMapper(): CreatePDFService
    {
        if ($this->mapper !== null) {
            return $this->mapper;
        }

        require_once 'include/portability/Actions/Pdf/CreatePDFService.php';

        $this->mapper = new CreatePDFService();

        return $this->mapper;
    }

    public function createPdf(array $config): PDFEngine
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->createPdf($config);

        $this->close();

        return $result;
    }

    public function writePDF(PDFEngine $pdf, array $pdfContent): PDFEngine
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->writePDF($pdf, $pdfContent);

        $this->close();

        return $result;
    }

    public function parseTemplate($template, array $object_arr): array
    {
        $this->init();

        $mapper = $this->getMapper();

        [$header, $footer, $printable] = $mapper->parseTemplate($template, $object_arr);

        $this->close();

        return [$header, $footer, $printable];
    }

    public function deleteTempFile(string $filePath): void
    {
        $this->init();

        $mapper = $this->getMapper();

        $mapper->deleteTempFile($filePath);

        $this->close();
    }

    public function getUploadDir(): string
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->getUploadDir();

        $this->close();

        return $result;
    }
}
