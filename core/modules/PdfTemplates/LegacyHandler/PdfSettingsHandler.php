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
use SuiteCRM\PDF\PDFConfigurator;
use SuiteCRM\PDF\PDFWrapper;
use SuiteCRM\Utility\StringUtils;

/**
 * The PDF settings of the administration (legacy Administration&action=PDFSettings) for the PDF
 * settings view: the PDF engine used to print the PDF templates, saved in the
 * config as the legacy page (pdf.defaultEngine).
 */
class PdfSettingsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'pdf-settings';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The available engines, the selected one and the labels of the page.
     *
     * @return array{engines: array<int, array{value: string, label: string}>, engine: string, labels: array<string, string>}
     */
    public function load(): array
    {
        return $this->inLegacy(function () {
            global $current_language;

            $engines = [];
            foreach (PDFWrapper::getEngines() as $engine) {
                $engines[] = ['value' => $engine, 'label' => (string)StringUtils::camelToTranslation($engine)];
            }

            $strings = return_module_language($current_language, 'Administration');
            $labels = array_filter(
                is_array($strings) ? $strings : [],
                static fn ($label, $key): bool => is_string($label) && in_array($key, [
                    'LBL_PDF_HEADER', 'LBL_PDF_HEADER_DESC', 'LBL_PDF_INTERFACE', 'LBL_PDF_OPTIONS',
                    'LBL_PDF_OPTIONS_HELP', 'LBL_AJAX_SUBMIT_FAIL',
                ], true),
                ARRAY_FILTER_USE_BOTH
            );

            return ['engines' => $engines, 'engine' => PDFWrapper::getDefaultEngine(), 'labels' => $labels];
        });
    }

    /**
     * Saves the PDF engine, if available.
     */
    public function save(string $engine): bool
    {
        return $this->inLegacy(static function () use ($engine) {
            if (!in_array($engine, PDFWrapper::getEngines(), true)) {
                return false;
            }

            require_once 'modules/Configurator/Configurator.php';
            PDFConfigurator::make()->setEngine($engine)->save();

            return true;
        });
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
