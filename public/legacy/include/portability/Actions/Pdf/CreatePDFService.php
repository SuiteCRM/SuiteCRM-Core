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

require_once 'modules/AOS_PDF_Templates/templateParser.php';

class CreatePDFService
{

    public function writePDF(PDFEngine $pdf, array $pdfContent): PDFEngine
    {
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
    public function parseTemplate(SugarBean $moduleBean, $template, array $objectArr, $userFormat): array
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
        $text = str_replace("<p><pagebreak /></p>", "<pagebreak />", $text);
        $text = preg_replace_callback(
            '/{DATE\s+(.*?)}/',
            function ($matches) {
                return date($matches[1]);
            },
            $text
        );
        $header = preg_replace($search, $replace, (string)$template->pdfheader);
        $footer = preg_replace($search, $replace, (string)$template->pdffooter);

        $this->parseLineItems($moduleBean, $text);

        $converted = templateParser::parse_template($text, $objectArr, $userFormat);
        $header = templateParser::parse_template($header, $objectArr, $userFormat);
        $footer = templateParser::parse_template($footer, $objectArr, $userFormat);

        $printable = str_replace("\n", "<br />", (string)$converted);
        return [$header, $footer, $printable];
    }

    public function deleteTempFile(string $filePath): void
    {
        unlink($filePath);
    }

    public function getUploadDir(): string
    {
        global $sugar_config;
        return $sugar_config['upload_dir'] ?? '';
    }

    /**
     * @param SugarBean $moduleBean
     * @param $text
     */
    protected function parseLineItems(SugarBean $moduleBean, &$text): void
    {
        [$lineItems, $lineItemsGroups] = $this->getLineItemsData($moduleBean);
        $moduleKey = strtolower($moduleBean->module_dir);

        $text = str_replace("\$aos_quotes", "\$" . $moduleKey, $text);
        $text = str_replace("\$aos_invoices", "\$" . $moduleKey, $text);
        $text = str_replace("\$total_amt", "\$" . $moduleKey . "_total_amt", $text);
        $text = str_replace("\$discount_amount", "\$" . $moduleKey . "_discount_amount", $text);
        $text = str_replace("\$subtotal_amount", "\$" . $moduleKey . "_subtotal_amount", $text);
        $text = str_replace("\$tax_amount", "\$" . $moduleKey . "_tax_amount", $text);
        $text = str_replace("\$shipping_amount", "\$" . $moduleKey . "_shipping_amount", $text);
        $text = str_replace("\$total_amount", "\$" . $moduleKey . "_total_amount", $text);

        $text = $this->populateGroupLines($text, $lineItemsGroups, $lineItems);
    }

    protected function getLineItemsData(SugarBean $moduleBean): array
    {
        $lineItemsGroups = [];
        $lineItems = [];

        $quotedId = $moduleBean->db->quote($moduleBean->id);
        $sql = "SELECT pg.id, pg.product_id, pg.group_id FROM aos_products_quotes pg LEFT JOIN aos_line_item_groups lig ON pg.group_id = lig.id WHERE pg.parent_type = '" . $moduleBean->object_name . "' AND pg.parent_id = '" . $quotedId . "' AND pg.deleted = 0 ORDER BY lig.number ASC, pg.number ASC";
        $res = $moduleBean->db->query($sql);
        while ($row = $moduleBean->db->fetchByAssoc($res)) {
            $lineItemsGroups[$row['group_id']][$row['id']] = $row['product_id'];
            $lineItems[$row['id']] = $row['product_id'];
        }


        return [$lineItems, $lineItemsGroups];
    }

    protected function populateGroupLines($text, $lineItemsGroups, $lineItems, $element = 'table')
    {
        $firstValue = '';
        $firstNum = 0;

        $lastValue = '';
        $lastNum = 0;

        $startElement = '<' . $element;
        $endElement = '</' . $element . '>';


        $groups = BeanFactory::newBean('AOS_Line_Item_Groups');
        foreach ($groups->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id') || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos((string) $text, '$aos_line_item_groups_' . $name);
                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_line_item_groups_' . $name;
                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_line_item_groups_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }
        if ($firstValue !== '' && $lastValue !== '') {
            //Converting Text
            $parts = explode($firstValue, $text);
            $text = $parts[0];
            $parts = explode($lastValue, $parts[1]);
            if ($lastValue === $firstValue) {
                $groupPart = $firstValue . $parts[0];
            } else {
                $groupPart = $firstValue . $parts[0] . $lastValue;
            }

            if ((is_countable($lineItemsGroups) ? count($lineItemsGroups) : 0) != 0) {
                //Read line start <tr> value
                $tcount = strrpos($text, $startElement);
                $lsValue = substr($text, $tcount);
                $tcount = strpos($lsValue, ">") + 1;
                $lsValue = substr($lsValue, 0, $tcount);


                //Read line end values
                $tcount = strpos($parts[1], $endElement) + strlen($endElement);
                $leValue = substr($parts[1], 0, $tcount);

                //Converting Line Items
                $obb = array();

                $tdTemp = explode($lsValue, $text);

                $groupPart = $lsValue . $tdTemp[(is_countable($tdTemp) ? count($tdTemp) : 0) - 1] . $groupPart . $leValue;

                $text = $tdTemp[0];

                foreach ($lineItemsGroups as $group_id => $lineItemsArray) {
                    $groupPartTemp = $this->populateProductLines($groupPart, $lineItemsArray);
                    $groupPartTemp = $this->populateServiceLines($groupPartTemp, $lineItemsArray);

                    $obb['AOS_Line_Item_Groups'] = $group_id;
                    $text .= templateParser::parse_template($groupPartTemp, $obb);
                    $text .= '<br />';
                }
                $tcount = strpos($parts[1], $endElement) + strlen($endElement);
                $parts[1] = substr($parts[1], $tcount);
            } else {
                $tcount = strrpos($text, $startElement);
                $text = substr($text, 0, $tcount);

                $tcount = strpos($parts[1], $endElement) + strlen($endElement);
                $parts[1] = substr($parts[1], $tcount);
            }

            $text .= $parts[1];
        } else {
            $text = $this->populateProductLines($text, $lineItems);
            $text = $this->populateServiceLines($text, $lineItems);
        }


        return $text;
    }

