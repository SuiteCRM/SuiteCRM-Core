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

use App\Data\LegacyHandler\PresetDataHandlers\RecordIdsDataHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Module\Reports\LegacyHandler\ReportDataTrait;
use App\Module\Service\ModuleNameMapperInterface;
use App\ViewDefinitions\LegacyHandler\FieldDefinitionsInjectorTrait;
use App\ViewDefinitions\Service\FieldAliasMapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The user's Home dashboard for the SuiteCRM 8 dashboard page, read from the same user
 * preferences as the legacy dashboard (pages and dashlets of the `Home` category), so both
 * always show the same dashboard.
 *
 * List dashlets (DashletGeneric with the generic display: My Accounts, My Calls...) run their
 * own legacy process(): same records, filters, "my items", invitees, sort and pages as the
 * legacy dashlet. Their records are then loaded in the SuiteCRM 8 list format, with the
 * dashlet's columns, so the page renders them with the standard field components. The chart
 * dashlets come with their figures, drawn by the page (see loadChart()), the report dashlets
 * with their table (loadReport()), the Activity Stream with its posts, the iFrame ones with
 * their page. The other dashlets (grouped reports or reports with charts, tools...) are shown
 * by the legacy page modules/Home/Dashlet.php.
 */
class DashboardHandler extends LegacyHandler
{
    use FieldDefinitionsInjectorTrait;
    use DashboardPreferencesTrait;
    use ReportDataTrait;

    public const HANDLER_KEY = 'dashboard';

    protected const TYPE = 'Home';

    // Columns of the call and meeting dashlets that are actions, not fields.
    protected const CLOSE_COLUMN = 'set_complete';
    protected const ACCEPT_COLUMN = 'set_accept_links';

    // Activities whose invitation the user answers from the dashlet.
    protected const INVITATION_MODULES = ['Calls' => 'calls_users', 'Meetings' => 'meetings_users'];
    protected const ACCEPT_STATUSES = ['accept', 'tentative', 'decline'];

    // Chart dashlets drawn natively (all the legacy ones), with the chart type of the page.
    protected const CHARTS = [
        'OpportunitiesByLeadSourceDashlet' => 'pie',
        'OpportunitiesByLeadSourceByOutcomeDashlet' => 'stacked',
        'OutcomeByMonthDashlet' => 'stacked-vertical',
        'PipelineBySalesStageDashlet' => 'funnel',
        'MyPipelineBySalesStageDashlet' => 'funnel',
        'CampaignROIChartDashlet' => 'bar',
    ];

    protected array $columnInterface = [
        'name' => '',
        'label' => '',
        'link' => false,
        'default' => true,
        'sortable' => true,
        'type' => '',
    ];

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected FieldDefinitionsProviderInterface $fieldDefinitions,
        protected FieldAliasMapper $fieldAliasMapper,
        protected RecordIdsDataHandler $recordIds,
        protected LoggerInterface $logger
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
     * Dashboard tabs, their columns and dashlets; the list dashlets of the
     * given tab come with their columns and first records.
     */
    public function getDashboard(int $page): array
    {
        return $this->inLegacy(fn() => $this->buildDashboard($page));
    }

    /**
     * Records of one list dashlet: a page, or a new sort (kept, as the
     * legacy dashlet keeps it).
     *
     * @param array{orderBy?: string, sortOrder?: string} $sort
     */
    public function getDashlet(string $id, int $offset, array $sort): array
    {
        return $this->inLegacy(function () use ($id, $offset, $sort) {
            $definition = $this->getDashletDefinitions()[$id] ?? null;
            if ($definition === null) {
                return ['id' => $id, 'error' => 'not-found'];
            }

            if (!empty($sort['orderBy'])) {
                $this->saveSort($id, $sort);
            }

            return $this->describeDashlet($id, $this->getDashletDefinitions()[$id], true, $offset) ?? ['id' => $id, 'error' => 'not-found'];
        });
    }

    /**
     * Answers the user's invitation to a call or a meeting (Accept, Tentative,
     * Decline links of the legacy dashlets).
     */
    public function setAcceptStatus(string $module, string $id, string $status): bool
    {
        if (!isset(self::INVITATION_MODULES[$module]) || !in_array($status, self::ACCEPT_STATUSES, true)) {
            return false;
        }

        return $this->inLegacy(function () use ($module, $id, $status) {
            global $current_user;

            $bean = \BeanFactory::getBean($module, $id);
            if (!$bean || !$this->isInvited($module, $id, $current_user->id)) {
                return false;
            }

            $bean->set_accept_status($current_user, $status);

            return true;
        });
    }

