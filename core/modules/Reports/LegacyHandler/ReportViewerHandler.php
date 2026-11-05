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

namespace App\Module\Reports\LegacyHandler;

use AOR_Report;
use App\Engine\LegacyHandler\LegacyClassNamesTrait;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The report page of SuiteCRM 8 (record view of a report), as the legacy report
 * page (AOR_Reports DetailView) shows it: the charts of the report, its parameters, and
 * the report (a table, or a table per value of the grouping field), from the legacy query,
 * with its parameters, pages and totals (ReportData). The chart figures are the ones the legacy charts draw
 * (AOR_Report::build_report_chart(), AOR_Chart). The records of the report can be added to
 * a target list (legacy action addToProspectList), and the report exported as the legacy
 * Export (CSV) and Download PDF actions.
 */
class ReportViewerHandler extends LegacyHandler
{
    use ReportParametersTrait;
    use ReportDataTrait;
    use LegacyClassNamesTrait;

    public const HANDLER_KEY = 'report-viewer';

    // Chart types of the report editor (AOR_Chart::getValidChartTypes()).
    protected const CHART_TYPES = ['bar', 'line', 'pie', 'radar', 'rose', 'grouped_bar', 'stacked_bar'];

    // Width of the content of a PDF page, in pixels of the PDF engine (72 a inch): A4 portrait
    // and its margins.
    protected const PDF_WIDTH = 500;

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The report page with the given parameter values (by parameter key; the values of the
     * report otherwise): parameters, charts, and the report, first page of each table. Null
     * when the report cannot be viewed or a value is not valid.
     */
    public function load(string $id, array $given): ?array
    {
        return $this->inLegacy(function () use ($id, $given) {
            $report = $this->prepare($id, $given);
            if ($report === null) {
                return null;
            }

            return [
                'id' => $report->id,
                'module' => $this->moduleNameMapper->toFrontEnd((string)$report->report_module),
                'graphsPerRow' => max(1, (int)$report->graphs_per_row),
                'parameters' => $this->describeReportParameters($report, $report->user_parameters),
                'charts' => $this->runReport(fn() => $this->describeCharts($report)),
            ] + $this->runReport(fn() => $this->readReportTables($report));
        });
    }

    /**
     * Another page of a table of the report: of the report, or of a group of a grouped
     * report (value of the grouping field, as the legacy action changeReportPage).
     */
    public function page(string $id, array $given, ?string $group, int $offset): ?array
    {
        return $this->inLegacy(function () use ($id, $given, $group, $offset) {
            $report = $this->prepare($id, $given);
            if ($report === null) {
                return null;
            }

            return $this->runReport(fn() => $this->readReportTable($report, max(0, $offset), (string)$group));
        });
    }

    /**
     * Adds the records of the report (with the given parameter values) to a target list, as
     * the legacy action addToProspectList. Returns the number of records added, or null when
     * the report or the target list cannot be used, or the records of the report cannot be in
     * a target list.
     */
    public function addToTargetList(string $id, array $given, string $targetListId): ?int
    {
        return $this->inLegacy(function () use ($id, $given, $targetListId) {
            global $beanList;

            require_once 'modules/Relationships/Relationship.php';

            $report = $this->prepare($id, $given);
            $targetList = BeanFactory::getBean('ProspectLists', $targetListId);
            if ($report === null || !$targetList || empty($targetList->id) || !$targetList->ACLAccess('edit')) {
                return null;
            }

            $db = \DBManagerFactory::getInstance();
            $relationship = \Relationship::retrieve_by_modules($report->report_module, 'ProspectLists', $db);
            $module = new $beanList[$report->report_module]();
            $query = $this->runReport(fn() => $report->build_report_query());
            if (empty($relationship) || !$query) {
                return null;
            }

            $ids = [];
            $result = $db->query($query);
            while ($row = $db->fetchByAssoc($result)) {
                if (!empty($row[$module->table_name . '_id'])) {
                    $ids[$row[$module->table_name . '_id']] = true;
                }
            }
            $ids = array_keys($ids);
            if ($ids === []) {
                return 0;
            }

            foreach ($targetList->field_defs as $name => $definition) {
                if (($definition['type'] ?? '') === 'link' && ($definition['relationship'] ?? '') === $relationship) {
                    $targetList->load_relationship($name);
                    $targetList->$name->add($ids);
                }
            }

            return count($ids);
        });
    }

