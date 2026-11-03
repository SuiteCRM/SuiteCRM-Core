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
 * The HTML of a template section (blocks of the legacy email template builder, Mozaik) in the
 * HTML editor (Squire) of the record view, with its source, as the legacy editor.
 */
class TemplateSectionFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    protected const EDIT_BUTTONS = [
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
        return 'template-section-fields';
    }

    public function getModule(): string
    {
        return 'template-section-line';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();
        if (empty($vardefs['description'])) {
            return;
        }

        $editor = [
            'dynamicHeight' => true,
            'dynamicHeightAncestor' => '.field-layout',
            'dynamicHeightAdjustment' => -140,
        ];
        $vardefs['description'] = array_merge($vardefs['description'], [
            'type' => 'html',
            'displayType' => 'squire',
            'inline_edit' => false,
            'metadata' => [
                'trustHTML' => true,
                'purifyHtml' => false,
                'squire' => [
                    'edit' => $editor + ['buttonLayout' => self::EDIT_BUTTONS],
                    'detail' => $editor + ['buttonLayout' => array_slice(self::EDIT_BUTTONS, 0, -1)],
                ],
            ],
        ]);

        $definition->setVardef($vardefs);
    }
}