    /**
     * Closes (Held) a planned call or meeting, as the "Close" column of the
     * legacy dashlets.
     */
    public function closeActivity(string $module, string $id): bool
    {
        if (!isset(self::INVITATION_MODULES[$module])) {
            return false;
        }

        return $this->inLegacy(function () use ($module, $id) {
            $bean = \BeanFactory::getBean($module, $id);
            if (!$bean || $bean->status !== 'Planned' || !$bean->ACLAccess('edit')) {
                return false;
            }

            $bean->status = 'Held';
            $bean->save();

            return true;
        });
    }

    protected function buildDashboard(int $page): array
    {
        global $current_user, $app_strings, $sugar_config;

        $pages = $current_user->getPreference('pages', self::TYPE);
        $definitions = $this->getDashletDefinitions();

        if (empty($pages) || empty($definitions)) {
            [$pages, $definitions] = $this->createDefaultDashboard();
        }

        $pages = array_values($pages);
        $page = max(0, min($page, count($pages) - 1));

        $tabs = [];
        $dashlets = [];
        foreach ($pages as $index => $pageDefinition) {
            $numColumns = (int)($pageDefinition['numColumns'] ?? 0);
            $columns = [];

            foreach (array_values($pageDefinition['columns'] ?? []) as $columnIndex => $column) {
                // As the legacy dashboard: the columns after numColumns are not shown.
                if ($numColumns > 0 && $columnIndex >= $numColumns) {
                    break;
                }

                $ids = [];
                foreach ($column['dashlets'] ?? [] as $id) {
                    if (empty($id) || !isset($definitions[$id])) {
                        continue;
                    }

                    try {
                        $dashlet = $this->describeDashlet($id, $definitions[$id], $index === $page, 0, true);
                    } catch (\Throwable $e) {
                        // A dashlet that fails is shown by its legacy page: the tab still shows.
                        $this->logger->warning('Dashboard: dashlet ' . $id . ' could not be described: ' . $e->getMessage());
                        $dashlet = ['id' => $id, 'title' => $this->fallbackTitle($definitions[$id]), 'type' => 'legacy', 'configurable' => false];
                    }
                    if ($dashlet === null) {
                        continue;
                    }

                    $dashlets[$id] = $dashlet;
                    $ids[] = $id;
                }

                $columns[] = ['dashlets' => $ids];
            }

            $tabs[] = [
                'title' => $index === 0
                    ? ($app_strings['LBL_SUITE_DASHBOARD'] ?? '')
                    : html_entity_decode((string)($pageDefinition['pageTitle'] ?? ''), ENT_QUOTES),
                'columns' => $columns,
            ];
        }

        return [
            'page' => $page,
            'pages' => $tabs,
            'dashlets' => $dashlets,
            'locked' => !empty($sugar_config['lock_homepage']),
        ];
    }