    /**
     * The report (with the given parameter values) as the legacy Export (CSV file,
     * AOR_Report::build_report_csv()) or Download PDF (AOR_ReportsController::action_downloadPDF(),
     * with the chart images of the page): file name, type and content (base64). Null when the
     * user cannot export the report or a value is not valid.
     *
     * @param string[] $chartImages PNG data URLs of the charts of the page (PDF)
     */
    public function export(string $id, array $given, string $format, array $chartImages = []): ?array
    {
        return $this->inLegacy(function () use ($id, $given, $format, $chartImages) {
            $report = $this->prepare($id, $given);
            if ($report === null || !$report->ACLAccess('Export')) {
                return null;
            }

            if ($format === 'pdf') {
                $content = $this->runReport(fn() => $this->buildPdf($report, $chartImages), 'DownloadPDF');
                $type = 'application/pdf';
            } else {
                [$content, $type] = $this->runReport(fn() => $this->buildCsv($report));
            }
            if ($content === null) {
                return null;
            }

            return [
                'name' => html_entity_decode((string)$report->name, ENT_QUOTES) . ($format === 'pdf' ? '.pdf' : '.csv'),
                'type' => $type,
                'content' => base64_encode($content),
            ];
        });
    }

    /**
     * The CSV file of the report (AOR_Report::build_report_csv(), which prints it and exits) in
     * the export charset, with its type.
     *
     * @return array{0: string, 1: string}
     */
    protected function buildCsv(AOR_Report $report): array
    {
        global $locale, $sugar_config;

        require_once 'include/export_utils.php';
        require_once 'include/CleanCSV.php';

        $delimiter = getDelimiter();
        $clean = new \SuiteCRM\CleanCSV();
        $enclose = static fn($value): string => '"' . $clean->escapeField((string)$value) . '"';

        $fields = [];
        $headers = [];
        foreach ($this->getFieldAttributes($report) as $name => $attribute) {
            $fields[$name] = $attribute;
            if ($attribute['display']) {
                $headers[] = $enclose($attribute['label']);
            }
        }
        $lines = [implode($delimiter, $headers)];

        $query = $report->build_report_query();
        $db = \DBManagerFactory::getInstance();
        $result = $query ? $db->query($query) : false;
        while ($result && $row = $db->fetchByAssoc($result, false)) {
            $cells = [];
            foreach ($fields as $name => $attribute) {
                if (!$attribute['display']) {
                    continue;
                }
                if ($attribute['function'] != '' || $attribute['format'] != '') {
                    $cells[] = $enclose($row[$name] ?? '');
                    continue;
                }
                $value = $this->formatValue($attribute, $row, $name);
                $cells[] = strpos($value, 'checkbox') !== false ? (string)($row[$name] ?? '') : $enclose(trim(strip_tags($value)));
            }
            $lines[] = implode($delimiter, $cells);
        }
        $csv = implode("\r\n", $lines);

        // As printCSV().
        if (!empty($sugar_config['export_excel_compatible'])) {
            return [$locale->addBOM($csv, 'UTF-8'), 'text/comma-separated-values; charset=UTF-8'];
        }
        $charset = $locale->getExportCharset();

        return [$locale->translateCharset($csv, 'UTF-8', $charset), 'text/comma-separated-values; charset=' . $charset];
    }

