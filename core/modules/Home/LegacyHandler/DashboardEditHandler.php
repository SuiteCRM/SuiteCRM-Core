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

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Editing of the user's Home dashboard from the SuiteCRM 8 dashboard page: the
 * dashlets (add, remove, move, options) and the tabs (add, rename, remove,
 * number of columns), in the same user preferences as the legacy dashboard,
 * so both keep showing the same dashboard.
 *
 * The legacy rules are kept: the dashlets offered are the ones of the legacy
 * "Add Dashlets" dialog (module access...), a new dashlet goes at the top of
 * the first column, a tab holds at most `max_dashlets_homepage` dashlets, the
 * first tab cannot be renamed or removed, nothing can be changed when the
 * administrator locked the homepage (`lock_homepage`). The options are saved
 * by the dashlet itself (saveOptions()), from the fields of its legacy form.
 */
class DashboardEditHandler extends LegacyHandler
{
    use DashboardPreferencesTrait;
    use ReportDashletOptionsTrait;

    public const HANDLER_KEY = 'dashboard-edit';

    protected const TYPE = 'Home';

    // Categories of the legacy "Add Dashlets" dialog, in its order.
    protected const CATEGORIES = [
        'Module Views' => 'LBL_MODULES',
        'Charts' => 'LBL_CHARTS',
        'Tools' => 'LBL_TOOLS',
        'Web' => 'LBL_WEB',
    ];

    protected const MAX_COLUMNS = 3;

    // Column widths of a new tab, as the legacy AddDashboardPages.
    protected const COLUMN_WIDTHS = [1 => ['100%'], 2 => ['60%', '40%'], 3 => ['30%', '30%', '30%']];

    protected const TITLE_MAX_LENGTH = 100;

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack
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
     * Dashlets the user can add, by category, and the number of dashlets a
     * tab can hold.
     */
    public function getCatalog(): array
    {
        return $this->inLegacy(fn() => $this->buildDashletsCache() ? ['retry' => true] : [
            'categories' => $this->buildCatalog(),
            'max' => $this->getMaxDashlets(),
        ]);
    }

    /**
     * Adds a dashlet at the top of the first column of a tab.
     *
     * @return array{id?: string, error?: string}
     */
    public function addDashlet(string $className, int $page): array
    {
        return $this->inLegacy(function () use ($className, $page) {
            if ($this->isLocked()) {
                return ['error' => 'locked'];
            }
            if ($this->buildDashletsCache()) {
                return ['error' => 'retry'];
            }

            // Before the dashlets cache is read below: the dialog includes it once.
            $offered = array_merge(...array_map(
                static fn(array $category) => array_column($category['dashlets'], 'className'),
                $this->buildCatalog()
            ));
            $files = $this->getDashletsFiles();
            if (!in_array($className, $offered, true) || !isset($files[$className])) {
                return ['error' => 'not-found'];
            }

            $pages = $this->getPages();
            $definitions = $this->getDashletDefinitions();
            if (!isset($pages[$page])) {
                return ['error' => 'not-found'];
            }
            if (count($this->getPageDashletIds($pages[$page])) >= $this->getMaxDashlets()) {
                return ['error' => 'max'];
            }

            $id = create_guid();
            $definitions[$id] = [
                'className' => $files[$className]['class'],
                'module' => $files[$className]['module'] ?? 'Home',
                'options' => [],
                'fileLocation' => $files[$className]['file'],
            ];

            if (empty($pages[$page]['columns'])) {
                $pages[$page]['columns'] = [['width' => '100%', 'dashlets' => []]];
            }
            $pages[$page]['columns'][0]['dashlets'] = array_values($pages[$page]['columns'][0]['dashlets'] ?? []);
            array_unshift($pages[$page]['columns'][0]['dashlets'], $id);

            $this->save($pages, $definitions);

            return ['id' => $id];
        });
    }