    /**
     * Title and type of a dashlet; for a list dashlet, when $withData, its
     * columns and records. Null when the legacy dashboard does not show it
     * (module not available to the user, dashlet file removed...).
     *
     * $defer (dashboard load): the report and Activity Stream dashlets come without their
     * content (`deferred`), which they load themselves (action `dashlet`), so that a slow or
     * failing one does not hold or fail the tab.
     */
    protected function describeDashlet(string $id, array $definition, bool $withData, int $offset, bool $defer = false): ?array
    {
        $dashlet = $this->createDashlet($id, $definition);
        if ($dashlet === null) {
            return null;
        }
        $className = $definition['className'] ?? '';

        $data = [
            'id' => $id,
            'title' => html_entity_decode(strip_tags((string)($dashlet->title ?? '')), ENT_QUOTES),
            'type' => 'legacy',
            'configurable' => !empty($dashlet->isConfigurable),
        ];

        if (isset(self::CHARTS[$className]) && $dashlet instanceof \DashletGenericChart) {
            $data['type'] = 'chart';
            if (!$withData) {
                return $data;
            }

            try {
                return array_merge($data, ['chart' => $this->loadChart($dashlet, self::CHARTS[$className])]);
            } catch (\Throwable $e) {
                $this->logger->warning('Dashboard: chart dashlet ' . $id . ' (' . $className . '): ' . $e->getMessage());

                return array_merge($data, ['error' => 'load']);
            }
        }

        // Reports without groups or charts, as a table (the others stay in a frame).
        if ($dashlet instanceof \AORReportsDashlet) {
            $mode = $this->getReportMode($dashlet);
            if ($mode === 'legacy') {
                return $data;
            }

            $data['type'] = 'report';
            if (!$withData || $mode === 'none') {
                return array_merge($data, ['report' => null]);
            }
            if ($defer) {
                return array_merge($data, ['report' => null, 'deferred' => true]);
            }

            try {
                return array_merge($data, ['report' => $this->loadReport($dashlet, $offset)]);
            } catch (\Throwable $e) {
                $this->logger->warning('Dashboard: report dashlet ' . $id . ': ' . $e->getMessage());

                return array_merge($data, ['error' => 'load']);
            }
        }

        // Activity Stream (SugarFeed): its posts, replies and post form.
        if ($dashlet instanceof \SugarFeedDashlet) {
            $data['type'] = 'feed';
            if (!$withData) {
                return $data;
            }
            if ($defer) {
                return array_merge($data, ['deferred' => true]);
            }

            try {
                return array_merge($data, ['feed' => $this->loadFeed($dashlet, $offset)]);
            } catch (\Throwable $e) {
                $this->logger->warning('Dashboard: Activity Stream dashlet ' . $id . ': ' . $e->getMessage());

                return array_merge($data, ['error' => 'load']);
            }
        }

        // iFrame and SuiteCRM News: their page in a frame of the dashboard.
        if ($dashlet instanceof \iFrameDashlet) {
            return array_merge($data, ['type' => 'frame', 'frame' => $this->describeFrame($dashlet)]);
        }

        if (!$this->isListDashlet($dashlet)) {
            return $data;
        }

        $legacyModule = $dashlet->seedBean->module_name;
        $data['type'] = 'list';
        $data['module'] = $this->moduleNameMapper->toFrontEnd($legacyModule);
        $data['legacyModule'] = $legacyModule;

        if (!$withData) {
            return $data;
        }

        try {
            return array_merge($data, $this->loadList($id, $definition, $dashlet, $offset));
        } catch (\Throwable $e) {
            // e.g. no list access to the module: the dashlet shows a message.
            $this->logger->warning('Dashboard: list dashlet ' . $id . ' (' . $className . '): ' . $e->getMessage());

            return array_merge($data, ['error' => 'load']);
        }
    }

    /**
     * Title of a dashlet that could not be described: the one of its options.
     */
    protected function fallbackTitle(array $definition): string
    {
        $options = is_array($definition['options'] ?? null) ? $definition['options'] : [];
        $title = $options['title'] ?? $options['dashletTitle'] ?? $definition['className'] ?? '';

        return html_entity_decode(strip_tags((string)$title), ENT_QUOTES);
    }

    /**
     * Runs the legacy dashlet query (process()) for the page at $offset, then
     * loads its records in the SuiteCRM 8 list format.
     */
    protected function loadList(string $id, array $definition, \DashletGeneric $dashlet, int $offset): array
    {
        $legacyModule = $dashlet->seedBean->module_name;

        // The legacy dashlet reads its page from the request, as the
        // DynamicAction displayDashlet call of the legacy dashboard sends it.
        $request = $_REQUEST;
        $_REQUEST['module'] = self::TYPE;
        $_REQUEST[self::TYPE . '2_' . strtoupper($dashlet->seedBean->object_name) . '_offset'] = $offset;

        try {
            $this->withLegacyClassNames(fn() => $dashlet->process($definition['sort_options'] ?? []));
        } finally {
            $_REQUEST = $request;
        }

        $rows = $dashlet->lvs->data['data'] ?? [];
        $pageData = $dashlet->lvs->data['pageData'] ?? [];

        $ids = [];
        $acceptStatus = [];
        foreach ($rows as $row) {
            $rowId = $row['ID'] ?? '';
            if ($rowId === '' || in_array($rowId, $ids, true)) {
                continue;
            }
            $ids[] = $rowId;
            if (array_key_exists('ACCEPT_STATUS', $row)) {
                $acceptStatus[$rowId] = (string)$row['ACCEPT_STATUS'];
            }
        }

        $columns = $this->buildColumns($dashlet, $legacyModule);
        $records = $this->loadRecords($legacyModule, $ids);

        foreach ($records as &$record) {
            if (isset($acceptStatus[$record['id']])) {
                $record['attributes']['dashlet_accept_status'] = $acceptStatus[$record['id']];
            }
        }
        unset($record);

        $offsets = $pageData['offsets'] ?? [];

        // The legacy page data holds the reverse order (the next click on the header).
        $orderBy = strtolower((string)($pageData['ordering']['orderBy'] ?? ''));
        $orderBy = substr($orderBy, (int)strrpos('.' . $orderBy, '.'));
        $sortOrder = '';
        if ($orderBy !== '') {
            $sortOrder = strtoupper((string)($pageData['ordering']['sortOrder'] ?? '')) === 'ASC' ? 'DESC' : 'ASC';
        }

        return [
            'columns' => $columns,
            'records' => $records,
            'offsets' => [
                'current' => (int)($offsets['current'] ?? $offset),
                'next' => (int)($offsets['next'] ?? -1),
                'prev' => (int)($offsets['prev'] ?? -1),
                'total' => (int)($offsets['total'] ?? count($records)),
            ],
            'sort' => ['orderBy' => $orderBy, 'sortOrder' => $sortOrder],
        ];
    }

