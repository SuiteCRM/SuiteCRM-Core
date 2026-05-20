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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20260416120000 extends BaseMigration implements ContainerAwareInterface
{
    protected const SERVICE_KEY = 'migrate-google-calendar-sync';
    protected const USERS_SERVICE_KEY = 'migrate-google-calendar-sync-users';
    protected const CONFIG_CATEGORY = 'calendar_sync';
    protected const CONFIG_NAME = 'google_sync_mig_status';
    protected const USERS_CONFIG_NAME = 'google_sync_users_mig_status';

    public function getDescription(): string
    {
        return 'Google Calendar Sync migration: pre-check and add ManualMigrationTasks';
    }

    public function up(Schema $schema): void
    {
        $this->log('Version20260416120000: Checking for legacy Google Calendar Sync data');

        $meetingsStatus = $this->connection->fetchOne(
            'SELECT value FROM config WHERE category = ? AND name = ? LIMIT 1',
            [self::CONFIG_CATEGORY, self::CONFIG_NAME]
        );
        $usersStatus = $this->connection->fetchOne(
            'SELECT value FROM config WHERE category = ? AND name = ? LIMIT 1',
            [self::CONFIG_CATEGORY, self::USERS_CONFIG_NAME]
        );

        if ($meetingsStatus !== false && $usersStatus !== false) {
            $this->log('Version20260416120000: Both migration statuses already set, skipping');
            return;
        }

        $this->log('Version20260416120000: Seeding migration tasks');

        $now = date('Y-m-d H:i:s');

        if ($meetingsStatus === false) {
            $this->seedTask(
                $now,
                self::SERVICE_KEY,
                'Migrate Google Calendar Sync Data',
                'Migrates legacy Google Calendar sync data (user_preferences/GoogleSync) to the CalendarSync system (ExternalOAuthConnection + CalendarAccount). If this task runs before "Migrate Google Calendar Sync Users", any missed users will be handled by that task — the order in which these tasks are run does not matter.'
            );
            $this->writeConfig(self::CONFIG_NAME, 'pending');
            $this->log('Version20260416120000: google_sync_migration_status set to pending');
        }

        if ($usersStatus === false) {
            $this->seedTask(
                $now,
                self::USERS_SERVICE_KEY,
                'Migrate Google Calendar Sync Users',
                'Migrates legacy Google Calendar sync users (user_preferences/GoogleSync) to the CalendarSync system (ExternalOAuthConnection + CalendarAccount).'
            );
            $this->writeConfig(self::USERS_CONFIG_NAME, 'pending');
            $this->log('Version20260416120000: google_sync_users_migration_status set to pending');
        }
    }

    protected function seedTask(string $now, string $serviceKey, string $name, string $description): void
    {
        $taskExists = (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM manual_migration_tasks WHERE service_key = ? AND deleted = 0',
            [$serviceKey]
        );

        if ($taskExists > 0) {
            return;
        }

        $this->connection->insert('manual_migration_tasks', [
            'id' => create_guid(),
            'name' => $name,
            'type' => 'background',
            'status' => 'initial',
            'service_key' => $serviceKey,
            'description' => $description,
            'date_entered' => $now,
            'date_modified' => $now,
            'created_by' => '1',
            'modified_user_id' => '1',
            'assigned_user_id' => '1',
            'deleted' => 0,
            'allow_failure_retry_action' => 1,
            'allow_failure_rerun_action' => 0,
        ]);

        $this->log("Version20260416120000: ManualMigrationTask '$serviceKey' seeded");
    }

    protected function writeConfig(string $name, string $value): void
    {
        $this->connection->executeStatement(
            'REPLACE INTO config (category, name, value) VALUES (?, ?, ?)',
            [self::CONFIG_CATEGORY, $name, $value]
        );
    }

    public function down(Schema $schema): void
    {
        foreach ([self::SERVICE_KEY, self::USERS_SERVICE_KEY] as $serviceKey) {
            $this->connection->executeStatement(
                'DELETE FROM manual_migration_tasks WHERE service_key = ?',
                [$serviceKey]
            );
        }

        foreach ([self::CONFIG_NAME, self::USERS_CONFIG_NAME] as $configName) {
            $this->connection->executeStatement(
                'DELETE FROM config WHERE category = ? AND name = ?',
                [self::CONFIG_CATEGORY, $configName]
            );
        }
    }
}
