<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

namespace App\Languages\LegacyHandler;

use ApiPlatform\Exception\ItemNotFoundException;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Languages\Entity\AppListStrings;
use Symfony\Component\HttpFoundation\RequestStack;

class AppListStringsHandler extends LegacyHandler implements AppListStringsProviderInterface
{
    protected const MSG_LANGUAGE_NOT_FOUND = 'Not able to get language: ';
    public const HANDLER_KEY = 'app-list-strings';
    protected array $language;

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        array $language
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );

        $this->language = $language;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Get app list strings for given $language
     * @param string $language
     * @return AppListStrings|null
     */
    public function getAppListStrings(string $language): ?AppListStrings
    {
        if (empty($language)) {
            return null;
        }

        $this->init();

        $enabledLanguages = get_languages();

        if (empty($enabledLanguages) || !array_key_exists($language, $enabledLanguages)) {
            throw new ItemNotFoundException(self::MSG_LANGUAGE_NOT_FOUND . "'$language'");
        }

        $appListStringsArray = return_app_list_strings_language($language);
        $appListStringsArray = $this->decodeLabels($appListStringsArray);

        $appListStringsArray = $this->injectPluginAppListStrings($language, $appListStringsArray);

        if (empty($appListStringsArray)) {
            throw new ItemNotFoundException(self::MSG_LANGUAGE_NOT_FOUND . "'$language'");
        }

        $appListStrings = new AppListStrings();
        $appListStrings->setId($language);
        $appListStrings->setItems($appListStringsArray);

        $this->close();

        return $appListStrings;
    }

    protected function decodeLabels(array $appListStringsArray): array
    {
        foreach ($appListStringsArray as $key => $string) {
            if (!is_array($string)) {
                $string = html_entity_decode($string ?? '', ENT_QUOTES);
            }
            $appListStringsArray[$key] = $string;
        }

        return $appListStringsArray;
    }

    /**
     * @param string $language
     * @param array $appListStringsArray
     * @return array
     */
    protected function injectPluginAppListStrings(string $language, array $appListStringsArray): array
    {
        $aooListStrings = $this->language[$language]['lists'] ?? [];
        return array_merge($appListStringsArray, $aooListStrings);
    }
}