    /**
     * Data of a chart dashlet, from the legacy dashlet's own query (with its
     * options: lead sources, users, dates), so the chart shows the same
     * opportunities as the legacy one. The opportunity amounts are in thousands.
     *
     * Every amount is in the user's currency: the system currency amounts
     * (amount_usdollar) are converted to it, as the legacy Pipeline does; the
     * other legacy charts showed them unconverted with the user's symbol.
     *
     * - pie: series [{name, value, key}] by lead source;
     * - stacked: groups [{name, key, series: [{name, value, key}]}], lead source by sales stage;
     * - stacked-vertical: groups by closing month, by sales stage (Outcome by Month);
     * - funnel: series by sales stage, opportunities closing in the dashlet dates
     *   (all of them, or the user's: My Pipeline);
     * - bar: the campaign figures (Campaign ROI), in currency.
     * `link` gives the opportunities list filter of a click, as the legacy charts open it.
     */
    protected function loadChart(\DashletGenericChart $dashlet, string $type): array
    {
        global $current_user, $app_strings;

        // The user's currency; -99 is the system currency.
        $currency = $this->getCurrency($current_user->getPreference('currency'));
        $symbol = (string)$currency->symbol;
        $thousands = translate('LBL_OPP_THOUSANDS', 'Charts');

        $chart = [
            'type' => $type,
            'currency' => (string)$symbol,
            'unit' => is_string($thousands) ? $thousands : '',
            'subtitle' => translate('LBL_OPP_SIZE', 'Charts') . ' ' . $symbol . '1' . $thousands,
            'empty' => (string)$dashlet->noDataMessage,
            'series' => [],
        ];
        $none = trim((string)($app_strings['LBL_NONE'] ?? ''), '- ');

        switch ($type) {
            case 'pie':
                foreach ($dashlet->getChartData($this->chartQuery($dashlet)) as $row) {
                    $key = (string)($row['lead_source_key'] ?? $row['lead_source'] ?? '');
                    $chart['series'][] = [
                        'name' => (string)($row['lead_source'] ?? '') !== '' ? (string)$row['lead_source'] : $none,
                        'key' => $key,
                        'value' => round($currency->convertFromDollar((float)$row['total']), 2),
                    ];
                }
                $chart['link'] = ['params' => [], 'group' => '', 'item' => 'lead_source'];
                break;

            case 'stacked':
                $rows = $dashlet->sortData($dashlet->getChartData($this->chartQuery($dashlet)), 'lead_source', true, 'sales_stage', true, true);
                $groups = [];
                foreach ($rows as $row) {
                    $key = (string)($row['lead_source_dom_option'] ?? '');
                    if (!isset($groups[$key])) {
                        $groups[$key] = [
                            'name' => (string)($row['lead_source'] ?? '') !== '' ? (string)$row['lead_source'] : $none,
                            'key' => $key,
                            'series' => [],
                        ];
                    }
                    $groups[$key]['series'][] = [
                        'name' => (string)($row['sales_stage'] ?? '') !== '' ? (string)$row['sales_stage'] : $none,
                        'key' => (string)($row['sales_stage_dom_option'] ?? ''),
                        'value' => round($currency->convertFromDollar((float)$row['total']), 2),
                    ];
                }
                $chart['groups'] = array_values($groups);
                $chart['link'] = ['params' => [], 'group' => 'lead_source', 'item' => 'sales_stage'];
                break;

            case 'stacked-vertical':
                // Months in order (the query sorts them), sales stages in their list order.
                $rows = $dashlet->sortData($dashlet->getChartData($this->chartQuery($dashlet)), 'm', false, 'sales_stage', true, true);
                $groups = [];
                foreach ($rows as $row) {
                    $key = (string)($row['m'] ?? '');
                    if (!isset($groups[$key])) {
                        $groups[$key] = ['name' => $this->monthLabel($key), 'key' => $key, 'series' => []];
                    }
                    $groups[$key]['series'][] = [
                        'name' => (string)($row['sales_stage'] ?? '') !== '' ? (string)$row['sales_stage'] : $none,
                        'key' => (string)($row['sales_stage_dom_option'] ?? ''),
                        'value' => round($currency->convertFromDollar((float)$row['total']), 2),
                    ];
                }
                $chart['groups'] = array_values($groups);
                // The list opens on the month (yyyy-mm) and stage, for the users of the dashlet;
                // no user filter when they are all selected (the default of the options form).
                $userIds = array_values((array)($dashlet->obm_ids ?? []));
                if (!array_diff(array_keys(get_user_array(false)), $userIds)) {
                    $userIds = [];
                }
                $chart['link'] = [
                    'params' => array_filter(['assigned_user_id' => $userIds]),
                    'group' => 'date_closed',
                    'item' => 'sales_stage',
                ];
                break;

            case 'funnel':
                // Pipeline By Sales Stage converts its amounts in its query, with the currency
                // set in display(); My Pipeline By Sales Stage sums them in the system currency.
                $mine = $dashlet instanceof \MyPipelineBySalesStageDashlet;
                if (!$mine) {
                    (new \ReflectionProperty(\PipelineBySalesStageDashlet::class, 'currency'))->setValue($dashlet, $currency);
                }
                foreach ($dashlet->getChartData($this->chartQuery($dashlet)) as $row) {
                    $total = (float)$row['total'];
                    $chart['series'][] = [
                        'name' => (string)($row['value'] ?? ''),
                        'key' => (string)($row['key'] ?? ''),
                        'value' => round($mine ? $currency->convertFromDollar($total) : $total, 2),
                    ];
                }
                $chart['link'] = [
                    'params' => $mine
                        ? [
                            'start_range_date_closed' => (string)$dashlet->mypbss_date_start,
                            'end_range_date_closed' => (string)$dashlet->mypbss_date_end,
                            'assigned_user_id' => [(string)$current_user->id],
                        ]
                        : [
                            'start_range_date_closed' => (string)$dashlet->pbss_date_start,
                            'end_range_date_closed' => (string)$dashlet->pbss_date_end,
                        ],
                    'group' => '',
                    'item' => 'sales_stage',
                ];
                break;

            case 'bar':
                $chart['unit'] = '';
                $chart['subtitle'] = '';
                $campaignId = is_array($dashlet->campaign_id ?? null) ? ($dashlet->campaign_id[0] ?? null) : null;
                $hasValue = false;
                foreach ($campaignId ? $this->campaignRoi((string)$campaignId, $currency) : [] as $label => $value) {
                    $hasValue = $hasValue || $value !== 0.0;
                    $chart['series'][] = ['name' => $label, 'key' => '', 'value' => round($value, 2)];
                }
                // As the legacy chart: nothing to show without a campaign or figures.
                if (!$hasValue) {
                    $chart['series'] = [];
                }
                break;
        }

        return $chart;
    }

