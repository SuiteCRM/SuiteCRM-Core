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

namespace App\Module\EmailMarketing\FieldDefinitions;

use App\FieldDefinitions\Service\VardefConfigMapperInterface;
use App\Module\Campaigns\Service\Email\Trackers\EmailTrackerManagerInterface;

class EmailMarketingTrackersEnabledMapper implements VardefConfigMapperInterface
{

    /**
     * EmailMarketingTrackersEnabledMapper constructor.
     */
    public function __construct(
        protected EmailTrackerManagerInterface $emailTrackerManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'email-marketing-trackers-enabled';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'email-marketing';
    }

    /**
     * @inheritDoc
     */
    public function map(array $vardefs): array
    {
        $isTrackingEnabled = $this->emailTrackerManager->isTrackingEnabled();

        if (!$isTrackingEnabled) {
            $vardefs['trackers_enabled']['readonly'] = true;
            $vardefs['trackers_enabled']['footnotes'] = [
                [
                    'labelKey' => 'LBL_TRACKERS_DISABLED_GLOBALLY_FOOTNOTE',
                    'displayModes' => ['edit', 'create', 'detail'],
                ]
            ];
        }

        return $vardefs;
    }
}
