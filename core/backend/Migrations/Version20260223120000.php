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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Throwable;

final class Version20260223120000 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return 'Migrate saved_search contents from base64+serialize to JSON';
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('entity_manager');
        $connection = $entityManager->getConnection();

        $rows = $connection->executeQuery(
            "SELECT id, contents FROM saved_search WHERE contents IS NOT NULL AND contents != '' AND deleted = '0'"
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $raw = $row['contents'];

            if (empty($raw)) {
                continue;
            }

            // Skip if already JSON
            try {
                $jsonDecoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $t) {
                $jsonDecoded = null;
            }

            if ($jsonDecoded !== null) {
                continue;
            }

            try {
                try {
                    $decoded = @unserialize(base64_decode($raw), ['allowed_classes' => false]);
                } catch (Throwable $t) {
                    $decoded = false;
                }

                if ($decoded === false) {
                    $connection->executeStatement(
                        "UPDATE saved_search SET contents = ? WHERE id = ?",
                        ['', $row['id']]
                    );
                    $this->log('Migration Version20260223120000: Could not decode contents for saved_search id=' . $row['id'] . ' . Contents has been cleared.');
                    continue;
                }

                try {
                    $json = json_encode($decoded, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                } catch (Throwable $t) {
                    $json = '';
                }

                $connection->executeStatement(
                    "UPDATE saved_search SET contents = ? WHERE id = ?",
                    [$json, $row['id']]
                );

                if ($json === '') {
                    $this->log('Migration Version20260223120000: Failed to encode contents to JSON for saved_search id=' . $row['id'] . '. Contents has been cleared.');
                }
            } catch (\Exception $e) {
                $this->log('Migration Version20260223120000: Failed for saved_search id=' . $row['id'] . '. Error: ' . $e->getMessage());
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
