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

namespace App\Module\Home\LegacyHandler;

use AOR_Report;
use App\Module\Reports\LegacyHandler\ReportParametersTrait;
use BeanFactory;

/**
 * Options of the report dashlet (AORReportsDashlet), as its legacy form
 * (dashletConfigure.tpl): title, report, "only show charts", charts of the report
 * and the parameters of its conditions. Saved by the dashlet's saveOptions() with
 * the inputs of that form. Runs in the legacy application (DashboardEditHandler).
 */
trait ReportDashletOptionsTrait
{
    use ReportParametersTrait;

    /**
     * Options of a report dashlet: the reports the user can list, and the charts and
     * parameters of the chosen report.
     */
    protected function describeReportOptions(object $dashlet): array
    {
        $reportId = (string)($dashlet->report->id ?? '');
        $report = $this->describeReport($reportId, $dashlet->params ?? []);

        return [
            'report' => $reportId,
            'reports' => $this->getReportChoices(),
            'onlyCharts' => !empty($dashlet->onlyCharts),
            'charts' => [
                'value' => array_values(array_map('strval', (array)($dashlet->charts ?? []))),
                'options' => $report['charts']['options'] ?? [],
            ],
            'parameters' => $report['parameters'] ?? [],
        ];
    }

    /**
     * Charts and parameters of a report; the parameter values of the dashlet override
     * the ones of the report conditions.
     */
    protected function describeReport(string $reportId, array $override = []): array
    {
        $report = $reportId !== '' ? BeanFactory::getBean('AOR_Reports', $reportId) : null;
        if (!$report instanceof AOR_Report || empty($report->id) || !$report->ACLAccess('view')) {
            return [];
        }

        $charts = [];
        foreach ($report->get_linked_beans('aor_charts', 'AOR_Charts') as $index => $chart) {
            // Unnamed charts as the legacy form shows them.
            $label = trim((string)$chart->name) ?: translate('LBL_CHAR_UNNAMED_DEFAULT_TITLE', 'AOR_Reports') . ' #' . ($index + 1);
            $charts[] = ['value' => (string)$chart->id, 'label' => html_entity_decode($label, ENT_QUOTES)];
        }

        return [
            'charts' => ['value' => [], 'options' => $charts],
            'parameters' => $this->describeReportParameters($report, $override),
        ];
    }

    /**
     * Reports the user can list, by name (the legacy report popup).
     */
    protected function getReportChoices(): array
    {
        if (!\ACLController::checkAccess('AOR_Reports', 'list', true)) {
            return [];
        }

        $choices = [];
        foreach (BeanFactory::newBean('AOR_Reports')->get_full_list('aor_reports.name') ?: [] as $report) {
            $choices[] = ['value' => (string)$report->id, 'label' => html_entity_decode((string)$report->name, ENT_QUOTES)];
        }

        return $choices;
    }

    /**
     * The request of the legacy form for saveOptions(); null when a value is not one the
     * form offers.
     */
    protected function buildReportOptionsRequest(array $values): ?array
    {
        $reportId = (string)($values['report'] ?? '');
        $request = [
            'dashletTitle' => $this->cleanTitle((string)($values['title'] ?? '')),
            'aor_report_id' => $reportId,
        ];
        if ($reportId === '') {
            return $request + $this->toParameterRequest([]);
        }
        if (!in_array($reportId, array_column($this->getReportChoices(), 'value'), true)) {
            return null;
        }

        $report = $this->describeReport($reportId);
        if (!empty($values['onlyCharts'])) {
            $request['onlyCharts'] = 'on';
        }

        $charts = array_values(array_unique(array_map('strval', (array)($values['charts'] ?? []))));
        if (array_diff($charts, array_column($report['charts']['options'] ?? [], 'value'))) {
            return null;
        }
        if ($charts !== []) {
            $request['charts'] = $charts;
        }

        $parameters = $this->checkReportParameters(
            $report['parameters'] ?? [],
            is_array($values['parameters'] ?? null) ? $values['parameters'] : []
        );
        if ($parameters === null) {
            return null;
        }

        return $request + $this->toParameterRequest($parameters);
    }
}
