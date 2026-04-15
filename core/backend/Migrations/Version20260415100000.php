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

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260415100000 extends BaseMigration
{
    public function getDescription(): string
    {
        return 'Migrate legacy syncGoogleCalendar schedulers to calendarSyncJob';
    }

    public function up(Schema $schema): void
    {
        try {
            $legacySchedulers = $this->connection->fetchAllAssociative(
                "SELECT id FROM schedulers WHERE deleted = '0' AND job = 'function::syncGoogleCalendar' AND status = 'Active'"
            );

            if (empty($legacySchedulers)) {
                $this->log('Migration Version20260415100000: No legacy syncGoogleCalendar schedulers found, skipping.');
                return;
            }

            $now = date('Y-m-d H:i:s');

            foreach ($legacySchedulers as $scheduler) {
                $this->connection->executeStatement(
                    "UPDATE schedulers SET name = ?, job = ?, date_modified = ? WHERE id = ?",
                    [
                        'Calendar Accounts Sync',
                        'function::calendarSyncJob',
                        $now,
                        $scheduler['id'],
                    ]
                );
                $this->log('Migration Version20260415100000: Migrated scheduler ID: ' . $scheduler['id'] . ' from syncGoogleCalendar to calendarSyncJob.');
            }
        } catch (\Exception $e) {
            $this->log('Migration Version20260415100000: Failed to migrate schedulers. Error: ' . $e->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
    }
}
