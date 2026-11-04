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

namespace App\Module\Surveys\ViewDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

/**
 * SuiteCRM 8 record views of the surveys and of the survey responses (legacy layouts of
 * the modules) without the fields whose value is the HTML of the legacy pages: the URL and the
 * questions of a survey (survey_url_display, survey_questions_display), the answers of a response
 * (question_responses_display). The survey questions and survey response answers widgets of
 * the core show them.
 */
class SurveyViewDefinitionMapper implements ViewDefinitionMapperInterface
{
    protected const HTML_FIELDS = ['survey_url_display', 'survey_questions_display', 'question_responses_display'];

    public function __construct(protected string $module = 'surveys')
    {
    }

    public function getKey(): string
    {
        return 'survey-view-' . $this->module;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $recordView = $definition->getRecordView() ?? [];

        $panels = [];
        foreach ($recordView['panels'] ?? [] as $panel) {
            $rows = [];
            foreach ($panel['rows'] ?? [] as $row) {
                $row['cols'] = array_values(array_filter(
                    $row['cols'] ?? [],
                    static fn($cell) => !in_array($cell['name'] ?? '', self::HTML_FIELDS, true)
                ));
                if ($row['cols']) {
                    $rows[] = $row;
                }
            }
            if ($rows) {
                $panel['rows'] = $rows;
                $panels[] = $panel;
            }
        }
        $recordView['panels'] = $panels;

        $definition->setRecordView($recordView);
    }
}
