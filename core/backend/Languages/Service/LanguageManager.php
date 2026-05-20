<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Languages\Service;

use App\Authentication\LegacyHandler\UserHandler;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Languages\LegacyHandler\AppListStringsProviderInterface;
use Psr\Log\LoggerInterface;

class LanguageManager implements LanguageManagerInterface
{

    public function __construct(
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected AppListStringsProviderInterface $appListStringsProvider,
        protected AppStringsProviderInterface $appStringsProvider,
        protected UserHandler $userHandler,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getListLabel(string $module, string $fieldName, string $value): string
    {
        $optionsKey = $this->fieldDefinitionsProvider->getOptionsKey($module, $fieldName);
        if (!$optionsKey) {
            return $value;
        }

        $language = $this->userHandler->getCurrentLanguage();

        $appListStrings = $this->appListStringsProvider->getAppListStrings($language);
        if (!$appListStrings) {
            return $value;
        }

        $translatedOptions = $appListStrings->getItems()[$optionsKey] ?? null;
        if (!$translatedOptions || !is_array($translatedOptions)) {
            return $value;
        }

        return $translatedOptions[$value] ?? $value;
    }

    public function getAppLabel(string $labelKey): string
    {
        if (empty($labelKey)) {
            return $labelKey;
        }

        $language = $this->userHandler->getCurrentLanguage();

        $appStrings = $this->appStringsProvider->getAppStrings($language);
        if (!$appStrings) {
            return $labelKey;
        }

        $label = $appStrings->getItems()[$labelKey] ?? null;
        if (!is_string($label)) {
            return $labelKey;
        }

        return $label;
    }

}