    /**
     * Removes a dashlet from the dashboard (its tab and its options).
     */
    public function removeDashlet(string $id): bool
    {
        return $this->inLegacy(function () use ($id) {
            $definitions = $this->getDashletDefinitions();
            if ($this->isLocked() || !isset($definitions[$id])) {
                return false;
            }

            $pages = $this->getPages();
            foreach ($pages as &$page) {
                foreach ($page['columns'] ?? [] as $index => $column) {
                    $page['columns'][$index]['dashlets'] = array_values(array_filter(
                        $column['dashlets'] ?? [],
                        static fn($dashletId) => $dashletId !== $id
                    ));
                }
            }
            unset($page);
            unset($definitions[$id]);

            $this->save($pages, $definitions);

            return true;
        });
    }

    /**
     * New order of the dashlets of a tab, column by column (drag and drop).
     * The tab keeps the same dashlets.
     *
     * @param string[][] $columns
     */
    public function saveLayout(int $page, array $columns): bool
    {
        return $this->inLegacy(function () use ($page, $columns) {
            $pages = $this->getPages();
            if ($this->isLocked() || !isset($pages[$page])) {
                return false;
            }

            $shown = $this->getShownColumns($pages[$page]);
            $columns = array_map(
                static fn($ids) => array_values(array_map('strval', is_array($ids) ? $ids : [])),
                array_values($columns)
            );
            if (count($columns) !== count($shown)) {
                return false;
            }

            $current = array_merge([], ...array_map(static fn($column) => $column['dashlets'] ?? [], $shown));
            $new = array_merge([], ...$columns);
            sort($current);
            $sortedNew = $new;
            sort($sortedNew);
            if ($current !== $sortedNew || count(array_unique($new)) !== count($new)) {
                return false;
            }

            foreach ($columns as $index => $ids) {
                $pages[$page]['columns'][$index]['dashlets'] = $ids;
            }
            $this->savePages($pages);

            return true;
        });
    }

    /**
     * Adds a tab after the others.
     *
     * @return array{page?: int, error?: string}
     */
    public function addTab(string $title, int $columns): array
    {
        return $this->inLegacy(function () use ($title, $columns) {
            $title = $this->cleanTitle($title);
            if ($this->isLocked() || $title === '' || !isset(self::COLUMN_WIDTHS[$columns])) {
                return ['error' => 'invalid'];
            }

            $pages = $this->getPages();
            $pages[] = [
                'columns' => array_map(
                    static fn(string $width) => ['width' => $width, 'dashlets' => []],
                    self::COLUMN_WIDTHS[$columns]
                ),
                'numColumns' => (string)$columns,
                'pageTitle' => $title,
            ];
            $this->savePages($pages);

            return ['page' => count($pages) - 1];
        });
    }

    /**
     * Renames a tab (not the first one, whose title is fixed).
     */
    public function renameTab(int $page, string $title): bool
    {
        return $this->inLegacy(function () use ($page, $title) {
            $title = $this->cleanTitle($title);
            $pages = $this->getPages();
            if ($this->isLocked() || $page < 1 || !isset($pages[$page]) || $title === '') {
                return false;
            }

            $pages[$page]['pageTitle'] = $title;
            $this->savePages($pages);

            return true;
        });
    }

    /**
     * Removes a tab (not the first one) and its dashlets. The legacy dashboard
     * kept the options of the dashlets of a removed tab.
     */
    public function removeTab(int $page): bool
    {
        return $this->inLegacy(function () use ($page) {
            $pages = $this->getPages();
            if ($this->isLocked() || $page < 1 || !isset($pages[$page])) {
                return false;
            }

            $definitions = $this->getDashletDefinitions();
            foreach ($this->getPageDashletIds($pages[$page], false) as $id) {
                unset($definitions[$id]);
            }
            unset($pages[$page]);

            $this->save(array_values($pages), $definitions);

            return true;
        });
    }

