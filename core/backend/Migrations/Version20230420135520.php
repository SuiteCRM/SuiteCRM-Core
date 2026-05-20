<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

final class Version20230420135520 extends BaseMigration implements ContainerAwareInterface
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
        return 'Add login throttling interval to .env';
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

        $this->addLoginThrottlingConfig($envContents, $envFile);

    }

    public function down(Schema $schema): void
    {
    }

    /**
     * Check and add missing login throttling config
     * @param $envContents
     * @param string $envFile
     */
    protected function addLoginThrottlingConfig(&$envContents, string $envFile): void
    {
        $properties = [
            'LOGIN_THROTTLING_IP_LOGIN_MAX_ATTEMPTS' => '50',
            'LOGIN_THROTTLING_INTERVAL' => '"30 minutes"'
        ];

        $wrapperStart = '###> login throttling ###';
        $wrapperEnd = '###< login throttling ###';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added LOGIN_THROTTLING_INTERVAL to .env.');

            return;
        }

        $this->log('LOGIN_THROTTLING_INTERVAL already in .env, skipping.');
    }
}
