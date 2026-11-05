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

namespace App\Module\PdfTemplates\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;

/**
 * Fields of a PDF template in the record view (the legacy editor is unchanged): the body
 * (description), header and footer in the HTML editor (Squire), with the HTML source and the
 * variables of the template type (field logic pdf-template-variables, process
 * pdf-template-editor). The samples of the legacy editor are the pdf-load-sample action of the
 * create and edit views (metadata/detailviewdefs.php). A new template has the page size and
 * orientation shown first by the legacy editor (empty vardef defaults).
 */
class PdfTemplateFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    /**
     * The HTML fields and their editor heights: fixed (with three editors, the dynamic height
     * of the editor grows with the page), the body taller.
     */
    protected const HTML_FIELDS = [
        'pdfheader' => ['minHeight' => '12vh', 'height' => '18vh', 'maxHeight' => '30vh'],
        'description' => ['minHeight' => '30vh', 'height' => '45vh', 'maxHeight' => '60vh'],
        'pdffooter' => ['minHeight' => '12vh', 'height' => '18vh', 'maxHeight' => '30vh'],
    ];

    protected const DEFAULTS = [
        'page_size' => 'A4',
        'orientation' => 'Portrait',
    ];

    protected const EDIT_BUTTONS = [
        ['variables'],
        ['bold', 'italic', 'underline', 'strikethrough'],
        ['font', 'size'],
        ['textColour', 'highlight'],
        ['insertLink'],
        ['unorderedList', 'orderedList', 'indentMore', 'indentLess'],
        ['alignLeft', 'alignCenter', 'alignRight', 'justify'],
        ['clearFormatting'],
        ['html'],
    ];

    public function getKey(): string
    {
        return 'pdf-template-fields';
    }

    public function getModule(): string
    {
        return 'pdf-templates';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        foreach (self::HTML_FIELDS as $field => $editor) {
            if (empty($vardefs[$field])) {
                continue;
            }

            $vardefs[$field] = array_merge($vardefs[$field], [
                'type' => 'html',
                'displayType' => 'squire',
                'inline_edit' => false,
                'metadata' => [
                    // Raw HTML of the PDF engine (tables, inline styles), as the legacy editor.
                    'trustHTML' => true,
                    'purifyHtml' => false,
                    'squire' => [
                        'edit' => $editor + ['buttonLayout' => self::EDIT_BUTTONS],
                        'detail' => $editor + ['buttonLayout' => array_slice(self::EDIT_BUTTONS, 1, -1)],
                    ],
                ],
                'logic' => [
                    'pdf-template-variables' => [
                        'key' => 'pdf-template-variables',
                        'modes' => ['edit', 'create'],
                        'params' => [
                            'fieldDependencies' => ['type'],
                        ],
                    ],
                ],
            ]);
        }

        foreach (self::DEFAULTS as $field => $default) {
            if (!empty($vardefs[$field]) && ($vardefs[$field]['default'] ?? '') === '') {
                $vardefs[$field]['default'] = $default;
            }
        }

        $definition->setVardef($vardefs);
    }
}
