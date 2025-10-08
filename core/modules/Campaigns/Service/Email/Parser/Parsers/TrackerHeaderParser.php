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

namespace App\Module\Campaigns\Service\Email\Parser\Parsers;

use App\Data\Entity\Record;
use App\Module\Campaigns\Service\Email\Parser\CampaignEmailParserInterface;
use App\Module\Campaigns\Service\Email\Trackers\EmailTrackerManagerInterface;
use Psr\Log\LoggerInterface;

class TrackerHeaderParser implements CampaignEmailParserInterface
{

    public function __construct(
        protected EmailTrackerManagerInterface $trackerManager,
        protected LoggerInterface $logger
    ) {
    }

    public function getKey(): string
    {
        return 'tracker-header-parser';
    }

    public function getOrder(): int
    {
        return 4;
    }

    public function applies(Record $record, array $context): bool
    {
        /** @var Record $emailMarketing */
        $emailMarketing = $context['emailMarketingRecord'] ?? null;

        if ($emailMarketing === null) {
            return false;
        }

        return true;
    }

    public function parse(Record $record, array $context): void
    {
        $trackerId = $context['trackerId'] ?? null;
        if ($trackerId === null) {
            $this->logger->debug(
                'Campaigns:TrackerHeaderParser::parse - missing trackerId - id: ' . $trackerId, [
                    'trackerId' => $trackerId,
                ]
            );
            return;
        }

        $attributes = $record->getAttributes() ?? [];

        $headers = $attributes['headers'] ?? [];

        $headers['X-CampTrackID'] = $trackerId;

        $attributes['headers'] = $headers;
        $record->setAttributes($attributes);
    }
}
