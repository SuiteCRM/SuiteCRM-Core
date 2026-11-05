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

namespace App\Module\Studio\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The relationships of a module in Studio (legacy view.relationships and
 * view.relationship):
 *  - the relationships of the module, as the legacy list (those made in Studio marked);
 *  - a relationship in the legacy editor, or a new one: the editor of the legacy view run on
 *    its request (its type, related module and labels as changed in the page), and what it sets
 *    up for its template: the definition, the types and modules to choose, the subpanels;
 *  - its labels saved as the legacy editor saves them (its controller action on the request of
 *    its form). Save & Deploy and Delete: the page posts the legacy form to its action, as they
 *    repair the application.
 */
class StudioRelationships extends LegacyHandler
{
    use StudioLegacyTrait;

    public const HANDLER_KEY = 'studio-relationships';

    /**
     * The values of the editor the page sends back to it, as the legacy editor reloads itself
     * (ModuleBuilder.moduleLoadRelationship2()).
     */
    protected const EDITOR_KEYS = ['relationship_name', 'relationship_type', 'rhs_module', 'lhs_label', 'rhs_label'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The relationships of a module, as the legacy list (ViewRelationships::getAjaxRelationships()).
     */
    public function getRelationships(string $module): array
    {
        return $this->inLegacy(function () use ($module): array {
            require_once 'modules/ModuleBuilder/views/view.relationships.php';
            if ($this->package !== '') {
                // A module of a package: its relationships not deployed yet (legacy Module Builder).
                require_once 'modules/ModuleBuilder/parsers/relationships/UndeployedRelationships.php';
                $parser = new \UndeployedRelationships($this->getMbModule($module)->getModuleDir());
            } else {
                $this->getStudioModule($module);
                require_once 'modules/ModuleBuilder/parsers/relationships/DeployedRelationships.php';
                $parser = new \DeployedRelationships($module);
            }

            return $this->withRequest(['view_module' => $module] + $this->packageRequest(), function () use ($module, $parser): array {
                $view = (new \ReflectionClass(\ViewRelationships::class))->newInstanceWithoutConstructor();
                $relationships = [];
                foreach ($view->getAjaxRelationships($parser) as $relationship) {
                    $relationships[] = [
                        'name' => (string)$relationship['name'],
                        'lhsModule' => $this->decode((string)$relationship['lhs_module']),
                        'type' => $this->decode((string)$relationship['relationship_type']),
                        'rhsModule' => $this->decode((string)$relationship['rhs_module']),
                    ];
                }

                return ['relationships' => $relationships, 'canAdd' => $module !== 'KBDocuments'];
            });
        });
    }

    /**
     * The editor of a relationship (a new one without name): what the legacy editor sets up for
     * its template (ViewRelationship::display()), from the values of the page.
     *
     * @param array<string, string> $values EDITOR_KEYS, with relationship_lang (and ajaxLoad,
     *                                      the language changed: the labels of the relationship)
     */
    public function getRelationship(string $module, array $values): array
    {
        return $this->inLegacy(function () use ($module, $values): array {
            $this->checkModule($module);
            require_once 'modules/ModuleBuilder/controller.php';
            require_once 'modules/ModuleBuilder/views/view.relationship.php';

            $params = ['module' => 'ModuleBuilder', 'action' => 'relationship', 'view_module' => $module, 'view_package' => $this->package, 'json' => 'false'];
            foreach (array_merge(self::EDITOR_KEYS, ['relationship_lang', 'ajaxLoad']) as $key) {
                if (isset($values[$key]) && $values[$key] !== '') {
                    $params[$key] = in_array($key, ['lhs_label', 'rhs_label'], true) ? securexss($values[$key]) : $values[$key];
                }
            }
            // The language of the user by default, as the legacy editor (from its session).
            $params['relationship_lang'] ??= (string)($_SESSION['authenticated_user_language'] ?? '') ?: (string)$GLOBALS['current_language'];

            return $this->withRequest($params, function (): array {
                $view = (new \ReflectionClass(\ViewRelationship::class))->newInstanceWithoutConstructor();
                $view->display();
                $vars = $view->smarty->getTemplateVars();
                $rel = is_array($vars['rel'] ?? null) ? $vars['rel'] : [];

                $options = static fn (array $values, array $labels): array => array_map(
                    static fn ($value): array => ['value' => (string)$value, 'label' => (string)($labels[$value] ?? $value)],
                    array_values($values)
                );
                $languages = [];
                foreach (is_array($vars['available_languages'] ?? null) ? $vars['available_languages'] : [] as $value => $label) {
                    $languages[] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
                }

                return [
                    'relationship' => [
                        'name' => (string)($rel['relationship_name'] ?? ''),
                        'lhsModule' => (string)($rel['lhs_module'] ?? ''),
                        'rhsModule' => (string)($rel['rhs_module'] ?? ''),
                        'type' => (string)($rel['relationship_type'] ?? ''),
                        'lhsLabel' => $this->decode((string)($rel['lhs_label'] ?? '')),
                        'rhsLabel' => $this->decode((string)($rel['rhs_label'] ?? '')),
                        'lhsSubpanel' => (string)($rel['lhs_subpanel'] ?? ''),
                        'rhsSubpanel' => (string)($rel['rhs_subpanel'] ?? ''),
                        'readonly' => !empty($rel['readonly']),
                        'fromStudio' => !empty($rel['from_studio']),
                        'relationshipOnly' => !empty($rel['relationship_only']),
                    ],
                    'isNew' => !empty($vars['is_new']),
                    'lhsModuleLabel' => $this->decode((string)translate((string)($vars['module_key'] ?? ''))),
                    'rhsModuleLabel' => $this->decode((string)translate((string)($rel['rhs_module'] ?? ''))),
                    'types' => array_map(fn (array $option): array => ['value' => $option['value'], 'label' => $this->decode($option['label'])],
                        $options(is_array($vars['cardinality'] ?? null) ? $vars['cardinality'] : [], is_array($vars['translated_cardinality'] ?? null) ? $vars['translated_cardinality'] : [])),
                    'modules' => array_map(fn (array $option): array => ['value' => $option['value'], 'label' => $this->decode($option['label'])],
                        $options(is_array($vars['relatable'] ?? null) ? $vars['relatable'] : [], is_array($vars['translated_relatable'] ?? null) ? $vars['translated_relatable'] : [])),
                    'lhsSubpanels' => array_values(array_map('strval', is_array($vars['lhspanels'] ?? null) ? $vars['lhspanels'] : [])),
                    'rhsSubpanels' => array_values(array_map('strval', is_array($vars['rhspanels'] ?? null) ? $vars['rhspanels'] : [])),
                    'language' => (string)($vars['selected_lang'] ?? ''),
                    'languages' => $languages,
                ];
            });
        });
    }

    /**
     * Saves the labels of a relationship in a language as the legacy editor (Save:
     * ModuleBuilderController::action_SaveRelationshipLabel()).
     */
    public function saveRelationshipLabels(string $module, string $name, string $language, string $lhsLabel, string $rhsLabel): void
    {
        $this->inLegacy(function () use ($module, $name, $language, $lhsLabel, $rhsLabel): void {
            $this->checkModule($module);
            if (!array_key_exists($language, get_languages())) {
                throw new \InvalidArgumentException('Unknown language ' . $language);
            }

            $this->withRequest([
                'module' => 'ModuleBuilder',
                'action' => 'SaveRelationshipLabel',
                'view_module' => $module,
                'relationship_name' => $name,
                'relationship_lang' => $language,
                'lhs_label' => securexss($lhsLabel),
                'rhs_label' => securexss($rhsLabel),
            ] + $this->packageRequest(), function (): void {
                $this->getController()->action_SaveRelationshipLabel();
            });
            $this->markCaches();
        });
    }
}