    /**
     * The PDF of the report (AOR_ReportsController::action_downloadPDF()): company logo, name,
     * the charts (an image of the charts of the page, graphs_per_row a row) and the whole report.
     *
     * The PDF engine only reads images from /tmp (SuiteTCPDF::Image(), validate_external_host():
     * the data URLs the legacy page posts are refused), so the chart images are written there
     * while the PDF is made.
     *
     * @param string[] $chartImages
     */
    protected function buildPdf(AOR_Report $report, array $chartImages): ?string
    {
        $logo = explode('?', \SugarThemeRegistry::current()->getImageURL('company_logo.png'), 2)[0];
        $head = '<table style="width: 100%; text-align: center;" border="0" cellpadding="2" cellspacing="2"><tbody style="text-align: left;">'
            . '<tr style="text-align: left;"><td style="text-align: left;"><p><img src="' . htmlspecialchars($logo, ENT_QUOTES) . '" style="float: left;"/>&nbsp;</p></td></tr>'
            . '<tr style="text-align: left;"><td style="text-align: left;"><h2>' . htmlspecialchars(strtoupper(html_entity_decode((string)$report->name, ENT_QUOTES)), ENT_QUOTES) . '</h2></td></tr>'
            . '</tbody></table>';

        $prefix = 'data:image/png;base64,';
        $images = [];
        foreach ($chartImages as $image) {
            $png = is_string($image) && str_starts_with($image, $prefix) ? base64_decode(substr($image, strlen($prefix)), true) : false;
            if ($png !== false && str_starts_with($png, "\x89PNG")) {
                $images[] = '/tmp/report-chart-' . create_guid() . '.png';
                file_put_contents(end($images), $png);
            }
        }
        foreach ($images as $image) {
            $head .= '<br /><img src="' . $image . '" width="' . self::PDF_WIDTH . '" />';
        }

        $content = $report->build_group_report(-1, false);
        $file = 'cache/report-' . create_guid() . '.pdf';
        // An image path starting with / is read under the document root by the PDF engine.
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
        try {
            $pdf = \SuiteCRM\PDF\PDFWrapper::getPDFEngine();
            $pdf->configurePDF(['mode' => 'en', 'font' => 'DejaVuSansCondensed']);
            $pdf->addCSS((string)file_get_contents(get_custom_file_if_exists('modules/AOR_Reports/pdf/pdf.css')));
            $pdf->writeFooter('{PAGENO}');
            $_SERVER['DOCUMENT_ROOT'] = '';
            $pdf->writeHTML($head, 2);
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
            $pdf->writeHTML((string)$content, 3);
            $pdf->outputPDF($file, 'F');

            return is_file($file) ? (string)file_get_contents($file) : null;
        } catch (\Exception $exception) {
            \LoggerManager::getLogger()->warn('Report PDF failed: ' . $exception->getMessage());

            return null;
        } finally {
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
            foreach ([$file, ...$images] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    /**
     * A value of a row of the report as on the record (getModuleField(), DetailView); the
     * value itself when the field cannot be shown (a report field no longer in the module).
     */
    protected function formatValue(array $attribute, array $row, string $name): string
    {
        try {
            return (string)getModuleField(
                $attribute['module'],
                $attribute['field'],
                $attribute['field'],
                'DetailView',
                $row[$name] ?? '',
                '',
                $row[$attribute['alias'] . '_currency_id'] ?? ''
            );
        } catch (\Throwable $exception) {
            return (string)($row[$name] ?? '');
        }
    }

    /**
     * The fields of the report by their column in its query (label and position): field,
     * label, shown or not, function, format, module and table alias.
     */
    protected function getFieldAttributes(AOR_Report $report): array
    {
        global $beanList;

        $attributes = [];
        foreach ($report->getReportFields() as $index => $field) {
            $path = unserialize(base64_decode((string)$field->module_path), ['allowed_classes' => false]) ?: [];
            $module = $report->report_module;
            $alias = (new $beanList[$report->report_module]())->table_name;
            if (($path[0] ?? '') !== $report->report_module) {
                foreach ($path as $relationship) {
                    if (!empty($relationship)) {
                        $module = getRelatedModule($module, $relationship);
                        $alias .= ':' . $relationship;
                    }
                }
            }
            $attributes[str_replace(' ', '_', (string)$field->label) . $index] = [
                'field' => $field->field,
                'label' => (string)$field->label,
                'display' => (bool)$field->display,
                'function' => $field->field_function,
                'format' => $field->format,
                'module' => $module,
                'alias' => $alias,
            ];
        }

        return $attributes;
    }

    /**
     * The report, with the given parameter values as its user parameters (as the report
     * dashlet sets them); null when the report cannot be viewed or a value is not valid.
     */
    protected function prepare(string $id, array $given): ?AOR_Report
    {
        $report = BeanFactory::getBean('AOR_Reports', $id);
        if (!$report instanceof AOR_Report || empty($report->id) || !$report->ACLAccess('view')) {
            return null;
        }

        $values = $this->checkReportParameters($this->describeReportParameters($report, []), $given);
        if ($values === null) {
            return null;
        }
        $report->user_parameters = $this->toUserParameters($values);

        return $report;
    }

    /**
     * The charts of the report with the figures the legacy charts draw (RGraph charts of
     * AOR_Chart::buildChartHTMLRGraph()): a value per row of the report for the X axis
     * field, or for the grouped and stacked bars the number of rows per value of the
     * grouping field.
     */
    protected function describeCharts(AOR_Report $report): array
    {
        $charts = $report->get_linked_beans('aor_charts', 'AOR_Charts');
        if (!$charts) {
            return [];
        }

        $columns = $this->getReportColumns($report);
        // As the legacy chart: the last field shown as a group.
        $groupColumn = null;
        foreach ($columns as $column) {
            if ($column['groupDisplay'] && $column['key'] !== null) {
                $groupColumn = $column;
            }
        }

        $rows = $this->getChartRows($report, $columns);
        $described = [];
        foreach ($charts as $chart) {
            $x = $columns[$chart->x_field] ?? null;
            $y = $columns[$chart->y_field] ?? null;
            if (!in_array($chart->type, self::CHART_TYPES, true) || !$x || !$y || $x['key'] === null || $y['key'] === null) {
                continue;
            }

            $grouped = in_array($chart->type, ['grouped_bar', 'stacked_bar'], true);
            $described[] = [
                'id' => (string)$chart->id,
                'name' => html_entity_decode((string)$chart->name, ENT_QUOTES),
                'type' => (string)$chart->type,
                'xLabel' => $x['label'],
                'yLabel' => $y['label'],
                'items' => $grouped
                    ? $this->getGroupedChartItems($rows, $x['key'], $groupColumn['key'] ?? null)
                    : $this->getChartItems($rows, $x['key'], $y['key']),
            ];
        }

        return $described;
    }

    /**
     * Rows of the report for its charts (AOR_Report::build_report_chart()): the values that are
     * not numbers are shown as on the record (list labels, names, dates...).
     */
    protected function getChartRows(AOR_Report $report, array $columns): array
    {
        $query = $report->build_report_query();
        if (!$query) {
            return [];
        }

        $db = \DBManagerFactory::getInstance();
        $rows = [];
        $result = $db->query($query);
        while ($row = $db->fetchByAssoc($result, false)) {
            foreach ($columns as $column) {
                $key = $column['key'];
                if ($key !== null && $column['function'] !== 'COUNT' && $column['format'] === '' && !is_numeric($row[$key] ?? null)) {
                    $row[$key] = $this->getReportValueText($column, $row[$key] ?? '');
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * A bar, point or slice per X axis value with its Y axis value (0 when not a number). The
     * legacy chart draws a bar per row, with the same label for the rows of the same X axis
     * value (report grouped on several fields): their values are added.
     */
    protected function getChartItems(array $rows, string $xKey, string $yKey): array
    {
        $values = [];
        foreach ($rows as $row) {
            $name = (string)($row[$xKey] ?? '');
            $values[$name] = ($values[$name] ?? 0) + (float)($row[$yKey] ?? 0);
        }

        return array_map(
            static fn($name, $value) => ['name' => (string)$name, 'value' => $value],
            array_keys($values),
            array_values($values)
        );
    }

    /**
     * A group of bars per X axis value, with the number of rows per value of the grouping
     * field of the report (AOR_Chart::getRGraphGroupedBarChartData()).
     */
    protected function getGroupedChartItems(array $rows, string $xKey, ?string $groupKey): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $name = (string)($row[$xKey] ?? '');
            $series = $groupKey !== null ? (string)($row[$groupKey] ?? '') : '';
            $groups[$name][$series] = ($groups[$name][$series] ?? 0) + 1;
        }

        $items = [];
        foreach ($groups as $name => $series) {
            $items[] = [
                'name' => (string)$name,
                'series' => array_map(
                    static fn($seriesName, $value) => ['name' => (string)$seriesName, 'value' => $value],
                    array_keys($series),
                    array_values($series)
                ),
            ];
        }

        return $items;
    }

    /**
     * Runs report code as the legacy report page action (it reads the module and action of the
     * request: DownloadPDF styles the groups for the PDF). What it prints (scripts of the
     * relate fields it formats) is dropped: it would end up in the response.
     */
    protected function runReport(callable $callback, string $action = 'DetailView')
    {
        $request = $_REQUEST;
        $_REQUEST['module'] = 'AOR_Reports';
        $_REQUEST['action'] = $action;
        ob_start();

        try {
            return $this->withLegacyClassNames($callback);
        } finally {
            ob_end_clean();
            $_REQUEST = $request;
        }
    }

    /**
     * Runs in the legacy application. The warnings and notices of the report code (a report
     * field or condition no longer in the module...) do not stop it, as on the legacy page.
     */
    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();
        $ignored = E_WARNING | E_NOTICE | E_DEPRECATED | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED;
        set_error_handler(static fn(int $level): bool => ($level & $ignored) !== 0);

        try {
            return $callback();
        } finally {
            restore_error_handler();
            $this->close();
        }
    }
}
