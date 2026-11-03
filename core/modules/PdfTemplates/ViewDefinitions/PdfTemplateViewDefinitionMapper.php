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

namespace App\Module\PdfTemplates\ViewDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

/**
 * Record view of the PDF templates (metadata/detailviewdefs.php, also the legacy detail view):
 * the header, body and footer are edited in the HTML editor (PdfTemplateFieldDefinitionMapper).
 * The custom code of the legacy detail view, which makes the record view show them read-only, is
 * for the legacy page only.
 */
class PdfTemplateViewDefinitionMapper implements ViewDefinitionMapperInterface
{
    protected const EDITABLE = ['pdfheader', 'description', 'pdffooter'];

    public function getKey(): string
    {
        return 'pdf-template-view';
    }

    public function getModule(): string
    {
        return 'pdf-templates';
    }

    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $recordView = $definition->getRecordView() ?? [];

        foreach ($recordView['panels'] ?? [] as $panelKey => $panel) {
            foreach ($panel['rows'] ?? [] as $rowKey => $row) {
                foreach ($row['cols'] ?? [] as $colKey => $cell) {
                    if (!in_array($cell['name'] ?? '', self::EDITABLE, true)) {
                        continue;
                    }

                    unset($cell['customCode'], $cell['readonly']);
                    $recordView['panels'][$panelKey]['rows'][$rowKey]['cols'][$colKey] = $cell;
                }
            }
        }

        $definition->setRecordView($recordView);
    }
}
