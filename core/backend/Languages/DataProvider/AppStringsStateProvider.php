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

namespace App\Languages\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Languages\Entity\AppStrings;
use App\Languages\LegacyHandler\AppStringsHandler;

/**
 * Class AppStringsStateProvider
 */
final class AppStringsStateProvider implements ProviderInterface
{

    /**
     * @var AppStringsHandler
     */
    private $appStringsHandler;

    /**
     * AppStringsStateProvider constructor.
     * @param AppStringsHandler $appStringsHandler
     */
    public function __construct(AppStringsHandler $appStringsHandler)
    {
        $this->appStringsHandler = $appStringsHandler;
    }

    /**
     * Get app strings for given language id
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return AppStrings|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AppStrings
    {
        return $this->appStringsHandler->getAppStrings($uriVariables['id'] ?? '');
    }
}
