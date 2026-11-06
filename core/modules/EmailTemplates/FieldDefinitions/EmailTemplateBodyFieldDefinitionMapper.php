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

namespace App\Module\EmailTemplates\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;
use App\Module\EmailTemplates\LegacyHandler\CalculateTemplateInjectorVariables;

/**
 * Bodies of an email template in the record view (the legacy editor is unchanged):
 *  - the HTML body (body_html) in the HTML editor (Squire), with the template
 *    variables of the legacy editor (Account, Contact/Lead/Target, User: modules_to_show of
 *    the variable injector) and the HTML source;
 *  - the plain text body (body) for a text only template; each is hidden when the other is
 *    used (text_only).
 */
class EmailTemplateBodyFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    protected const EDIT_BUTTONS = [
        ['variables'],
        ['bold', 'italic', 'underline', 'strikethrough'],
        ['font', 'size'],
        ['textColour', 'highlight'],
        ['insertLink'],
        ['unorderedList', 'orderedList', 'indentMore', 'indentLess'],
        ['alignLeft', 'alignCenter', 'alignRight', 'justify'],
        ['quote', 'unquote'],
        ['clearFormatting'],
        ['html'],
    ];

    public function __construct(protected CalculateTemplateInjectorVariables $templateVariables)
    {
    }

    public function getKey(): string
    {
        return 'email-template-body';
    }

    public function getModule(): string
    {
        return 'email-templates';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();
        if (empty($vardefs['body_html']) || empty($vardefs['body'])) {
            return;
        }

        $modules = $this->templateVariables->getModules('EmailTemplates');
        $editor = [
            'dynamicHeight' => true,
            'dynamicHeightAncestor' => '.field-layout',
            'dynamicHeightAdjustment' => -140,
        ];

        // The legacy vardefs swap the labels of the two bodies.
        $vardefs['body_html'] = array_merge($vardefs['body_html'], [
            'vname' => 'LBL_BODY',
            'type' => 'html',
            'displayType' => 'squire',
            'inline_edit' => false,
            'metadata' => [
                'trustHTML' => true,
                'purifyHtml' => false,
                'squire' => [
                    'edit' => $editor + ['buttonLayout' => self::EDIT_BUTTONS],
                    'detail' => $editor + ['buttonLayout' => array_slice(self::EDIT_BUTTONS, 1, -1)],
                    'variables' => [
                        'modules' => $modules,
                        'fieldDefs' => $this->templateVariables->getFieldDefs('EmailTemplates', $modules),
                    ],
                ],
            ],
            'displayLogic' => $this->hideWhen('hide-on-text-only', ['operator' => 'is-true']),
        ]);

        $vardefs['body'] = array_merge($vardefs['body'], [
            'vname' => 'LBL_PLAIN_TEXT',
            'type' => 'text',
            'inline_edit' => false,
            'rows' => 12,
            // Also hidden while text_only is empty (new template).
            'displayLogic' => $this->hideWhen('hide-on-html', ['operator' => 'not-equal', 'values' => ['true', '1']]),
        ]);

        $definition->setVardef($vardefs);
    }

    /**
     * Display logic hiding a body in every mode when text_only matches $condition.
     */
    protected function hideWhen(string $key, array $condition): array
    {
        return [
            $key => [
                'key' => 'displayType',
                'modes' => ['detail', 'edit', 'create'],
                'params' => [
                    'fieldDependencies' => ['text_only'],
                    'activeOnFields' => [
                        'text_only' => [$condition],
                    ],
                ],
            ],
        ];
    }
}
