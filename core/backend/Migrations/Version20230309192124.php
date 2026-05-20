<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

final class Version20230309192124 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('entity_manager');

        try {
            $entityManager->getConnection()->executeQuery('ALTER TABLE alerts ADD COLUMN `snooze` datetime  NULL');
            $entityManager->getConnection()->executeQuery('ALTER TABLE alerts ADD COLUMN `date_start` datetime NULL');
        } catch (\Exception $e) {
        }

        $this->addSql('UPDATE alerts SET `snooze` = `date_entered` WHERE `snooze` IS NULL');

        $this->addStartDate($entityManager);
    }

    public function down(Schema $schema): void
    {
    }

    public function addStartDate(EntityManagerInterface $entityManager): void
    {

        $urls = [];
        $id = '';
        $module = '';

        $stmt = $entityManager->getConnection()->prepare('SELECT * FROM alerts');

        $result = $stmt->executeQuery();

        $rows = $result->fetchAllAssociative();

        if (empty($rows)){
            $this->log('Could not fetch all Alert records');
            return;
        }

        foreach ($rows as $row) {
            if (!isset($row['url_redirect'])){
                continue;
            }

            $urls[] = $row['url_redirect'];
        }

        if (!isset($urls)){
            $this->log('Alerts do not have an associated url.');
            return;
        }

        foreach($urls as $url) {

            $splitUrl = explode('&', $url);

            foreach($splitUrl as $split) {

                if (str_contains($split, 'module')) {
                    $module = explode('=', $split)[1];
                }

                if (str_contains($split, 'record')) {
                    $id = explode('=', $split)[1];
                }
            }

            if (empty($id) || empty($module)) {
                $this->log("Unable to find ID or Module");
                return;
            }

            $stmt = $entityManager->getConnection()->prepare('SELECT * FROM ' .  strtolower($module) . ' WHERE id=:id');

            if (empty($stmt)) {
                continue;
            }


            $result = $stmt->executeQuery(['id' => $id]);

            $row = $result->fetchAllAssociative();

            if (empty($row)){
                $this->log('Unable to find records with the ID' . $id);
                continue;
            }

            if (!isset($row[0]['date_start'])) {
                $this->log('Start date alreay set for ' . $id);
                continue;
            }

            $stmt = $entityManager->getConnection()->prepare('UPDATE alerts SET `date_start` = :date_start WHERE `url_redirect` = :url');

            if (empty($stmt)){
                continue;
            }

            $stmt->executeQuery(['date_start' => $row[0]['date_start'], 'url' => $url]);
        }
    }




}