    /**
     * Figures of the Campaign ROI chart, in the user's currency, with the
     * labels and opportunities of the legacy chart (CampaignROIChartDashlet::
     * constructQuery()): value of the prospecting opportunities, revenue of the
     * won ones, actual cost, budget and expected revenue of the campaign. The
     * legacy chart added the won amounts in their own currencies and showed
     * the campaign figures in the campaign currency.
     *
     * @return array<string, float>
     */
    protected function campaignRoi(string $campaignId, \Currency $currency): array
    {
        $campaign = \BeanFactory::getBean('Campaigns', $campaignId);
        if (!$campaign || !$campaign->ACLAccess('view')) {
            return [];
        }

        $db = \DBManagerFactory::getInstance();
        $opportunities = static fn(string $stage): float => (float)$db->getOne(
            'SELECT SUM(amount_usdollar) FROM opportunities WHERE deleted = 0 AND campaign_id = '
            . $db->quoted($campaignId) . ' AND sales_stage = ' . $db->quoted($stage)
        );

        // Campaign figures: campaign currency → system currency.
        $campaignCurrency = $this->getCurrency($campaign->currency_id);
        $fromCampaign = static fn($value): float => $campaignCurrency->convertToDollar((float)$value);

        return array_map(static fn(float $value): float => $currency->convertFromDollar($value), [
            'Total Value' => $opportunities('Prospecting'),
            'Revenue' => $opportunities('Closed Won'),
            'Investment' => $fromCampaign($campaign->actual_cost),
            'Budget' => $fromCampaign($campaign->budget),
            'Expected Revenue' => $fromCampaign($campaign->expected_revenue),
        ]);
    }

