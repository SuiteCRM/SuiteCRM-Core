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
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20231108164138  extends BaseMigration implements ContainerAwareInterface
{
    use EnvHandlingMigrationTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var LoggerInterface
     */
    protected $upgradeLogger;

    public function getDescription() : string
    {
        return 'Remove pdf from allowed_preview';
    }

    public function up(Schema $schema) : void
    {
        $systemConfigsHandler = $this->container->get('app.system-configs');
        $systemConfigs = $systemConfigsHandler->getConfigs();
        if (isset($systemConfigs['allowed_preview']) && in_array('pdf', $systemConfigs['allowed_preview'])) {
            $key = array_search('pdf', $systemConfigs['allowed_preview']);
            unset($systemConfigs['allowed_preview'][$key]);
            $systemConfigsHandler->updateSystemConfig($systemConfigs);
            $this->log('Removed PDF from allowed_preview inside config file.');
            return;
        }

        $this->log('PDF was not found in allowed_preview config skipping...');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