    protected function populateProductLines($text, $lineItems, $element = 'tr')
    {
        $firstValue = '';
        $firstNum = 0;

        $lastValue = '';
        $lastNum = 0;

        $startElement = '<' . $element;
        $endElement = '</' . $element . '>';

        //Find first and last valid line values
        $product_quote = BeanFactory::newBean('AOS_Products_Quotes');
        foreach ($product_quote->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id') || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos((string) $text, '$aos_products_quotes_' . $name);

                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_products_quotes_' . $name;
                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_products_quotes_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }

        $product = BeanFactory::newBean('AOS_Products');
        foreach ($product->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id') || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos((string) $text, '$aos_products_' . $name);
                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_products_' . $name;


                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_products_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }

        if ($firstValue !== '' && $lastValue !== '') {

            //Converting Text
            $tparts = explode($firstValue, $text);
            $temp = $tparts[0];

            //check if there is only one line item
            if ($firstNum === $lastNum) {
                $linePart = $firstValue;
            } else {
                $tparts = explode($lastValue, $tparts[1]);
                $linePart = $firstValue . $tparts[0] . $lastValue;
            }


            $tcount = strrpos($temp, $startElement);
            $lsValue = substr($temp, $tcount);
            $tcount = strpos($lsValue, ">") + 1;
            $lsValue = substr($lsValue, 0, $tcount);

            //Read line end values
            $tcount = strpos($tparts[1], $endElement) + strlen($endElement);
            $leValue = substr($tparts[1], 0, $tcount);
            $tdTemp = explode($lsValue, $temp);

            $linePart = $lsValue . $tdTemp[(is_countable($tdTemp) ? count($tdTemp) : 0) - 1] . $linePart . $leValue;
            $parts = explode($linePart, $text);
            $text = $parts[0];

            //Converting Line Items
            if ((is_countable($lineItems) ? count($lineItems) : 0) != 0) {
                foreach ($lineItems as $id => $productId) {
                    if ($productId != null && $productId != '0') {
                        $obb['AOS_Products_Quotes'] = $id;
                        $obb['AOS_Products'] = $productId;
                        $text .= templateParser::parse_template($linePart, $obb);
                    }
                }
            }
            $partsCount = is_countable($parts) ? count($parts) : 0;

            for ($i = 1; $i < $partsCount; $i++) {
                $text .= $parts[$i];
            }
        }
        return $text;
    }

    function populateServiceLines($text, $lineItems, $element = 'tr')
    {
        $firstValue = '';
        $firstNum = 0;

        $lastValue = '';
        $lastNum = 0;

        $startElement = '<' . $element;
        $endElement = '</' . $element . '>';

        $text = str_replace("\$aos_services_quotes_service", "\$aos_services_quotes_product", (string) $text);

        //Find first and last valid line values
        $product_quote = BeanFactory::newBean('AOS_Products_Quotes');
        foreach ($product_quote->field_defs as $name => $arr) {
            if (!((isset($arr['dbType']) && strtolower($arr['dbType']) == 'id') || $arr['type'] == 'id' || $arr['type'] == 'link')) {
                $curNum = strpos($text, '$aos_services_quotes_' . $name);
                if ($curNum) {
                    if ($curNum < $firstNum || $firstNum == 0) {
                        $firstValue = '$aos_products_quotes_' . $name;
                        $firstNum = $curNum;
                    }
                    if ($curNum > $lastNum) {
                        $lastValue = '$aos_products_quotes_' . $name;
                        $lastNum = $curNum;
                    }
                }
            }
        }
        if ($firstValue !== '' && $lastValue !== '') {
            $text = str_replace("\$aos_products", "\$aos_null", $text);
            $text = str_replace("\$aos_services", "\$aos_products", $text);

            //Converting Text
            $tparts = explode($firstValue, $text);
            $temp = $tparts[0];

            //check if there is only one line item
            if ($firstNum === $lastNum) {
                $linePart = $firstValue;
            } else {
                $tparts = explode($lastValue, $tparts[1]);
                $linePart = $firstValue . $tparts[0] . $lastValue;
            }

            $tcount = strrpos($temp, $startElement);
            $lsValue = substr($temp, $tcount);
            $tcount = strpos($lsValue, ">") + 1;
            $lsValue = substr($lsValue, 0, $tcount);

            //Read line end values
            $tcount = strpos($tparts[1], $endElement) + strlen($endElement);
            $leValue = substr($tparts[1], 0, $tcount);
            $tdTemp = explode($lsValue, $temp);

            $linePart = $lsValue . $tdTemp[(is_countable($tdTemp) ? count($tdTemp) : 0) - 1] . $linePart . $leValue;
            $parts = explode($linePart, $text);
            $text = $parts[0];

            //Converting Line Items
            if ((is_countable($lineItems) ? count($lineItems) : 0) != 0) {
                foreach ($lineItems as $id => $productId) {
                    if ($productId == null || $productId == '0') {
                        $obb['AOS_Products_Quotes'] = $id;
                        $text .= templateParser::parse_template($linePart, $obb);
                    }
                }
            }
            $partsCount = is_countable($parts) ? count($parts) : 0;

            for ($i = 1; $i < $partsCount; $i++) {
                $text .= $parts[$i];
            }
        }
        return $text;
    }
}