    /**
     * How a report dashlet is shown: 'none' (no report chosen yet), 'native'
     * (a table), or 'legacy' (in a frame: grouped reports, with one pager per
     * group, and reports with charts).
     */
    protected function getReportMode(\AORReportsDashlet $dashlet): string
    {
        $report = $dashlet->report;
        if (!$report instanceof \AOR_Report || empty($report->id)) {
            return 'none';
        }
        if (!empty($dashlet->onlyCharts)) {
            return 'legacy';
        }

        $db = \DBManagerFactory::getInstance();
        $reportId = $db->quoted($report->id);
        $grouped = (int)$db->getOne("SELECT COUNT(*) FROM aor_fields WHERE aor_report_id = $reportId AND group_display = 1 AND deleted = 0");
        $charts = (int)$db->getOne("SELECT COUNT(*) FROM aor_charts WHERE aor_report_id = $reportId AND deleted = 0");

        return $grouped || $charts ? 'legacy' : 'native';
    }

    /**
     * One page of the report of a dashlet, as the legacy dashlet shows it (report
     * query with the parameters of the dashlet, page totals): columns, rows,
     * totals and pages (ReportDataTrait).
     */
    protected function loadReport(\AORReportsDashlet $dashlet, int $offset): array
    {
        $report = $dashlet->report;
        if (!$report->ACLAccess('view')) {
            throw new \RuntimeException('No access to the report ' . $report->id);
        }

        // The user parameters of the dashlet are set on the report by the dashlet. What legacy
        // code prints would end up in the response.
        ob_start();
        try {
            $table = $this->withLegacyClassNames(fn() => $this->readReportTable($report, $offset, ''));
        } finally {
            ob_end_clean();
        }

        return array_merge(['id' => $report->id, 'name' => html_entity_decode((string)$report->name, ENT_QUOTES)], $table);
    }

    /**
     * One page of an Activity Stream dashlet: the posts its legacy query finds
     * (categories and "my items" of the dashlet, record access), each with its
     * replies, and whether the user can post (UserFeed enabled).
     */
    protected function loadFeed(\SugarFeedDashlet $dashlet, int $offset): array
    {
        // The legacy dashlet reads its page from the request, as the list dashlets.
        $request = $_REQUEST;
        $_REQUEST['module'] = self::TYPE;
        $_REQUEST[self::TYPE . '2_' . strtoupper($dashlet->seedBean->object_name) . '_offset'] = $offset;
        try {
            $this->withLegacyClassNames(fn() => $dashlet->process());
        } finally {
            $_REQUEST = $request;
        }

        $items = [];
        foreach (array_slice($dashlet->lvs->data['data'] ?? [], 0, max(1, (int)$dashlet->displayRows)) as $row) {
            $feed = empty($row['ID']) ? null : \BeanFactory::getBean('SugarFeed', $row['ID']);
            if (!$feed || empty($feed->id)) {
                continue;
            }

            $item = $this->describeFeed($feed);
            $replies = $feed->get_list('date_entered', "related_module = 'SugarFeed' AND related_id = " . $feed->db->quoted($feed->id));
            $item['replies'] = array_map(fn($reply) => $this->describeFeed($reply), $replies['list'] ?? []);
            $items[] = $item;
        }

        $offsets = $dashlet->lvs->data['pageData']['offsets'] ?? [];

        return [
            'items' => $items,
            'canPost' => (bool)$dashlet->check_enabled('UserFeed'),
            'offsets' => [
                'current' => (int)($offsets['current'] ?? $offset),
                'next' => (int)($offsets['next'] ?? -1),
                'prev' => (int)($offsets['prev'] ?? -1),
                'total' => (int)($offsets['total'] ?? count($items)),
            ],
        ];
    }

    /**
     * A post or reply, as the legacy feed shows it: the message (name and
     * description) with its user, labels and record tokens resolved (records
     * the user cannot see are not links), its author and age, and whether the
     * user can delete it (author or admin).
     */
    protected function describeFeed(\SugarFeed $feed): array
    {
        global $current_user;

        $author = (string)get_assigned_user_name($feed->created_by);
        $message = str_replace('{this.CREATED_BY}', $author, html_entity_decode((string)$feed->name . (string)$feed->description, ENT_QUOTES));
        $message = preg_replace_callback(
            '/\{([^\^ }]+)\.([^\}]+)\}/',
            static fn($matches) => $matches[1] === 'this' ? '' : (string)translate($matches[2], $matches[1]),
            $message
        );
        $message = preg_replace('/\[(\w+):([\w\-\d]*):([^\]]*)\]\[HIDELINK\]/', '$3', $message);
        $message = preg_replace_callback(
            '/\[(\w+):([\w\-\d]*):([^\]]*)\]/',
            fn($matches) => '<a href="#/' . $this->moduleNameMapper->toFrontEnd($matches[1]) . '/record/' . rawurlencode($matches[2]) . '">' . $matches[3] . '</a>',
            $message
        );

        return [
            'id' => $feed->id,
            'author' => $author,
            // Sanitised by the page (bold, italic and record links are kept).
            'html' => $message,
            'age' => trim(strip_tags((string)\SugarFeed::getTimeLapse($feed->date_entered))),
            'date' => (string)$feed->date_entered,
            'canDelete' => is_admin($current_user) || $feed->created_by === $current_user->id,
        ];
    }

