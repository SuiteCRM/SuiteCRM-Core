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

namespace App\Module\Maps\Service\Scheduler;

use App\Module\Maps\LegacyHandler\MapsAdminHandler;
use App\Schedulers\Service\SchedulerInterface;
use Psr\Log\LoggerInterface;

/**
 * Geocodes the addresses with OpenStreetMap (MapsAdminHandler::geocodeScheduled()): when it is the
 * map provider, within the geocoding hours of the maps settings, the modules geocoded with it,
 * within the geocoding limits. Meant to run often (every 15 minutes): with Google Maps or outside
 * the hours it does nothing.
 */
class MapsGeocodeScheduler implements SchedulerInterface
{
    public const SCHEDULER_KEY = 'scheduler::maps-geocode';

    public function __construct(
        protected MapsAdminHandler $maps,
        protected LoggerInterface $logger
    ) {
    }

    public function getKey(): string
    {
        return self::SCHEDULER_KEY;
    }

    public function run(): bool
    {
        try {
            $done = $this->maps->geocodeScheduled(new \DateTimeImmutable());
        } catch (\Throwable $throwable) {
            $this->logger->error('Maps geocoding scheduler failed: ' . $throwable->getMessage());

            return false;
        }

        $this->logger->info('Maps geocoding scheduler: ' . $done);

        return true;
    }
}
