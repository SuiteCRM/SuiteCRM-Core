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

namespace App\Module\Surveys\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The surveys in SuiteCRM 8, as the legacy pages of the Surveys module:
 * - the questions of a survey and their options (survey_questions_display() of the legacy detail
 *   and edit views), changed as the legacy edit view saves them (Surveys::save() with the posted
 *   survey_questions_* values), while the survey has no response;
 * - the public URL of a survey (survey_url_display());
 * - the report of a survey (legacy view.reports.php: responses, surveys sent, and the answers by
 *   question);
 * - the answers of a survey response (question_responses_display());
 * - the Duplicate of a survey with its questions and options.
 */
class SurveyQuestionsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'survey-questions';

    /** The question types with options (editsurveyquestions.tpl). */
    protected const OPTION_TYPES = ['Radio', 'Dropdown', 'Multiselect', 'Matrix'];

    protected const LABELS = [
        'LBL_SURVEY_QUESTIONS_DISPLAY', 'LBL_SURVEY_URL_DISPLAY', 'LBL_SURVEY_QUESTION', 'LBL_SURVEY_TEXT', 'LBL_SURVEY_TYPE',
        'LBL_SURVEY_ACTIONS', 'LBL_SURVEY_NEW_QUESTION', 'LBL_OPTIONS', 'LBL_ADD_OPTION', 'LBL_CANT_EDIT_RESPONDED',
        'LBL_VIEW_SURVEY_REPORTS', 'LBL_SURVEYS_SENT', 'LBL_SURVEY_DISTINCT', 'LBL_RESPONSE_ANSWER', 'LBL_RESPONSE_CONTACT',
        'LBL_RESPONSE_TIME', 'LBL_RESPONSE_COUNT', 'LBL_CHECKED', 'LBL_UNCHECKED', 'LBL_STARS', 'LBL_SHOW_RESPONSES',
        'LBL_HIDE_RESPONSES', 'LBL_SURVEYS_SURVEYRESPONSES_FROM_SURVEYRESPONSES_TITLE', 'LBL_SURVEY_VIEW_EDIT_QUESTIONS',
        'LBL_SURVEY_VIEW_NO_QUESTION', 'LBL_SURVEY_VIEW_RESPONSES', 'LBL_SURVEY_VIEW_NOT_PUBLIC', 'LBL_SURVEY_VIEW_QUESTION_REQUIRED',
        'LBL_SURVEY_VIEW_MOVE_UP', 'LBL_SURVEY_VIEW_MOVE_DOWN', 'LBL_SURVEY_VIEW_NO_ANSWER',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The questions of a survey with their options, its URL, whether they can be changed, the
     * question types and the labels.
     */
    public function questions(string $surveyId): array
    {
        return $this->inLegacy(fn() => $this->questionsData($this->survey($surveyId, 'view')));
    }

    /**
     * Saves the questions of a survey as its legacy edit view: the questions and options posted
     * are created or changed in their order, the others are deleted.
     *
     * @param array<int, array> $questions id, name, type, options (id, name)
     */
    public function saveQuestions(string $surveyId, array $questions): array
    {
        return $this->inLegacy(function () use ($surveyId, $questions) {
            global $app_list_strings;

            $survey = $this->survey($surveyId, 'edit');
            if ($this->hasResponses($survey)) {
                throw new AccessDeniedHttpException('The survey has responses');
            }

            $existing = $this->questionList($survey);
            $existingIds = array_column($existing, 'id');
            $types = $app_list_strings['surveys_question_type'] ?? [];
            $request = ['survey_questions_supplied' => '1'];
            $kept = [];
            foreach (array_values($questions) as $index => $question) {
                $name = trim((string)($question['name'] ?? ''));
                $type = (string)($question['type'] ?? '');
                if ($name === '' || !isset($types[$type])) {
                    throw new \InvalidArgumentException('Question');
                }
                $id = in_array((string)($question['id'] ?? ''), $existingIds, true) ? (string)$question['id'] : '';
                $kept[] = $id;
                $request['survey_questions_names'][$index] = $name;
                $request['survey_questions_ids'][$index] = $id;
                $request['survey_questions_types'][$index] = $type;
                $request['survey_questions_sortorder'][$index] = (string)$index;
                $request['survey_questions_deleted'][$index] = '0';

                $currentNames = $id !== '' ? array_column($existing[array_search($id, $existingIds, true)]['options'], 'name', 'id') : [];
                $current = array_map('strval', array_keys($currentNames));
                $options = in_array($type, self::OPTION_TYPES, true) && is_array($question['options'] ?? null) ? array_values($question['options']) : [];
                $optionIds = [];
                foreach ($options as $option) {
                    $optionName = trim((string)($option['name'] ?? ''));
                    if ($optionName === '') {
                        throw new \InvalidArgumentException('Option');
                    }
                    $optionId = in_array((string)($option['id'] ?? ''), $current, true) ? (string)$option['id'] : '';
                    $optionIds[] = $optionId;
                    $request['survey_questions_options'][$index][] = $optionName;
                    $request['survey_questions_options_id'][$index][] = $optionId;
                    $request['survey_questions_options_deleted'][$index][] = '0';
                }
                // The options removed in the page (all of them for a type without options).
                foreach (array_diff($current, $optionIds) as $optionId) {
                    $request['survey_questions_options'][$index][] = $currentNames[$optionId];
                    $request['survey_questions_options_id'][$index][] = $optionId;
                    $request['survey_questions_options_deleted'][$index][] = '1';
                }
            }

            // The questions removed in the page.
            $index = count($questions);
            foreach ($existing as $question) {
                if (!in_array($question['id'], $kept, true)) {
                    $request['survey_questions_names'][$index] = $question['name'];
                    $request['survey_questions_ids'][$index] = $question['id'];
                    $request['survey_questions_types'][$index] = $question['type'];
                    $request['survey_questions_sortorder'][$index] = (string)$question['sortOrder'];
                    $request['survey_questions_deleted'][$index] = '1';
                    $index++;
                }
            }

            $this->withRequest($request, fn() => $survey->save());

            return $this->questionsData($this->survey($surveyId, 'view'));
        });
    }

    /**
     * The report of a survey (legacy view.reports.php): the responses, the surveys sent by the
     * campaigns, and by question the counts of the answers (choices, checkbox, rating, scale and
     * matrix) or the answers (text and dates) with their contact and date.
     */
    public function report(string $surveyId): array
    {
        return $this->inLegacy(function () use ($surveyId) {
            global $app_list_strings, $timedate, $current_language;

            $survey = $this->survey($surveyId, 'view');
            $strings = return_module_language($current_language, 'Surveys');
            $db = \DBManagerFactory::getInstance();
            $sent = $db->fetchOne(
                "SELECT COUNT(campaign_log.target_id) AS sent, COUNT(DISTINCT campaign_log.target_id) AS distinct_sent FROM campaigns"
                . " INNER JOIN campaign_log ON (campaigns.id = campaign_log.campaign_id AND campaign_log.deleted = 0"
                . " AND activity_type = 'targeted' AND campaign_log.target_type = 'Contacts')"
                . ' WHERE campaigns.survey_id = ' . $db->quoted($survey->id) . ' AND campaigns.deleted = 0'
            ) ?: [];

            $questions = [];
            foreach ($this->questionList($survey) as $question) {
                $counts = [];
                switch ($question['type']) {
                    case 'Checkbox':
                        $counts = [0 => $this->text($strings, 'LBL_UNCHECKED'), 1 => $this->text($strings, 'LBL_CHECKED')];
                        break;
                    case 'Multiselect':
                    case 'Radio':
                    case 'Dropdown':
                        $counts = array_column($question['options'], 'name', 'id');
                        break;
                    case 'Rating':
                        foreach (range(1, 5) as $stars) {
                            $counts[$stars] = $stars . ' ' . $this->text($strings, 'LBL_STARS');
                        }
                        break;
                    case 'Scale':
                        $counts = array_combine(range(1, 10), array_map('strval', range(1, 10)));
                        break;
                }
                $questions[$question['id']] = $question + [
                    'counts' => array_map(static fn($label, $key) => ['key' => (string)$key, 'label' => (string)$label, 'count' => 0], $counts, array_keys($counts)),
                    'matrix' => $question['type'] === 'Matrix' ? array_map(fn($option) => [
                        'id' => $option['id'],
                        'label' => $option['name'],
                        'counts' => array_map(fn($label, $key) => ['key' => (string)$key, 'label' => $this->decode($label), 'count' => 0],
                            $app_list_strings['surveys_matrix_options'] ?? [], array_keys($app_list_strings['surveys_matrix_options'] ?? [])),
                    ], $question['options']) : [],
                    'answers' => [],
                ];
            }

            $responses = $survey->get_linked_beans('surveys_surveyresponses', 'SurveyResponses', 'surveyresponses.date_created') ?: [];
            foreach ($responses as $response) {
                foreach ($response->get_linked_beans('surveyresponses_surveyquestionresponses') ?: [] as $answer) {
                    $questionId = (string)$answer->surveyquestion_id;
                    if (!isset($questions[$questionId])) {
                        continue;
                    }
                    $question = &$questions[$questionId];
                    switch ($question['type']) {
                        case 'Checkbox':
                            $this->increment($question['counts'], !empty($answer->answer_bool) ? '1' : '0');
                            break;
                        case 'Multiselect':
                        case 'Radio':
                        case 'Dropdown':
                            foreach ($answer->get_linked_beans('surveyquestionoptions_surveyquestionresponses', 'SurveyQuestionOptions') ?: [] as $option) {
                                $this->increment($question['counts'], (string)$option->id);
                            }
                            break;
                        case 'Matrix':
                            foreach ($answer->get_linked_beans('surveyquestionoptions_surveyquestionresponses', 'SurveyQuestionOptions') ?: [] as $option) {
                                foreach ($question['matrix'] as &$row) {
                                    if ($row['id'] === $option->id) {
                                        $this->increment($row['counts'], (string)$answer->answer);
                                    }
                                }
                                unset($row);
                            }
                            break;
                        case 'Rating':
                        case 'Scale':
                            if (!empty($answer->answer)) {
                                $this->increment($question['counts'], (string)$answer->answer);
                            }
                            break;
                        default:
                            $value = in_array($question['type'], ['Date', 'DateTime'], true) ? (string)$answer->answer_datetime : (string)$answer->answer;
                            if ($question['type'] === 'Date' && ($date = $timedate->fromUser($value))) {
                                $value = $timedate->asUserDate($timedate->tzGMT($date));
                            }
                            $question['answers'][] = [
                                'answer' => $this->decode($value),
                                'contactId' => (string)$response->contact_id,
                                'contactName' => $this->decode((string)$response->contact_name),
                                'time' => (string)$answer->fetched_row['date_entered'],
                            ];
                    }
                    unset($question);
                }
            }

            return [
                'id' => $survey->id,
                'name' => $this->decode((string)$survey->name),
                'responses' => is_countable($responses) ? count($responses) : 0,
                'sent' => (int)($sent['sent'] ?? 0),
                'distinctSent' => (int)($sent['distinct_sent'] ?? 0),
                'questions' => array_values($questions),
                'labels' => $this->labels(),
            ];
        });
    }

    /**
     * The answers of a survey response by question, in the order of the questions
     * (question_responses_display()).
     */
    public function answers(string $responseId): array
    {
        return $this->inLegacy(function () use ($responseId) {
            global $app_list_strings, $timedate, $current_language;

            $response = \BeanFactory::getBean('SurveyResponses', $responseId);
            if (empty($response->id) || !$response->ACLAccess('view')) {
                throw new AccessDeniedHttpException('Survey response');
            }
            $strings = return_module_language($current_language, 'Surveys');

            $byQuestion = [];
            foreach ($response->get_linked_beans('surveyresponses_surveyquestionresponses', 'SurveyQuestionResponses') ?: [] as $answer) {
                $byQuestion[(string)$answer->surveyquestion_id][] = $answer;
            }

            $lines = [];
            foreach ($byQuestion as $questionId => $answers) {
                $question = \BeanFactory::getBean('SurveyQuestions', $questionId);
                if (empty($question->id)) {
                    continue;
                }
                $values = [];
                switch ($question->type) {
                    case 'Checkbox':
                        $values[] = $this->text($strings, !empty($answers[0]->answer_bool) ? 'LBL_CHECKED' : 'LBL_UNCHECKED');
                        break;
                    case 'Radio':
                    case 'Dropdown':
                    case 'Multiselect':
                    case 'Matrix':
                        $rows = [];
                        foreach ($answers as $answer) {
                            foreach ($answer->get_linked_beans('surveyquestionoptions_surveyquestionresponses', 'SurveyQuestionOptions') ?: [] as $option) {
                                $text = $this->decode((string)$option->name);
                                if ($question->type === 'Matrix') {
                                    $text .= ': ' . $this->decode((string)($app_list_strings['surveys_matrix_options'][$answer->answer] ?? $answer->answer));
                                }
                                $rows[(int)$option->sort_order * 1000 + count($rows)] = $text;
                            }
                        }
                        ksort($rows);
                        $values = array_values($rows);
                        break;
                    case 'DateTime':
                        $values[] = (string)$answers[0]->answer_datetime;
                        break;
                    case 'Date':
                        $date = $timedate->fromUser((string)$answers[0]->answer_datetime);
                        $values[] = $date ? $timedate->asUserDate($timedate->tzGMT($date)) : (string)$answers[0]->answer_datetime;
                        break;
                    case 'Rating':
                        $values[] = str_repeat('★', (int)$answers[0]->answer) ?: '';
                        break;
                    case 'Scale':
                        $values[] = $answers[0]->answer . '/10';
                        break;
                    default:
                        $values[] = $this->decode((string)$answers[0]->answer);
                }
                $lines[] = [
                    'order' => (int)$question->sort_order,
                    'question' => $this->decode((string)$question->name),
                    'answers' => $values,
                ];
            }
            usort($lines, static fn($a, $b) => $a['order'] <=> $b['order']);

            return ['answers' => $lines, 'labels' => $this->labels()];
        });
    }

    /**
     * Duplicates a survey with its questions and their options (the core Duplicate copies the
     * fields only). The copy is a draft of the user. Returns its id.
     */
    public function duplicate(string $surveyId): array
    {
        return $this->inLegacy(function () use ($surveyId) {
            global $current_user;

            $original = $this->survey($surveyId, 'view');
            $survey = \BeanFactory::newBean('Surveys');
            if (!$survey->ACLAccess('save')) {
                throw new AccessDeniedHttpException('Survey');
            }
            $this->copyFields($original, $survey);
            $survey->assigned_user_id = $current_user->id;
            $survey->status = 'Draft';
            $this->withRequest([], fn() => $survey->save());

            foreach ($this->questionList($original) as $question) {
                $copy = \BeanFactory::newBean('SurveyQuestions');
                $this->copyFields(\BeanFactory::getBean('SurveyQuestions', $question['id']), $copy);
                $copy->survey_id = $survey->id;
                $copy->save();
                foreach ($question['options'] as $option) {
                    $optionCopy = \BeanFactory::newBean('SurveyQuestionOptions');
                    $this->copyFields(\BeanFactory::getBean('SurveyQuestionOptions', $option['id']), $optionCopy);
                    $optionCopy->survey_question_id = $copy->id;
                    $optionCopy->save();
                }
            }

            return ['id' => $survey->id];
        });
    }

    protected function questionsData(\Surveys $survey): array
    {
        global $app_list_strings, $sugar_config;

        $types = [];
        foreach ($app_list_strings['surveys_question_type'] ?? [] as $value => $label) {
            $types[] = ['value' => (string)$value, 'label' => $this->decode($label), 'options' => in_array($value, self::OPTION_TYPES, true)];
        }

        return [
            'id' => $survey->id,
            'url' => $survey->status === 'Public' ? rtrim((string)$sugar_config['site_url'], '/') . '/index.php?entryPoint=survey&id=' . $survey->id : '',
            'hasResponses' => $this->hasResponses($survey),
            'canEdit' => (bool)$survey->ACLAccess('edit'),
            'questions' => $this->questionList($survey),
            'types' => $types,
            'labels' => $this->labels(),
        ];
    }

    /**
     * The questions of a survey in their order, with their options in their order.
     */
    protected function questionList(\Surveys $survey): array
    {
        global $app_list_strings;

        $questions = [];
        foreach ($survey->get_linked_beans('surveys_surveyquestions', 'SurveyQuestions', 'sort_order') ?: [] as $question) {
            $options = [];
            foreach ($question->get_linked_beans('surveyquestions_surveyquestionoptions', 'SurveyQuestionOptions', 'sort_order') ?: [] as $option) {
                $options[] = ['id' => (string)$option->id, 'name' => $this->decode((string)$option->name), 'sortOrder' => (int)$option->sort_order];
            }
            usort($options, static fn($a, $b) => $a['sortOrder'] <=> $b['sortOrder']);
            $questions[] = [
                'id' => (string)$question->id,
                'name' => $this->decode((string)$question->name),
                'type' => (string)$question->type,
                'typeLabel' => $this->decode((string)($app_list_strings['surveys_question_type'][$question->type] ?? $question->type)),
                'sortOrder' => (int)$question->sort_order,
                'options' => $options,
            ];
        }
        usort($questions, static fn($a, $b) => $a['sortOrder'] <=> $b['sortOrder']);

        return $questions;
    }

    protected function hasResponses(\Surveys $survey): bool
    {
        return !empty($survey->id) && (bool)$survey->get_linked_beans('surveys_surveyresponses');
    }

    protected function survey(string $surveyId, string $access): \Surveys
    {
        $survey = \BeanFactory::getBean('Surveys', $surveyId);
        if (!$survey instanceof \Surveys || empty($survey->id) || !$survey->ACLAccess($access)) {
            throw new AccessDeniedHttpException('Survey');
        }

        return $survey;
    }

    /**
     * Copies the database fields of a record to a new one.
     */
    protected function copyFields(\SugarBean $from, \SugarBean $to): void
    {
        foreach ($from->field_defs as $name => $definition) {
            if (($definition['source'] ?? 'db') === 'db' && !in_array($name, ['id', 'date_entered', 'date_modified', 'deleted'], true)) {
                $to->$name = $from->$name;
            }
        }
    }

    protected function increment(array &$counts, string $key): void
    {
        foreach ($counts as &$count) {
            if ($count['key'] === $key) {
                $count['count']++;
            }
        }
        unset($count);
    }

    protected function labels(): array
    {
        global $current_language;

        $strings = return_module_language($current_language, 'Surveys');
        $labels = [];
        foreach (self::LABELS as $label) {
            $labels[$label] = $this->text($strings, $label);
        }

        return $labels;
    }

    protected function text(array $strings, string $key): string
    {
        return rtrim($this->decode((string)($strings[$key] ?? $key)), ': ');
    }

    /**
     * Runs a legacy save with the given request values.
     *
     * @param array<string, mixed> $values
     */
    protected function withRequest(array $values, callable $callback): mixed
    {
        $request = $_REQUEST;
        $post = $_POST;
        $_REQUEST = $values;
        $_POST = $values;
        try {
            return $callback();
        } finally {
            $_REQUEST = $request;
            $_POST = $post;
        }
    }

    protected function decode($value): string
    {
        return html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