    /**
     * Number of columns of a tab (1 to 3). The dashlets of the columns taken
     * away go at the end of the last column left.
     */
    public function setTabColumns(int $page, int $columns): bool
    {
        return $this->inLegacy(function () use ($page, $columns) {
            $pages = $this->getPages();
            if ($this->isLocked() || !isset($pages[$page]) || !isset(self::COLUMN_WIDTHS[$columns])) {
                return false;
            }

            $current = $this->getShownColumns($pages[$page]);
            $new = [];
            foreach (self::COLUMN_WIDTHS[$columns] as $index => $width) {
                $new[] = ['width' => $width, 'dashlets' => array_values($current[$index]['dashlets'] ?? [])];
            }
            for ($index = $columns, $count = count($current); $index < $count; $index++) {
                $new[$columns - 1]['dashlets'] = array_merge(
                    $new[$columns - 1]['dashlets'],
                    array_values($current[$index]['dashlets'] ?? [])
                );
            }

            $pages[$page]['columns'] = $new;
            $pages[$page]['numColumns'] = (string)$columns;
            $this->savePages($pages);

            return true;
        });
    }

    /**
     * The options form of a dashlet:
     * - list: title, rows, auto refresh, "my items", columns and filters;
     * - chart: title, auto refresh and the fields of the chart (dates, users,
     *   lead sources...);
     * - report: title, report, "only show charts", charts and parameters of the
     *   report (ReportDashletOptionsTrait);
     * - legacy: the other dashlets (Activity Stream, iFrame...), configured with
     *   their legacy form.
     * The filters and chart fields are the inputs of the legacy form
     * (multiple choice, single choice, text, date), with their legacy names.
     */
    public function getDashletOptions(string $id): array
    {
        return $this->inLegacy(function () use ($id) {
            $definitions = $this->getDashletDefinitions();
            $dashlet = isset($definitions[$id]) ? $this->createDashlet($id, $definitions[$id]) : null;
            if ($dashlet === null) {
                return ['error' => 'not-found'];
            }
            if (empty($dashlet->isConfigurable)) {
                return ['id' => $id, 'type' => 'none'];
            }

            $type = $this->getOptionsType($dashlet);
            $options = [
                'id' => $id,
                'type' => $type,
                'title' => html_entity_decode((string)($dashlet->title ?? ''), ENT_QUOTES),
            ];
            if ($type === 'legacy') {
                return $options;
            }
            if ($type === 'report') {
                return $options + $this->describeReportOptions($dashlet);
            }

            // Fills the labels and inputs of the fields (currentSearchFields).
            $this->withLegacyClassNames(fn() => $dashlet->displayOptions());
            $options['fields'] = $this->describeFields($dashlet, $type);
            $options['autoRefresh'] = $this->describeAutoRefresh($dashlet);

            if ($type === 'list') {
                global $sugar_config;

                $options['rows'] = [
                    'value' => (string)$dashlet->displayRows,
                    'options' => array_map('strval', array_values((array)($sugar_config['dashlet_display_row_options'] ?? ['1', '3', '5', '10']))),
                ];
                $options['myItemsOnly'] = $dashlet->showMyItemsOnly ? !empty($dashlet->myItemsOnly) && $dashlet->myItemsOnly !== 'false' : null;
                $options['columns'] = $this->describeColumns($dashlet);
            }

            return $options;
        });
    }

    /**
     * Charts and parameters of the report chosen in the options of a report dashlet.
     */
    public function getReportOptions(string $reportId): array
    {
        return $this->inLegacy(fn() => $this->describeReport($reportId) ?: ['error' => 'not-found']);
    }

    /**
     * Saves the options of a list, chart or report dashlet, through the dashlet's own
     * saveOptions(), with the values of the legacy form.
     *
     * @param array{title?: string, rows?: string, autoRefresh?: string, myItemsOnly?: bool, columns?: string[], fields?: array} $values
     */
    public function saveDashletOptions(string $id, array $values): bool
    {
        return $this->inLegacy(function () use ($id, $values) {
            $definitions = $this->getDashletDefinitions();
            $dashlet = isset($definitions[$id]) ? $this->createDashlet($id, $definitions[$id]) : null;
            if ($this->isLocked() || $dashlet === null || empty($dashlet->isConfigurable)) {
                return false;
            }

            $type = $this->getOptionsType($dashlet);
            if ($type === 'legacy') {
                return false;
            }

            if ($type === 'report') {
                $request = $this->buildReportOptionsRequest($values);
            } else {
                $this->withLegacyClassNames(fn() => $dashlet->displayOptions());
                $request = $this->buildOptionsRequest($dashlet, $type, $values);
            }
            if ($request === null) {
                return false;
            }

            $definitions[$id]['options'] = $this->withLegacyClassNames(fn() => $dashlet->saveOptions($request));
            $this->saveDashlets($definitions);

            return true;
        });
    }

