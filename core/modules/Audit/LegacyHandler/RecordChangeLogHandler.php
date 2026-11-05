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

namespace App\Module\Audit\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The change log of a record (legacy Audit Popup_picker.php) for the change log modal: the
 * audited fields of its module and its changes (Audit::get_audit_list), newest first, with the
 * values formatted as the legacy popup (SugarField getChangeLogSmarty).
 */
class RecordChangeLogHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'record-change-log';

    protected const LABELS = [
        'LBL_CHANGE_LOG',
        'LBL_FIELD_NAME',
        'LBL_OLD_NAME',
        'LBL_NEW_VALUE',
        'LBL_CREATED_BY',
        'LBL_LIST_DATE',
        'LBL_AUDITED_FIELDS'
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The change log of a record of a legacy module, null when the record cannot be viewed.
     */
    public function load(string $module, string $id): ?array
    {
        return $this->inLegacy(function () use ($module, $id) {
            global $focus, $current_user, $current_language;

            $bean = \BeanFactory::getBean($module, $id);
            // As Audit::get_audit_list(), which ends the request otherwise.
            if (!$bean || empty($bean->id) || !$bean->ACLAccess('', $bean->isOwner($current_user->id))) {
                return null;
            }

            require_once 'modules/Audit/Audit.php';
            require_once 'include/SugarFields/SugarFieldHandler.php';

            // Audit::get_audit_list() reads the record from the global $focus and the request.
            $previousFocus = $focus ?? null;
            $previousRecord = $_REQUEST['record'] ?? null;
            $focus = $bean;
            $_REQUEST['record'] = $bean->id;

            try {
                $list = (new \Audit())->get_audit_list();
            } finally {
                $focus = $previousFocus;
                if ($previousRecord === null) {
                    unset($_REQUEST['record']);
                } else {
                    $_REQUEST['record'] = $previousRecord;
                }
            }

            $changes = [];
            foreach ($list as $audit) {
                $changes[] = [
                    'id' => (string)($audit['id'] ?? ''),
                    'field' => rtrim($this->text($audit['field_name'] ?? ''), ': '),
                    'before' => $this->format($audit, 'before'),
                    'after' => $this->format($audit, 'after'),
                    'user' => $this->text($audit['created_by'] ?? ''),
                    'date' => $this->text($audit['date_created'] ?? ''),
                ];
            }

            $fields = [];
            foreach ($bean->getAuditEnabledFieldDefinitions() as $definition) {
                $label = $definition['vname'] ?? $definition['label'] ?? '';
                $fields[] = $this->text(str_replace(':', '', (string)translate($label, $bean->module_dir)));
            }
            sort($fields, SORT_NATURAL | SORT_FLAG_CASE);

            $strings = return_module_language($current_language, 'Audit');
            $labels = [];
            foreach (self::LABELS as $label) {
                $labels[$label] = rtrim($this->text($strings[$label] ?? $label), ': ');
            }

            return [
                'module' => $this->text((string)translate('LBL_MODULE_NAME', $bean->module_dir)),
                'name' => $this->text((string)$bean->get_summary_text()),
                'audited' => $bean->is_AuditEnabled(),
                'fields' => $fields,
                'changes' => $changes,
                'labels' => $labels,
            ];
        });
    }

    /**
     * A value of a change as the legacy popup: the string value, or the text value when there is
     * none, through the field of its type.
     *
     * @param array<string, mixed> $audit
     */
    protected function format(array $audit, string $side): string
    {
        if (empty($audit['before_value_string']) && empty($audit['after_value_string'])) {
            $value = $audit[$side . '_value_text'] ?? '';
        } else {
            $value = $audit[$side . '_value_string'] ?? '';
        }

        if (!empty($audit['data_type'])) {
            $vardef = ['name' => 'audit_field', 'type' => $audit['data_type']];
            $field = \SugarFieldHandler::getSugarField($audit['data_type']);
            $value = $field->getChangeLogSmarty([$vardef['name'] => $value], $vardef, [], $vardef['name']);
        }

        return $this->text(is_scalar($value) ? (string)$value : '');
    }

    /**
     * Plain text of a legacy value (HTML encoded by the database layer).
     */
    protected function text($value): string
    {
        $text = (string)$value;
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);
        $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === 'NULL' ? '' : $text;
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
