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

final class Version20260305100000 extends BaseMigration
{
    public function getDescription(): string
    {
        return 'Add drafts_popup config entry if not already present';
    }

    public function up(Schema $schema): void
    {
        $result = $this->connection->executeQuery(
            "SELECT count(*) AS the_count FROM config WHERE category = 'system' AND name = 'drafts_popup'"
        );

        $row = $result->fetchAssociative();

        if (!empty($row['the_count'])) {
            $this->log('drafts_popup config entry already exists, skipping.');
            return;
        }

        $this->connection->executeStatement(
            "INSERT INTO config (category, name, value) VALUES ('system', 'drafts_popup', '1')"
        );

        $this->log('drafts_popup config entry inserted.');
    }

    public function down(Schema $schema): void
    {
    }
}
