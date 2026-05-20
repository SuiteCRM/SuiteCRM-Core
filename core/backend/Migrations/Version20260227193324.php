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

final class Version20260227193324 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return 'Move media upload directories: uploads → media, public/media-upload → public/media';
    }

    public function up(Schema $schema): void
    {
        $projectDir = $this->getProjectDir();

        $moves = [
            [
                'from' => $projectDir . '/uploads',
                'to' => $projectDir . '/media',
            ],
            [
                'from' => $projectDir . '/public/media-upload',
                'to' => $projectDir . '/public/media',
            ],
        ];

        foreach ($moves as $move) {
            $from = $move['from'];
            $to = $move['to'];

            if (!is_dir($from)) {
                $this->log("Source directory does not exist, skipping: $from");
                continue;
            }

            if (is_dir($to)) {
                $this->log("Target directory already exists, skipping: $to");
                continue;
            }

            try {
                $renamed = rename($from, $to);

                if (!$renamed) {
                    $this->write('WARNING: COULD NOT MOVE DIRECTORY "' . $from . '" TO "' . $to . '". YOU DO NOT HAVE THE REQUIRED PERMISSIONS. PLEASE MOVE THE DIRECTORY MANUALLY.');
                    continue;
                }

                $this->log("Moved $from → $to");
            } catch (\Exception $e) {
                $this->write('WARNING: COULD NOT MOVE DIRECTORY "' . $from . '" TO "' . $to . '". YOU DO NOT HAVE THE REQUIRED PERMISSIONS. PLEASE MOVE THE DIRECTORY MANUALLY. ERROR: ' . $e->getMessage());
            }
        }
    }

    protected function write(string $message): void
    {
        $this->warnIf(true, $message);
    }

    public function down(Schema $schema): void
    {
        $projectDir = $this->getProjectDir();

        $moves = [
            [
                'from' => $projectDir . '/media',
                'to' => $projectDir . '/uploads',
            ],
            [
                'from' => $projectDir . '/public/media',
                'to' => $projectDir . '/public/media-upload',
            ],
        ];

        foreach ($moves as $move) {
            $from = $move['from'];
            $to = $move['to'];

            if (!is_dir($from)) {
                $this->log("Source directory does not exist, skipping: $from");
                continue;
            }

            if (is_dir($to)) {
                $this->log("Target directory already exists, skipping: $to");
                continue;
            }

            try {
                $renamed = rename($from, $to);

                if (!$renamed) {
                    $this->write('WARNING: COULD NOT MOVE DIRECTORY "' . $from . '" TO "' . $to . '". YOU DO NOT HAVE THE REQUIRED PERMISSIONS. PLEASE MOVE THE DIRECTORY MANUALLY.');
                    continue;
                }

                $this->log("Moved $from → $to");
            } catch (\Exception $e) {
                $this->write('WARNING: COULD NOT MOVE DIRECTORY "' . $from . '" TO "' . $to . '". YOU DO NOT HAVE THE REQUIRED PERMISSIONS. PLEASE MOVE THE DIRECTORY MANUALLY. ERROR: ' . $e->getMessage());
            }
        }
    }
}
