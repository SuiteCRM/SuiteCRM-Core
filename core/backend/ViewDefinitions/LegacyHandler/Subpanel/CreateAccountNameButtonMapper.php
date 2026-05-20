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

namespace App\ViewDefinitions\LegacyHandler\Subpanel;

class CreateAccountNameButtonMapper extends CreateButtonMapper
{
    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'SubPanelTopCreateAccountNameButton';
    }

    /**
     * @inheritDoc
     */
    public function map(
        string $parentModule,
        array $subpanel,
        array $button,
        array $parentVardefs
    ): array {
        $button = parent::map($parentModule, $subpanel, $button, $parentVardefs);

        $additionalFields = $button['additionalFields'] ?? [];
        $additionalFields['primary_address_street'] = 'billing_address_street';
        $additionalFields['primary_address_city'] = 'billing_address_city';
        $additionalFields['primary_address_state'] = 'billing_address_state';
        $additionalFields['primary_address_country'] = 'billing_address_country';
        $additionalFields['primary_address_postalcode'] = 'billing_address_postalcode';
        $additionalFields['phone_work'] = 'phone_office';

        if ($parentModule === 'Accounts') {
            $additionalFields['account_name'] = 'name';
            $additionalFields['account_id'] = 'id';
        } else {
            $additionalFields['account_name'] = 'account_name.name';
            $additionalFields['account_id'] = 'account_id';
        }


        $button['additionalFields'] = $additionalFields;

        return $button;
    }
}
