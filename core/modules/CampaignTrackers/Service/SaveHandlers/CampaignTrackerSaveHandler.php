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

namespace App\Module\CampaignTrackers\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;

/**
 * Save of a campaign tracker from the record view, as the legacy edit view (CampaignTrackers/
 * Save.php): an opt-out tracker leads to the opt-out entry point, whatever URL is typed (the
 * legacy form disables the URL of an opt-out tracker). The bean adds the scheme to the other URLs.
 */
class CampaignTrackerSaveHandler implements RecordSaveHandlerInterface
{
    public const OPT_OUT_URL = 'index.php?entryPoint=removeme';

    public function getKey(): string
    {
        return 'campaign-tracker-save';
    }

    public function getModule(): string
    {
        return 'campaign-trackers';
    }

    public function getModes(): array
    {
        return ['before-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        $attributes = $inputRecord->getAttributes() ?? [];
        if (!array_key_exists('is_optout', $attributes)) {
            return;
        }

        $optOut = in_array($attributes['is_optout'], [true, 1, '1', 'true', 'on'], true);
        $attributes['is_optout'] = $optOut ? 1 : 0;
        if ($optOut) {
            $attributes['tracker_url'] = self::OPT_OUT_URL;
        }

        $inputRecord->setAttributes($attributes);
    }
}