    protected function getOptionsType(object $dashlet): string
    {
        if ($this->isListDashlet($dashlet)) {
            return 'list';
        }
        if ($dashlet instanceof \AORReportsDashlet) {
            return 'report';
        }
        return $dashlet instanceof \DashletGenericChart ? 'chart' : 'legacy';
    }

    /**
     * The request of the legacy form for saveOptions(), from the values of the
     * page; null when a value is not one the form offers.
     */
    protected function buildOptionsRequest(object $dashlet, string $type, array $values): ?array
    {
        $request = ['dashletTitle' => $this->cleanTitle((string)($values['title'] ?? ''))];

        $autoRefresh = $this->describeAutoRefresh($dashlet);
        if ($autoRefresh !== null) {
            $value = (string)($values['autoRefresh'] ?? $autoRefresh['value']);
            if (!in_array($value, array_column($autoRefresh['options'], 'value'), true)) {
                return null;
            }
            $request['autoRefresh'] = $value;
        }

        if ($type === 'list') {
            global $sugar_config;

            $rows = (string)($values['rows'] ?? $dashlet->displayRows);
            if (!in_array($rows, array_map('strval', (array)($sugar_config['dashlet_display_row_options'] ?? [])), true)) {
                return null;
            }
            $request['displayRows'] = $rows;

            if (!empty($values['myItemsOnly'])) {
                $request['myItemsOnly'] = 'true';
            }

            $columns = array_values(array_unique(array_map('strval', (array)($values['columns'] ?? []))));
            $available = array_column($this->describeColumns($dashlet)['options'], 'value');
            if ($columns === [] || array_diff($columns, $available)) {
                return null;
            }
            $request['displayColumnsDef'] = implode('|', $columns);
        }

        $given = is_array($values['fields'] ?? null) ? $values['fields'] : [];
        foreach ($this->describeFields($dashlet, $type) as $field) {
            // A field the page did not send keeps its value.
            $value = array_key_exists($field['name'], $given) ? $given[$field['name']] : $field['value'];
            switch ($field['kind']) {
                case 'multi':
                    $value = array_values(array_map('strval', is_array($value) ? $value : []));
                    if (array_diff($value, array_column($field['options'], 'value'))) {
                        return null;
                    }
                    if ($value !== []) {
                        $request[$field['name']] = $value;
                    }
                    break;
                case 'select':
                    $value = (string)$value;
                    if (!in_array($value, array_column($field['options'], 'value'), true)) {
                        return null;
                    }
                    $request[$field['name']] = $value;
                    break;
                case 'date':
                    // The page gives yyyy-mm-dd, the legacy form the user's date format.
                    global $timedate;
                    $value = (string)$value;
                    if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        return null;
                    }
                    $request[$field['name']] = $value === '' ? '' : $timedate->swap_formats($value, 'Y-m-d', $timedate->get_date_format());
                    break;
                default:
                    $request[$field['name']] = securexss((string)$value);
            }
        }

