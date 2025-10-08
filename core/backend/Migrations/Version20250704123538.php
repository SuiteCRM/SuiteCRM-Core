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

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20250704123538 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return 'Add new "status" and "queueing_status" fields to db';
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('entity_manager');

        $this->log('Migration Version20250704123538: Adding new columns "status" and "queueing_status" to Email Marketing Table');

        try {
            $entityManager->getConnection()->executeQuery('ALTER TABLE email_marketing ADD COLUMN `status` varchar(100) NULL');
        } catch (\Exception $e) {
            $this->log('Migration Version20250704123538: Failed to add column status to Email Marketing Table. Error: ' . $e->getMessage());
        }

        try {
            $entityManager->getConnection()->executeQuery('ALTER TABLE email_marketing ADD COLUMN `queueing_status` varchar(100) NULL');
        } catch (\Exception $e) {
            $this->log('Migration Version20250704123538: Failed to add column queueing_status on Email Marketing Table. Error: ' . $e->getMessage());
        }

        $this->log('Migration Version20250704123538: Updating new columns "status" and "queueing_status" to value "sent" and "finished"');

        try {
            $entityManager->getConnection()->executeQuery("UPDATE email_marketing SET status = 'sent' WHERE type = 'legacy' AND deleted = '0'");
        } catch (\Exception $e) {
            $this->log('Migration Version20250704123538: Failed to update column status on Email Marketing Table. Error: ' . $e->getMessage());
        }

        try {
            $entityManager->getConnection()->executeQuery("UPDATE email_marketing SET queueing_status = 'finished' WHERE type = 'legacy' AND deleted = '0'");
        } catch (\Exception $e) {
            $this->log('Migration Version20250704123538: Failed to update column queueing_status on Email Marketing Table. Error: ' . $e->getMessage());
        }

    }

    public function down(Schema $schema): void
    {
    }
}