    /**
     * Posts, replies to or deletes a post of an Activity Stream dashlet, with the
     * legacy dashlet's own actions (escaping of the text, author or admin to
     * delete). Posts are text only: this SuiteCRM has no link handlers
     * (modules/SugarFeed/linkHandlers), as the legacy post form.
     *
     * @param array{text?: string, parentId?: string, recordId?: string} $values
     */
    public function feedAction(string $id, string $action, array $values): bool
    {
        return $this->inLegacy(function () use ($id, $action, $values) {
            $definition = $this->getDashletDefinitions()[$id] ?? null;
            $dashlet = $definition ? $this->createDashlet($id, $definition) : null;
            if (!$dashlet instanceof \SugarFeedDashlet) {
                return false;
            }

            $text = trim((string)($values['text'] ?? ''));
            $request = $_REQUEST;
            try {
                switch ($action) {
                    case 'post':
                        if (!$dashlet->check_enabled('UserFeed') || $text === '') {
                            return false;
                        }
                        $_REQUEST = ['text' => $text];
                        $dashlet->pushUserFeed();
                        break;
                    case 'reply':
                        $parent = \BeanFactory::getBean('SugarFeed', (string)($values['parentId'] ?? ''));
                        if ($text === '' || !$parent || empty($parent->id)) {
                            return false;
                        }
                        $_REQUEST = ['text' => $text, 'parentFeed' => $parent->id];
                        $dashlet->pushUserFeedReply();
                        break;
                    case 'delete':
                        $_REQUEST = ['record' => (string)($values['recordId'] ?? '')];
                        $dashlet->deleteUserFeed();
                        break;
                    default:
                        return false;
                }
            } finally {
                $_REQUEST = $request;
            }

            return true;
        });
    }

    /**
     * Page of an iFrame (or SuiteCRM News) dashlet, as the legacy dashlet builds
     * it: an http(s) URL only, with its height.
     */
    protected function describeFrame(\iFrameDashlet $dashlet): array
    {
        global $current_language, $sugar_config;

        $url = str_replace(
            ['@@LANG@@', '@@VER@@', '@@EDITION@@'],
            [$current_language, $sugar_config['sugar_version'] ?? '', 'COM'],
            (string)$dashlet->url
        );
        $valid = $url !== '' && (new \ReflectionMethod($dashlet, 'checkURL'))->invoke($dashlet);

        return ['url' => $valid ? $url : '', 'height' => max(50, (int)($dashlet->height ?? 315))];
    }

    /**
     * "Mar 2026" for the month 2026-03 (short month names of the user's language).
     */
    protected function monthLabel(string $month): string
    {
        global $app_list_strings;

        if (!preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
            return $month;
        }

        $name = $app_list_strings['dom_cal_month_short'][(string)(int)$matches[2]] ?? '';

        return $name !== '' ? $name . ' ' . $matches[1] : $month;
    }

    /**
     * A currency, or the system currency (-99) when none or no longer defined.
     */
    protected function getCurrency(?string $id): \Currency
    {
        $currency = \BeanFactory::newBean('Currencies');
        $currency->retrieve($id ?: '-99');
        if (empty($currency->conversion_rate)) {
            $currency->retrieve('-99');
        }

        return $currency;
    }

    /**
     * The legacy dashlet's chart query (protected constructQuery()).
     */
    protected function chartQuery(\DashletGenericChart $dashlet): string
    {
        return (new \ReflectionMethod($dashlet, 'constructQuery'))->invoke($dashlet);
    }

