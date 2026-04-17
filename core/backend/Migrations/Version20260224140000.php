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

use App\Migrations\BaseMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20260224140000 extends BaseMigration implements ContainerAwareInterface
{
    private const TASKS = [
        [
            'service_key' => 'migrate-contact-photos',
            'name' => 'Migrate Contact Photos',
            'description' => 'Migrates photo field files from the Contacts module from legacy storage (upload/{record_id}_photo) to the new media object storage system.',
        ],
        [
            'service_key' => 'migrate-lead-photos',
            'name' => 'Migrate Lead Photos',
            'description' => 'Migrates photo field files from the Leads module from legacy storage (upload/{record_id}_photo) to the new media object storage system.',
        ],
        [
            'service_key' => 'migrate-prospect-photos',
            'name' => 'Migrate Prospect Photos',
            'description' => 'Migrates photo field files from the Prospects (Targets) module from legacy storage (upload/{record_id}_photo) to the new media object storage system.',
        ],
        [
            'service_key' => 'migrate-user-photos',
            'name' => 'Migrate User Photos',
            'description' => 'Migrates photo field files from the Users module from legacy storage (upload/{record_id}_photo) to the new media object storage system.',
        ],
    ];

    public function getDescription(): string
    {
        return 'Add per-module Person Photo migration tasks (Contacts, Leads, Prospects, Users)';
    }

    public function up(Schema $schema): void
    {
        $this->log('Migration Version20260224140000: Adding per-module Person Photo migration tasks');

        $now = date('Y-m-d H:i:s');

        foreach (self::TASKS as $task) {
            $existing = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM manual_migration_tasks WHERE service_key = ? AND deleted = 0',
                [$task['service_key']]
            );

            if ((int)$existing > 0) {
                $this->log('Migration Version20260224140000: Task ' . $task['service_key'] . ' already exists, skipping');
                continue;
            }

            $this->connection->insert(
                'manual_migration_tasks', [
                    'id' => create_guid(),
                    'name' => $task['name'],
                    'type' => 'background',
                    'status' => 'initial',
                    'service_key' => $task['service_key'],
                    'description' => $task['description'],
                    'date_entered' => $now,
                    'date_modified' => $now,
                    'created_by' => '1',
                    'modified_user_id' => '1',
                    'assigned_user_id' => 1,
                    'deleted' => 0,
                    'allow_failure_retry_action' => 1,
                    'allow_failure_rerun_action' => 0,
                ]
            );

            $this->log('Migration Version20260224140000: Task ' . $task['service_key'] . ' added');
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TASKS as $task) {
            $this->connection->executeStatement(
                'DELETE FROM manual_migration_tasks WHERE service_key = ?',
                [$task['service_key']]
            );
        }
    }
}