        return $request;
    }

    /**
     * The filters (list) or fields (chart) of the legacy form, read from the
     * inputs the dashlet built for it (displayOptions()).
     */
    protected function describeFields(object $dashlet, string $type): array
    {
        global $timedate;

        $searchFields = $type === 'chart'
            ? (array)(new \ReflectionProperty(\DashletGenericChart::class, '_searchFields'))->getValue($dashlet)
            : (array)$dashlet->searchFields;

        $fields = [];
        foreach ((array)($dashlet->currentSearchFields ?? []) as $name => $field) {
            if (empty($field['input']) || !is_string($field['input'])) {
                continue;
            }

            $label = trim(html_entity_decode(strip_tags((string)($field['label'] ?? '')), ENT_QUOTES), ': ');
            $isDate = ($searchFields[$name]['type'] ?? '') === 'datepicker';
            foreach ($this->readInputs($field['input']) as $input) {
                if ($isDate && $input['kind'] === 'text') {
                    $input['kind'] = 'date';
                    $date = $input['value'] !== '' ? $timedate->swap_formats($input['value'], $timedate->get_date_format(), 'Y-m-d') : '';
                    $input['value'] = is_string($date) ? $date : '';
                }
                $fields[] = array_merge(['label' => $label], $input);
            }
        }

        return $fields;
    }

    /**
     * The select and text inputs of a legacy widget: name (without []),
     * kind (multi, select, text), options and current value.
     */
    protected function readInputs(string $html): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $inputs = [];
        foreach ($document->getElementsByTagName('select') as $select) {
            $multiple = $select->hasAttribute('multiple');
            $options = [];
            $selected = [];
            foreach ($select->getElementsByTagName('option') as $option) {
                $value = $option->getAttribute('value');
                $options[] = ['value' => $value, 'label' => trim($option->textContent)];
                if ($option->hasAttribute('selected')) {
                    $selected[] = $value;
                }
            }
            if ($multiple) {
                $options = array_values(array_filter($options, static fn($option) => trim($option['value']) !== ''));
            }
            $inputs[] = [
                'name' => preg_replace('/\[\]$/', '', $select->getAttribute('name')),
                'kind' => $multiple ? 'multi' : 'select',
                'options' => $options,
                'value' => $multiple ? $selected : ($selected[0] ?? ($options[0]['value'] ?? '')),
            ];
        }
        foreach ($document->getElementsByTagName('input') as $input) {
            if (!in_array(strtolower($input->getAttribute('type') ?: 'text'), ['text', 'number'], true)) {
                continue;
            }
            $inputs[] = [
                'name' => $input->getAttribute('name'),
                'kind' => 'text',
                'options' => [],
                'value' => $input->getAttribute('value'),
            ];
        }

        return array_values(array_filter($inputs, static fn($input) => $input['name'] !== ''));
    }

    /**
     * Columns a list dashlet can show, and the ones it shows, in order.
     *
     * @return array{value: string[], options: array<array{value: string, label: string}>}
     */
    protected function describeColumns(\DashletGeneric $dashlet): array
    {
        $module = $dashlet->seedBean->module_dir;
        $label = static function (array $column, string $name) use ($module): string {
            $translated = translate((string)($column['label'] ?? $name), $module);

            return trim(html_entity_decode(is_string($translated) ? $translated : (string)($column['label'] ?? $name), ENT_QUOTES), ': ');
        };

        $shown = $dashlet->displayColumns;
        if (empty($shown)) {
            $shown = array_keys(array_filter($dashlet->columns, static fn($column) => !empty($column['default'])));
        }
        $shown = array_values(array_filter(array_map('strval', $shown), static fn($name) => isset($dashlet->columns[$name])));

        $options = [];
        foreach (array_merge($shown, array_diff(array_keys($dashlet->columns), $shown)) as $name) {
            $options[] = ['value' => (string)$name, 'label' => $label($dashlet->columns[$name], (string)$name)];
        }

        return ['value' => $shown, 'options' => $options];
    }

    /**
     * @return array{value: string, options: array<array{value: string, label: string}>}|null
     */
    protected function describeAutoRefresh(object $dashlet): ?array
    {
        $isRefreshable = new \ReflectionMethod($dashlet, 'isAutoRefreshable');
        if (!$isRefreshable->invoke($dashlet)) {
            return null;
        }

        $options = [];
        foreach ((array)(new \ReflectionMethod($dashlet, 'getAutoRefreshOptions'))->invoke($dashlet) as $value => $label) {
            $options[] = ['value' => (string)$value, 'label' => (string)$label];
        }

        // saveOptions() stores 0 when nothing was chosen: the first choice ("Never") on the legacy form too.
        $value = (string)($dashlet->autoRefresh ?? '');
        if (!in_array($value, array_column($options, 'value'), true)) {
            $value = $options[0]['value'] ?? '-1';
        }

        return ['value' => $value, 'options' => $options];
    }

    /**
     * Builds the dashlets cache when it is missing (after a cache clear).
     * Building it includes the metadata files of the dashlets, which the
     * legacy dialog includes once more (require_once) to read them: the
     * dialog only works in the next request, so the page asks again (retry).
     *
     * @return bool true when the cache had to be built
     */
    protected function buildDashletsCache(): bool
    {
        if (is_file(sugar_cached('dashlets/dashlets.php'))) {
            return false;
        }

        require_once 'include/Dashlets/DashletCacheBuilder.php';
        (new \DashletCacheBuilder())->buildCache();

        return true;
    }

    /**
     * Dashlets of the legacy "Add Dashlets" dialog, by category. The dashlets
     * cache must exist (see buildDashletsCache()).
     */
    protected function buildCatalog(): array
    {
        global $app_strings, $mod_strings;

        require_once 'include/MySugar/DashletsDialog/DashletsDialog.php';
        $dialog = new \DashletsDialog();
        $dialog->getDashlets();

        $homeStrings = return_module_language($GLOBALS['current_language'], 'Home');
        // Text of a cell; a label the dialog left untranslated is a Home one (iFrame: "My Sites").
        $text = static function ($value) use ($homeStrings): string {
            $value = trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES));

            return preg_match('/^LBL_[A-Z0-9_]+$/', $value) && !empty($homeStrings[$value]) ? (string)$homeStrings[$value] : $value;
        };

        $categories = [];
        foreach (self::CATEGORIES as $category => $labelKey) {
            $dashlets = [];
            foreach ($dialog->dashlets[$category] ?? [] as $cell) {
                $dashlets[] = [
                    'className' => preg_replace('/_select$/', '', (string)($cell['id'] ?? '')),
                    'title' => $text($cell['title'] ?? ''),
                    'description' => $text($cell['description'] ?? ''),
                    'module' => (string)($cell['module_name'] ?? ''),
                ];
            }
            usort($dashlets, static fn($a, $b) => strcasecmp($a['title'], $b['title']));

            $categories[] = [
                'key' => strtolower(str_replace(' ', '-', $category)),
                'label' => (string)($homeStrings[$labelKey] ?? $app_strings[$labelKey] ?? $mod_strings[$labelKey] ?? $category),
                'dashlets' => $dashlets,
            ];
        }

        return $categories;
    }

    /**
     * Columns of a tab shown by the dashboard (the legacy dashboard does not
     * show the columns after numColumns).
     */
    protected function getShownColumns(array $page): array
    {
        $columns = array_values($page['columns'] ?? []);
        $numColumns = (int)($page['numColumns'] ?? 0);

        return $numColumns > 0 ? array_slice($columns, 0, $numColumns) : $columns;
    }

    /**
     * @return string[]
     */
    protected function getPageDashletIds(array $page, bool $shownOnly = true): array
    {
        $columns = $shownOnly ? $this->getShownColumns($page) : array_values($page['columns'] ?? []);

        return array_values(array_filter(array_merge([], ...array_map(
            static fn($column) => array_values($column['dashlets'] ?? []),
            $columns
        ))));
    }

    protected function getMaxDashlets(): int
    {
        global $sugar_config;

        return (int)($sugar_config['max_dashlets_homepage'] ?? 15) ?: 15;
    }

    protected function isLocked(): bool
    {
        global $sugar_config;

        return !empty($sugar_config['lock_homepage']);
    }

    /**
     * A tab or dashlet title, stored HTML-encoded as the legacy forms post it.
     */
    protected function cleanTitle(string $title): string
    {
        $title = trim(preg_replace('/\s+/u', ' ', strip_tags($title)));
        $title = mb_substr($title, 0, self::TITLE_MAX_LENGTH);

        return $title === '' ? '' : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    }

    protected function save(?array $pages, ?array $definitions): void
    {
        global $current_user;

        if ($definitions !== null) {
            $current_user->setPreference('dashlets', $definitions, 0, self::TYPE);
        }
        if ($pages !== null) {
            $current_user->setPreference('pages', array_values($pages), 0, self::TYPE);
        }
        $this->savePreferences();
    }

    protected function savePages(array $pages): void
    {
        $this->save($pages, null);
    }

    protected function saveDashlets(array $definitions): void
    {
        $this->save(null, $definitions);
    }
}
