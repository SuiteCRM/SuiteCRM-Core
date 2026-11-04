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

namespace App\Module\Studio\Service\ProcessHandlers;

use App\Module\Studio\LegacyHandler\Studio;
use App\Module\Studio\LegacyHandler\StudioGridLayouts;
use App\Module\Studio\LegacyHandler\StudioListLayouts;
use App\Module\Studio\LegacyHandler\StudioRelationships;
use App\Module\Studio\LegacyHandler\StudioTools;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `Studio` process of the SuiteCRM 8 Studio page (Studio). Admins only.
 *
 * Options, by action:
 * - modules: the modules of Studio and the labels of the page;
 * - module: module → its components;
 * - labels: module, language, all (bool) → its labels in the language;
 * - saveLabels: module, language, labels (key => text, the changed ones) → saved;
 * - fields: module → its fields;
 * - listLayout: module, view (legacy list layout view), subpanel, sid (a layout of its history)
 *   → its columns of fields, subpanel title and history;
 * - saveListLayout: module, view, subpanel, sid (the layout of the history restored), groups
 *   (the field names of each column), widths (name => width), labels (key => text, changed),
 *   subpanelTitle → saved, then the layout as saved;
 * - gridLayout: module, view (legacy grid layout view), sid (a layout of its history),
 *   copyFromEditView → its panels, rows and fields, the fields to add, its history;
 * - saveGridLayout: module, view, sid (the layout of the history restored), deploy (Save &
 *   Deploy, else Save), panels (id, label, newTab, panelDefault, rows of slots: name, labelKey,
 *   tabindex), sync (Sync to Detail View), labels (key => text, changed) → saved, then the
 *   layout as saved;
 * - resetActions: module → the customizations Reset Module removes (the page posts the legacy
 *   action);
 * - exportModules: the customized modules of Export Customizations;
 * - exportCustomizations: name, author, description, modules → the package (file name, content
 *   in base64);
 * - relationships: module → its relationships;
 * - relationship: module, values (relationship_name, relationship_type, rhs_module, lhs_label,
 *   rhs_label, relationship_lang, ajaxLoad) → the editor of the relationship (a new one without
 *   name);
 * - saveRelationshipLabels: module, name, language, lhsLabel, rhsLabel → saved, then the
 *   relationships (Save & Deploy and Delete: the page posts the legacy form to its action).
 *
 * With package, the actions of a module (labels, fields, layouts, relationships) are for
 * a module of that Module Builder package.
 */
class StudioProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = Studio::HANDLER_KEY;

    protected const ACTIONS = [
        'modules', 'module', 'labels', 'saveLabels', 'fields', 'listLayout', 'saveListLayout', 'gridLayout', 'saveGridLayout',
        'resetActions', 'exportModules', 'exportCustomizations', 'relationships', 'relationship', 'saveRelationshipLabels',
    ];

    /**
     * The actions without a module.
     */
    protected const GLOBAL_ACTIONS = ['modules', 'exportModules', 'exportCustomizations'];

    public function __construct(
        protected Studio $studio,
        protected StudioListLayouts $listLayouts,
        protected StudioGridLayouts $gridLayouts,
        protected StudioTools $tools,
        protected StudioRelationships $relationships,
        protected LoggerInterface $logger
    ) {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_ADMIN';
    }

    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
        if (!in_array($options['action'], self::GLOBAL_ACTIONS, true) && !preg_match('/^\w+$/', (string)($options['module'] ?? ''))) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
        // A module of a Module Builder package (not for the Studio tools).
        if (!preg_match('/^\w*$/', (string)($options['package'] ?? ''))
            || ((string)($options['package'] ?? '') !== '' && in_array($options['action'], ['modules', 'resetActions', 'exportModules', 'exportCustomizations'], true))) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
        if (in_array($options['action'], ['listLayout', 'saveListLayout', 'gridLayout', 'saveGridLayout'], true) && (
            !preg_match('/^\w+$/', (string)($options['view'] ?? ''))
            || !preg_match('/^\w*$/', (string)($options['subpanel'] ?? ''))
            || !preg_match('/^\d*$/', (string)($options['sid'] ?? ''))
        )) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = (string)($options['module'] ?? '');
        $language = (string)($options['language'] ?? '');
        // The services for a module of this Module Builder package ('' for Studio).
        $package = (string)($options['package'] ?? '');
        foreach ([$this->studio, $this->listLayouts, $this->gridLayouts, $this->relationships] as $service) {
            $service->forPackage($package);
        }

        try {
            $data = match ($options['action']) {
                'modules' => $this->studio->getModules(),
                'module' => $this->studio->getModule($module),
                'labels' => $this->studio->getLabels($module, $language, !empty($options['all'])),
                'saveLabels' => $this->saveLabels($module, $language, is_array($options['labels'] ?? null) ? $options['labels'] : [], !empty($options['all'])),
                'fields' => $this->studio->getFields($module),
                'listLayout' => $this->listLayouts->getLayout($module, (string)$options['view'], (string)($options['subpanel'] ?? ''), (string)($options['sid'] ?? '')),
                'saveListLayout' => $this->saveListLayout($module, $options),
                'gridLayout' => $this->gridLayouts->getLayout($module, (string)$options['view'], (string)($options['sid'] ?? ''), !empty($options['copyFromEditView'])),
                'saveGridLayout' => $this->saveGridLayout($module, $options),
                'resetActions' => $this->tools->getResetActions($module),
                'exportModules' => $this->tools->getExportModules(),
                'exportCustomizations' => $this->exportCustomizations($options),
                'relationships' => $this->relationships->getRelationships($module),
                'relationship' => $this->relationships->getRelationship($module, $this->values($options['values'] ?? null)),
                'saveRelationshipLabels' => $this->saveRelationshipLabels($module, $options),
            };
            $success = true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Studio ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = [];
            $success = false;
        }

        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($data);
    }

    /**
     * Saves a list layout, then the layout as saved (as the legacy editor shown again).
     *
     * @param array<string, mixed> $options
     */
    protected function saveListLayout(string $module, array $options): array
    {
        $view = (string)$options['view'];
        $subpanel = (string)($options['subpanel'] ?? '');
        $groups = [];
        foreach (is_array($options['groups'] ?? null) ? $options['groups'] : [] as $names) {
            $groups[] = array_values(array_filter(is_array($names) ? $names : [], static fn ($name): bool => is_string($name) && preg_match('/^\w+$/', $name) === 1));
        }
        $widths = [];
        foreach (is_array($options['widths'] ?? null) ? $options['widths'] : [] as $name => $width) {
            if (preg_match('/^\w+$/', (string)$name) && preg_match('/^\d{0,3}$/', (string)$width)) {
                $widths[(string)$name] = (string)$width;
            }
        }
        $labels = [];
        foreach (is_array($options['labels'] ?? null) ? $options['labels'] : [] as $key => $text) {
            if (preg_match('/^\w+$/', (string)$key) && is_string($text)) {
                $labels[(string)$key] = $text;
            }
        }
        $title = isset($options['subpanelTitle']) && is_string($options['subpanelTitle']) ? $options['subpanelTitle'] : null;

        $this->listLayouts->saveLayout($module, $view, $subpanel, (string)($options['sid'] ?? ''), $groups, $widths, $labels, $title);

        return $this->listLayouts->getLayout($module, $view, $subpanel);
    }

    /**
     * Saves a grid layout, then the layout as saved (as the legacy editor shown again).
     *
     * @param array<string, mixed> $options
     */
    protected function saveGridLayout(string $module, array $options): array
    {
        $view = (string)$options['view'];
        $panels = [];
        foreach (is_array($options['panels'] ?? null) ? $options['panels'] : [] as $panel) {
            if (!is_array($panel) || !preg_match('/^\w+$/', (string)($panel['id'] ?? ''))) {
                throw new \InvalidArgumentException('Process options are not defined');
            }
            $rows = [];
            foreach (is_array($panel['rows'] ?? null) ? $panel['rows'] : [] as $row) {
                $slots = [];
                foreach (is_array($row) ? $row : [] as $slot) {
                    $name = (string)(is_array($slot) ? ($slot['name'] ?? '') : '');
                    $labelKey = (string)(is_array($slot) ? ($slot['labelKey'] ?? '') : '');
                    $tabindex = (string)(is_array($slot) ? ($slot['tabindex'] ?? '') : '');
                    // The label as in the layout (some with a space, as the legacy editor sends them).
                    if (!preg_match('/^(\w+|\(filler\)|\(empty\))$/', $name) || !preg_match('/^[^\x00-\x1f<>]{0,100}$/u', $labelKey) || !preg_match('/^\d{0,4}$/', $tabindex)) {
                        throw new \InvalidArgumentException('Process options are not defined');
                    }
                    $slots[] = ['name' => $name, 'labelKey' => $labelKey, 'tabindex' => $tabindex];
                }
                $rows[] = $slots;
            }
            $panels[] = [
                'id' => (string)$panel['id'],
                'label' => is_string($panel['label'] ?? null) ? $panel['label'] : '',
                'newTab' => !empty($panel['newTab']),
                'panelDefault' => ($panel['panelDefault'] ?? '') === 'collapsed' ? 'collapsed' : 'expanded',
                'rows' => $rows,
            ];
        }
        $labels = [];
        foreach (is_array($options['labels'] ?? null) ? $options['labels'] : [] as $key => $text) {
            if (preg_match('/^\w+$/', (string)$key) && is_string($text)) {
                $labels[(string)$key] = $text;
            }
        }
        $sync = isset($options['sync']) && is_bool($options['sync']) ? $options['sync'] : null;

        $this->gridLayouts->saveLayout($module, $view, (string)($options['sid'] ?? ''), !empty($options['deploy']), $panels, $sync, $labels);

        return $this->gridLayouts->getLayout($module, $view);
    }

    /**
     * The package of the customizations of modules: a package name of the rule of the page (a
     * folder of the package), its author and description.
     *
     * @param array<string, mixed> $options
     */
    protected function exportCustomizations(array $options): array
    {
        $name = trim((string)($options['name'] ?? ''));
        $modules = $this->names($options['modules'] ?? null);
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_ -]{0,49}$/', $name) || empty($modules)
            || !is_string($options['author'] ?? '') || !is_string($options['description'] ?? '')) {
            throw new \InvalidArgumentException('Process options are not defined');
        }

        return $this->tools->exportCustomizations($name, (string)($options['author'] ?? ''), (string)($options['description'] ?? ''), $modules);
    }

    /**
     * Saves the labels of a relationship, then the relationships.
     *
     * @param array<string, mixed> $options
     */
    protected function saveRelationshipLabels(string $module, array $options): array
    {
        $name = (string)($options['name'] ?? '');
        if (!preg_match('/^\w+$/', $name) || !preg_match('/^\w+$/', (string)($options['language'] ?? ''))
            || !is_string($options['lhsLabel'] ?? '') || !is_string($options['rhsLabel'] ?? '')) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
        $this->relationships->saveRelationshipLabels($module, $name, (string)$options['language'], (string)($options['lhsLabel'] ?? ''), (string)($options['rhsLabel'] ?? ''));

        return $this->relationships->getRelationships($module);
    }

    /**
     * The values of an editor: texts by key, names (words) except the labels.
     *
     * @return array<string, string>
     */
    protected function values(mixed $values): array
    {
        $texts = [];
        foreach (is_array($values) ? $values : [] as $key => $value) {
            if (!preg_match('/^\w+$/', (string)$key) || !is_scalar($value)) {
                continue;
            }
            $value = (string)$value;
            if (!in_array($key, ['lhs_label', 'rhs_label'], true) && !preg_match('/^[\w-]*$/', $value)) {
                throw new \InvalidArgumentException('Process options are not defined');
            }
            $texts[(string)$key] = $value;
        }

        return $texts;
    }

    /**
     * The names (words) of a list option.
     *
     * @return string[]
     */
    protected function names(mixed $names): array
    {
        return array_values(array_filter(is_array($names) ? $names : [], static fn ($name): bool => is_string($name) && preg_match('/^\w+$/', $name) === 1));
    }

    /**
     * Saves the labels, then the labels as saved (as the legacy editor shown again).
     *
     * @param array<string, mixed> $labels
     */
    protected function saveLabels(string $module, string $language, array $labels, bool $all): array
    {
        $this->studio->saveLabels($module, $language, $labels);

        return $this->studio->getLabels($module, $language, $all);
    }
}
