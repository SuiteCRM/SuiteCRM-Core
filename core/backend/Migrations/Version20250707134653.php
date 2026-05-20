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

final class Version20250707134653 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return 'Add missing Target List Email Marketing Relationship';
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('entity_manager');

        $query = "SELECT em.id, plc.prospect_list_id FROM campaigns
        JOIN email_marketing em ON em.campaign_id = campaigns.id
        JOIN prospect_list_campaigns plc ON plc.campaign_id = campaigns.id
        LEFT JOIN prospect_lists pl ON pl.id = plc.prospect_list_id
        WHERE plc.deleted = '0' AND campaigns.deleted = '0' AND em.deleted = '0' AND pl.list_type != 'test'";

        $rows = [];

        $this->log('Migration Version20250707134653: Retrieving all Target Lists associated and Email Marketing
        associated with Campaigns');

        try {
            $result = $entityManager->getConnection()->executeQuery($query);
            $rows = $result->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->log('Migration Version20250707134653: Failed to retrieve Target Lists and Email Marketing associated
            with Campaigns. Error: ' . $e->getMessage());
        }

        $this->log('Migration Version20250707134653: Updating Email Marketing Target Lists to Match Campaigns');

        foreach ($rows as $row) {

            $newId = "'" . create_guid() . "'";
            $prospectId = "'" . $row['prospect_list_id'] . "'";
            $marketingId = "'" . $row['id'] . "'";

            $query = "INSERT INTO email_marketing_prospect_lists (id, prospect_list_id, email_marketing_id, date_modified)
            VALUES ($newId,$prospectId,$marketingId, NOW())";

            try {
                $entityManager->getConnection()->executeQuery($query);
            } catch (\Exception $e) {
                $this->log('Migration Version20250707134653: Failed to update Email Marketing Table with id: ' . $marketingId
                . 'and target list id:' . $prospectId . 'Error: ' . $e->getMessage());
            }
        }

    }

    public function down(Schema $schema): void
    {
    }
}
