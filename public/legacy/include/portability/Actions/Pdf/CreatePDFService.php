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

use SuiteCRM\PDF\Exceptions\PDFException;
use SuiteCRM\PDF\PDFEngine;
use SuiteCRM\PDF\PDFWrapper;

require_once('modules/AOS_PDF_Templates/templateParser.php');

class CreatePDFService {

    public function writePDF(PDFEngine $pdf, array $pdfContent): PDFEngine {
        $pdf->writeHeader($pdfContent['header']);
        $pdf->writeFooter($pdfContent['footer']);
        $pdf->writeHTML($pdfContent['printable']);
        return $pdf;
    }

    public function buildPDFConfig(SugarBean $template): array
    {
        if (!$template->id) {
            return [];
        }

        return [
            'mode' => 'en',
            'page_size' => $template->page_size,
            'font' => 'DejaVuSansCondensed',
            'margin_left' => $template->margin_left,
            'margin_right' => $template->margin_right,
            'margin_top' => $template->margin_top,
            'margin_bottom' => $template->margin_bottom,
            'margin_header' => $template->margin_header,
            'margin_footer' => $template->margin_footer,
            'orientation' => $template->orientation
        ];
    }

    public function createPdf(array $config): PDFEngine
    {
        try {
            $pdf = PDFWrapper::getPDFEngine();
            $pdf->configurePDF($config);
        } catch (PDFException $e) {
            LoggerManager::getLogger()->warn('PDFException: ' . $e->getMessage());
        }

        return $pdf;
    }

    /**
     * @param $template
     * @param array $object_arr
     * @return array
     */
    public function parseTemplate($template, array $object_arr): array
    {
        $search = array(
            '@<script[^>]*?>.*?</script>@si',        // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',        // Strip out HTML tags
            '@([\r\n])[\s]+@',            // Strip out white space
            '@&(quot|#34);@i',            // Replace HTML entities
            '@&(amp|#38);@i',
            '@&(lt|#60);@i',
            '@&(gt|#62);@i',
            '@&(nbsp|#160);@i',
            '@&(iexcl|#161);@i',
            '@<address[^>]*?>@si'
        );

        $replace = array(
            '',
            '',
            '\1',
            '"',
            '&',
            '<',
            '>',
            ' ',
            chr(161),
            '<br>'
        );

        $text = preg_replace($search, $replace, (string)$template->description);
        $text = preg_replace_callback(
            '/{DATE\s+(.*?)}/',
            function ($matches) {
                return date($matches[1]);
            },
            $text
        );
        $header = preg_replace($search, $replace, (string)$template->pdfheader);
        $footer = preg_replace($search, $replace, (string)$template->pdffooter);


        $converted = templateParser::parse_template($text, $object_arr);
        $header = templateParser::parse_template($header, $object_arr);
        $footer = templateParser::parse_template($footer, $object_arr);

        $printable = str_replace("\n", "<br />", (string)$converted);
        return [$header, $footer, $printable];
    }

    public function deleteTempFile(string $filePath): void
    {
        unlink($filePath);
    }

    public function getUploadDir()
    {
        global $sugar_config;
        return $sugar_config['upload_dir'];
    }
}
