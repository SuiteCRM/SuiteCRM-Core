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

namespace App\UserPreferences\DataProvider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use App\UserPreferences\Entity\UserPreference;
use App\UserPreferences\Service\UserPreferencesProviderInterface;

/**
 * Class UserPreferenceProvider
 */
final class UserPreferenceStateProvider implements ProviderInterface
{

    /**
     * @var UserPreferencesProviderInterface
     */
    private $userPreferenceService;

    /**
     * UserPreferenceStateProvider constructor.
     * @param UserPreferencesProviderInterface $userPreferenceService
     */
    public function __construct(UserPreferencesProviderInterface $userPreferenceService)
    {
        $this->userPreferenceService = $userPreferenceService;
    }

    /**
     * Get user preference
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return ArrayPaginator|UserPreference|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|UserPreference|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $preferences = $this->userPreferenceService->getAllUserPreferences();
            return new ArrayPaginator($preferences, 0, count($preferences));
        }

        return $this->userPreferenceService->getUserPreference($uriVariables['id'] ?? '');
    }
}