    /**
     * The dashlet's columns (the user's choice, or the dashlet's default
     * ones), as list view columns: field definition, type, label, link.
     */
    protected function buildColumns(\DashletGeneric $dashlet, string $legacyModule): array
    {
        $vardefs = $this->fieldDefinitions->getVardef($this->moduleNameMapper->toFrontEnd($legacyModule))->getVardef() ?? [];

        $columns = [];
        foreach ($dashlet->lvs->displayColumns ?? [] as $key => $definition) {
            $name = strtolower((string)$key);
            $label = translate((string)($definition['label'] ?? ''), $legacyModule);
            $column = array_merge($this->columnInterface, [
                'name' => $name,
                'label' => (string)($definition['label'] ?? ''),
                'labelText' => trim(html_entity_decode(is_string($label) ? $label : '', ENT_QUOTES), ': '),
                'link' => !empty($definition['link']),
                'sortable' => ($definition['sortable'] ?? true) !== false,
                'orderBy' => strtolower((string)($definition['orderBy'] ?? $name)),
            ]);

            if ($name === self::CLOSE_COLUMN || $name === self::ACCEPT_COLUMN) {
                if (isset(self::INVITATION_MODULES[$legacyModule])) {
                    $column['type'] = $name === self::CLOSE_COLUMN ? 'dashlet-close' : 'dashlet-accept';
                    $column['sortable'] = false;
                    $columns[] = $column;
                }
                continue;
            }

            if (!isset($vardefs[$name])) {
                continue;
            }

            $columns[] = $this->addFieldDefinition($vardefs, $name, $column, $this->columnInterface, $this->fieldAliasMapper);
        }

        return $columns;
    }

    /**
     * Records in the SuiteCRM 8 list format (the list view query), in the
     * order of the dashlet.
     *
     * @param string[] $ids
     */
    protected function loadRecords(string $legacyModule, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $listData = $this->recordIds->fetch(
            $legacyModule,
            ['preset' => ['type' => RecordIdsDataHandler::PRESET_TYPE, 'params' => ['ids' => $ids]]],
            0,
            count($ids)
        );

        $byId = [];
        foreach ($listData->getRecords() ?? [] as $record) {
            $byId[$record->getId()] = $record->toArray();
        }

        return array_values(array_filter(array_map(static fn(string $id) => $byId[$id] ?? null, $ids)));
    }

    protected function saveSort(string $id, array $sort): void
    {
        global $current_user;

        $definitions = $this->getDashletDefinitions();
        if (!isset($definitions[$id])) {
            return;
        }

        $definitions[$id]['sort_options'] = [
            'orderBy' => (string)$sort['orderBy'],
            'sortOrder' => strtoupper((string)($sort['sortOrder'] ?? '')) === 'DESC' ? 'DESC' : 'ASC',
        ];
        $current_user->setPreference('dashlets', $definitions, 0, self::TYPE);
        $this->savePreferences();
    }

    protected function isInvited(string $module, string $id, string $userId): bool
    {
        $db = \DBManagerFactory::getInstance();
        $table = self::INVITATION_MODULES[$module];
        $idField = $module === 'Calls' ? 'call_id' : 'meeting_id';

        $count = $db->getOne(
            "SELECT COUNT(*) FROM $table WHERE deleted = 0 AND $idField = " . $db->quoted($id)
            . ' AND user_id = ' . $db->quoted($userId)
        );

        return (int)$count > 0;
    }

    /**
     * The default dashboard of a user who has none yet, as the legacy
     * dashboard creates it on the first visit (modules/Home/index.php):
     * SugarFeed in the right column, the default dashlets in the left one.
     *
     * @return array{0: array, 1: array} pages, dashlets
     */
    protected function createDefaultDashboard(): array
    {
        global $current_user;

        $dashletsFiles = $this->getDashletsFiles();
        $defaultDashlets = [];
        require 'modules/Home/dashlets.php';

        $definitions = [];
        if (isset($dashletsFiles['SugarFeedDashlet'])) {
            $definitions[create_guid()] = [
                'className' => 'SugarFeedDashlet',
                'module' => 'SugarFeed',
                'forceColumn' => 1,
                'fileLocation' => $dashletsFiles['SugarFeedDashlet']['file'],
            ];
        }

        require_once 'include/MySugar/MySugar.php';
        foreach ($defaultDashlets as $className => $module) {
            if (isset($dashletsFiles[$className]) && (new \MySugar($module))->checkDashletDisplay()) {
                $definitions[create_guid()] = [
                    'className' => $className,
                    'module' => $module,
                    'forceColumn' => 0,
                    'fileLocation' => $dashletsFiles[$className]['file'],
                    'options' => [],
                ];
            }
        }

        $columns = [
            ['width' => '60%', 'dashlets' => []],
            ['width' => '40%', 'dashlets' => []],
        ];
        foreach ($definitions as $id => $definition) {
            $columns[$definition['forceColumn'] == 0 ? 0 : 1]['dashlets'][] = $id;
        }

        $pages = [['columns' => $columns, 'numColumns' => '3', 'pageTitleLabel' => 'LBL_HOME_PAGE_1_NAME']];

        $current_user->setPreference('dashlets', $definitions, 0, self::TYPE);
        $current_user->setPreference('pages', $pages, 0, self::TYPE);
        $this->savePreferences();

        return [$pages, $definitions];
    }
}
