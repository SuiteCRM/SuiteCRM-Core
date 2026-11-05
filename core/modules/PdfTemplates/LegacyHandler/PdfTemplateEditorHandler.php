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

namespace App\Module\PdfTemplates\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Data of the PDF template editor of the record view, as the legacy editor (AOS_PDF_TemplatesViewEdit::setFields):
 *  - the variables of a template type, by group: the fields of the module, of each related
 *    record and, for modules with line items, of the line items and the currency;
 *  - the sample templates (modules/AOS_PDF_Templates/samples).
 * Variable names have no leading `$` (the HTML editor adds it).
 */
class PdfTemplateEditorHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'pdf-template-editor';

    protected const SERVICE_VARIABLES = [
        'aos_services_quotes_name' => ['LBL_SERVICE_NAME', 'AOS_Quotes'],
        'aos_services_quotes_number' => ['LBL_LIST_NUM', 'AOS_Products_Quotes'],
        'aos_services_quotes_service_list_price' => ['LBL_SERVICE_LIST_PRICE', 'AOS_Quotes'],
        'aos_services_quotes_service_discount' => ['LBL_SERVICE_DISCOUNT', 'AOS_Quotes'],
        'aos_services_quotes_service_unit_price' => ['LBL_SERVICE_PRICE', 'AOS_Quotes'],
        'aos_services_quotes_vat_amt' => ['LBL_VAT_AMT', 'AOS_Quotes'],
        'aos_services_quotes_vat' => ['LBL_VAT', 'AOS_Quotes'],
        'aos_services_quotes_service_total_price' => ['LBL_TOTAL_PRICE', 'AOS_Quotes'],
    ];

    protected const CURRENCY_EXCLUDED_LABELS = [
        'LBL_DELETED', 'LBL_CURRENCIES_HASH', 'LBL_LIST_ACCEPT_STATUS', 'LBL_AUTHENTICATE_ID', 'LBL_MODIFIED_BY',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The variables of a template type (module of pdf_template_type_dom), the module group
     * first, then the others by label; null when the user cannot edit PDF templates.
     *
     * @return array<int, array{label: string, variables: array<int, array{name: string, label: string}>}>|null
     */
    public function getVariables(string $type): ?array
    {
        return $this->inLegacy(function () use ($type) {
            global $app_list_strings, $beanList;

            if (!$this->canEdit()) {
                return null;
            }
            if (!isset($app_list_strings['pdf_template_type_dom'][$type], $beanList[$type])) {
                return [];
            }

            $module = \BeanFactory::newBean($type);
            if (!$module) {
                return [];
            }

            $groups = [];
            foreach ($module->field_defs as $name => $definition) {
                if (!$this->isVariable($definition)) {
                    continue;
                }
                $groups['module'][$module->table_name . '_' . $name] = $this->label($definition['vname'] ?? '', $module->module_dir);
            }

            $labels = [];
            foreach ($module->field_defs as $definition) {
                $related = $definition['module'] ?? '';
                if (($definition['type'] ?? '') !== 'relate' || ($definition['source'] ?? '') !== 'non-db'
                    || $related === '' || $related === 'EmailAddress' || ($definition['vname'] ?? '') === 'LBL_DELETED'
                    || !isset($beanList[$related]) || !($relatedModule = \BeanFactory::newBean($related))) {
                    continue;
                }

                $key = 'relate:' . $definition['name'];
                foreach ($relatedModule->field_defs as $name => $relatedDefinition) {
                    if ($this->isVariable($relatedDefinition) && isset($relatedDefinition['vname'])) {
                        $groups[$key][$definition['name'] . '_' . $name] = $this->label($relatedDefinition['vname'], $relatedModule->module_dir);
                    }
                }
                $labels[$key] = $this->moduleLabel($relatedModule->module_dir) . ' : '
                    . $this->label($definition['vname'] ?? '', $module->module_dir);
            }

            if (!empty($module->lineItems)) {
                $this->addLineItemVariables($groups, $labels);
            }

            asort($labels);
            $result = [['label' => $this->label('LBL_MODULE_NAME', $module->module_dir), 'variables' => $this->toList($groups['module'] ?? [])]];
            foreach ($labels as $key => $label) {
                if (!empty($groups[$key])) {
                    $result[] = ['label' => $label, 'variables' => $this->toList($groups[$key])];
                }
            }

            return $result;
        });
    }

    /**
     * The sample templates, by label, null when the user cannot edit PDF templates.
     *
     * @return array<int, array{label: string, type: string, body: string, header: string, footer: string}>|null
     */
    public function getSamples(): ?array
    {
        return $this->inLegacy(function () {
            if (!$this->canEdit()) {
                return null;
            }

            $directory = realpath($this->legacyDir . '/modules/AOS_PDF_Templates/samples');
            $samples = [];
            foreach ($directory !== false ? (scandir($directory) ?: []) : [] as $file) {
                $path = realpath($directory . DIRECTORY_SEPARATOR . $file);
                if (!preg_match('/^smpl_\w+\.php$/', $file) || $path === false
                    || !str_starts_with($path, $directory . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                require_once $path;
                $class = substr($file, 0, -4);
                if (!class_exists($class)) {
                    continue;
                }

                $sample = new $class();
                $samples[] = [
                    'label' => $this->label('LBL_' . strtoupper(substr($class, 5)), 'AOS_PDF_Templates'),
                    'type' => (string)$sample->getType(),
                    'body' => (string)$sample->getBody(),
                    'header' => (string)$sample->getHeader(),
                    'footer' => (string)$sample->getFooter(),
                ];
            }
            usort($samples, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

            return $samples;
        });
    }

    /**
     * Variables of the line items, as the legacy editor: groups, products, services and
     * currency.
     */
    protected function addLineItemVariables(array &$groups, array &$labels): void
    {
        $lineItems = $this->label('LBL_LINE_ITEMS', 'AOS_Quotes') . ' : ';

        $group = \BeanFactory::newBean('AOS_Line_Item_Groups');
        foreach ($group->field_defs as $name => $definition) {
            if ($this->isVariable($definition)) {
                $groups['groups'][$group->table_name . '_' . $name] = $this->label($definition['vname'] ?? '', $group->module_dir);
            }
        }
        $labels['groups'] = $lineItems . $this->label('LBL_MODULE_NAME', 'AOS_Line_Item_Groups');

        foreach (['AOS_Products_Quotes', 'AOS_Products'] as $productModule) {
            $product = \BeanFactory::newBean($productModule);
            foreach ($product->field_defs as $name => $definition) {
                if ($this->isVariable($definition) && !($productModule === 'AOS_Products' && ($definition['vname'] ?? '') === 'LBL_NAME')) {
                    $groups['products'][$product->table_name . '_' . $name] = $this->label($definition['vname'] ?? '', $product->module_dir);
                }
            }
        }
        $labels['products'] = $lineItems . $this->label('LBL_MODULE_NAME', 'AOS_Products');

        foreach (self::SERVICE_VARIABLES as $name => [$label, $labelModule]) {
            $groups['services'][$name] = $this->label($label, $labelModule);
        }
        $labels['services'] = $lineItems . $this->label('LBL_SERVICE_MODULE_NAME', 'AOS_Products_Quotes');

        $currency = \BeanFactory::newBean('Currencies');
        foreach ($currency->field_defs as $name => $definition) {
            $type = $definition['type'] ?? '';
            if (!isset($definition['vname']) || !$this->isVariable($definition) || in_array($type, ['bool', 'datetime'], true)
                || ($definition['link_type'] ?? '') === 'relationship_info' || $name === 'created_by_name'
                || in_array($definition['vname'], self::CURRENCY_EXCLUDED_LABELS, true)) {
                continue;
            }
            $groups['currencies']['currencies_' . $name] = $this->label($definition['vname'], 'Currencies');
        }
        $labels['currencies'] = $this->label('LBL_MODULE_NAME', 'Currencies') . ' : ' . $this->label('LBL_MODULE_NAME', 'Currencies');
    }

    /**
     * A field usable as a variable: no id or link, reportable.
     */
    protected function isVariable(array $definition): bool
    {
        $type = $definition['type'] ?? '';

        return strtolower($definition['dbType'] ?? '') !== 'id' && $type !== 'id' && $type !== 'link'
            && (!isset($definition['reportable']) || $definition['reportable']);
    }

    /**
     * @return array<int, array{name: string, label: string}>
     */
    protected function toList(array $variables): array
    {
        $list = [];
        foreach ($variables as $name => $label) {
            $list[] = ['name' => (string)$name, 'label' => $label !== '' ? $label : (string)$name];
        }

        return $list;
    }

    protected function label(string $key, string $module): string
    {
        $label = $key !== '' ? translate($key, $module) : '';
        $label = is_string($label) ? $label : $key;

        return rtrim(trim(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8')), ':');
    }

    protected function moduleLabel(string $module): string
    {
        global $app_list_strings;

        return (string)($app_list_strings['moduleList'][$module] ?? $module);
    }

    protected function canEdit(): bool
    {
        return \ACLController::checkAccess('AOS_PDF_Templates', 'edit', true);
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
