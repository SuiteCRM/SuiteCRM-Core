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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

/**
 * Set Connector Properties (legacy Connectors&action=ModifyProperties): the properties of
 * each connector (its required configuration fields), saved by ConnectorsController::
 * action_SaveModifyProperties(); Test Connector (action_RunTest(), which saves them first, as the
 * legacy page).
 */
class ConnectorPropertiesPage extends ConnectorSettingsPage
{
    public function getPageKey(): string
    {
        return 'connector-properties';
    }

    protected function getPage(): array
    {
        require_once 'include/connectors/utils/ConnectorUtils.php';

        $sections = [];
        $values = [];
        foreach ($this->connectors(static fn (\source $source) => $source->isEnabledInAdminProperties() && !empty($source->getRequiredConfigFields())) as $id => $connector) {
            $strings = \ConnectorUtils::getConnectorStrings($id);
            $properties = $connector['source']->getProperties();
            $fields = [];
            foreach ($connector['source']->getRequiredConfigFields() as $field) {
                $fields[] = ['name' => $id . '_' . $field, 'label' => $this->decode((string)($strings[$field] ?? $field)), 'type' => 'text'];
                $values[$id . '_' . $field] = $this->decode((string)($properties[$field] ?? ''));
            }
            $section = $this->section('', $fields, 'Connectors', null, ['test:' . $id => 'LBL_TEST_SOURCE']);
            $section['title'] = $this->connectorName($id, $connector['entry']);
            if (!empty($strings['LBL_LICENSING_INFO'])) {
                $section['intro'] = strip_tags($this->decode((string)$strings['LBL_LICENSING_INFO']));
            }
            $sections[] = $section;
        }

        if (!$sections) {
            $sections[] = $this->section('LBL_MODIFY_PROPERTIES_TITLE', [], 'Connectors', 'LBL_NO_PROPERTIES');
        }

        return [
            'title' => $this->text('LBL_MODIFY_PROPERTIES_TITLE', 'Connectors'),
            'sections' => $sections,
            'values' => $values,
            'back' => self::BACK,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $this->runController('SaveModifyProperties', $this->request($values));

        return null;
    }

    protected function runAction(string $action, array $values): array
    {
        if (!str_starts_with($action, 'test:')) {
            return parent::runAction($action, $values);
        }
        $id = substr($action, 5);
        if (!array_key_exists($id, $this->connectors(static fn () => true))) {
            return parent::runAction($action, $values);
        }

        $message = trim(strip_tags((string)$this->runController('RunTest', ['source_id' => $id] + $this->request($values, $id))));
        $success = $message === $this->text('LBL_TEST_SOURCE_SUCCESS', 'Connectors', false);

        return ['status' => $success ? 'success' : 'error', 'message' => $message];
    }

    /**
     * The request of the legacy form: the connectors (source0, source1...) and their properties
     * (<connector>_<property>), of one connector or all.
     *
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    protected function request(array $values, ?string $only = null): array
    {
        $request = [];
        $index = 0;
        foreach ($this->connectors(static fn (\source $source) => $source->isEnabledInAdminProperties() && !empty($source->getRequiredConfigFields())) as $id => $connector) {
            if ($only !== null && $id !== $only) {
                continue;
            }
            $request['source' . $index++] = $id;
            foreach ($connector['source']->getRequiredConfigFields() as $field) {
                $request[$id . '_' . $field] = trim((string)($values[$id . '_' . $field] ?? ''));
            }
        }

        return $request;
    }
}
