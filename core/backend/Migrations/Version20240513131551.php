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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20240513131551 extends BaseMigration implements ContainerAwareInterface
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

    public function getDescription(): string
    {
        return 'Add log level and log filename configs to .env';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $envFile = $this->getProjectDir() . "/.env";

        if (!file_exists($envFile)) {
            return;
        }

        $envContents = file_get_contents($envFile);

        $this->addLogConfig($envContents, $envFile);

    }

    public function down(Schema $schema): void
    {
    }

    /**
     * Check and add missing log config
     * @param $envContents
     * @param string $envFile
     */
    protected function addLogConfig(&$envContents, string $envFile): void
    {
        $properties = [
            'MAIN_LOG_LEVEL' => 'warning',
            'DEPRECATION_LOG_LEVEL' => 'error',
            'SECURITY_LOG_LEVEL' => 'error',
            '#LOG_DIR' => "'my_log_dir' # When not set defaults to logs/current-env/, e.g: logs/prod/. Can also set absolute path, e.g. '/path-to-suitecrm/my_log_dir'",
            '#MAIN_LOG_FILE_NAME' => 'my_main.log # when not set defaults to prod.log or dev.log',
            '#DEPRECATION_LOG_FILE_NAME' => 'my_deprecation.log # when not set defaults to prod.deprecation.log or dev.deprecation.log',
            '#SECURITY_LOG_FILE_NAME' => 'my_security.log # when not set defaults to prod.security.log or dev.security.log'
        ];

        $wrapperStart = '###> logs ###';
        $wrapperEnd = '###< logs ###';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added LOG CONFIGS to .env.');

            return;
        }

        $this->log('LOG CONFIGS already in .env, skipping.');
    }
}
