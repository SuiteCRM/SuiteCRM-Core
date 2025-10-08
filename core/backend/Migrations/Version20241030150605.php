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

use App\Security\AppSecretGenerator;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20241030150605 extends BaseMigration implements ContainerAwareInterface
{
    use EnvHandlingMigrationTrait;

    public function getDescription(): string
    {
        return 'Add randomly generated APP_SECRET to .env.$app_env.local';
    }

    public function up(Schema $schema): void
    {
        $env = $_ENV ?? [];

        $appEnv = $_ENV['APP_ENV'] ?? 'prod';

        $envLocalFile = $this->getProjectDir() . "/.env.$appEnv.local";

        if (!empty($env['APP_SECRET'])) {
            $this->log('APP_SECRET already set in ENV, skipping.');
            return;
        }

        if (!file_exists($envLocalFile)) {
            $this->log("File .env.$appEnv.local does not exist, skipping.");
            return;
        }

        $envContents = file_get_contents($envLocalFile);

        $this->addAppSecret($envContents, $envLocalFile);
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * Check and add randomly generated app secret
     * @param $envContents
     * @param string $envFile
     */
    protected function addAppSecret(&$envContents, string $envFile): void
    {
        /** @var AppSecretGenerator $entityManager */
        $appSecretGenerator = $this->container->get('security.app_secret_generator');

        $appSecret = $appSecretGenerator->generate();

        $properties = [
            'APP_SECRET' => $appSecret
        ];

        $wrapperStart = '';
        $wrapperEnd = '';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added randomly generated APP secret');
            $_ENV['APP_SECRET'] = $appSecret;

            return;
        }

        $this->log('APP_SECRET already in .env.$app_env.local, skipping');
    }
}
