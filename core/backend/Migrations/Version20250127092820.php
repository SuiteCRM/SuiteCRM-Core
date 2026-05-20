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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20250127092820 extends BaseMigration implements ContainerAwareInterface
{

    public function getDescription(): string
    {
        return 'Update Log Dir in config.php';
    }

    public function up(Schema $schema): void
    {

        $systemConfigsHandler = $this->container->get('app.system-configs');
        $systemConfigs = $systemConfigsHandler?->getConfigs();

        if ($systemConfigs['log_dir'] === '../../logs/legacy') {
            $this->log('Log Dir already up to date');
            return;
        }

        $logPaths = [
          '.',
          '',
          './'
        ];

        if (in_array($systemConfigs['log_dir'], $logPaths, true)) {
            $systemConfigs['log_dir'] = '../../logs/legacy';
            $systemConfigsHandler?->updateSystemConfig($systemConfigs);
            $this->log('Updated Log Dir in config.php');
            return;
        }

        $this->log('Log Dir already Updated');
    }

    public function down(Schema $schema): void
    {
    }
}
