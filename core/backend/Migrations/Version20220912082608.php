<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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

final class Version20220912082608 extends BaseMigration implements ContainerAwareInterface
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
        return 'Add SAML and LDAP settings to .env';
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

        $this->addAuthType($envContents, $envFile);

        $this->addBaseLdapConfig($envContents, $envFile);

        $this->addLdapAutoCreateConfig($envContents, $envFile);

        $this->addSamlConfig($envContents, $envFile);

        $this->addLoginThrottlingConfig($envContents, $envFile);
    }

    /**
     * Check and add missing auth type
     * @param $envContents
     * @param string $envFile
     */
    protected function addAuthType(&$envContents, string $envFile): void
    {
        $properties = [
            'AUTH_TYPE' => 'native'
        ];
        $wrapperStart = '###> AUTH_TYPE ###';
        $wrapperEnd = '###< AUTH_TYPE ###';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);

        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            $this->log('Added AUTH_TYPE to .env.');
            file_put_contents($envFile, $envContents);

            return;
        }

        $this->log('AUTH_TYPE already in .env, skipping.');
    }

    /**
     * Check and add missing ldap configs
     * @param $envContents
     * @param string $envFile
     */
    protected function addBaseLdapConfig(&$envContents, string $envFile): void
    {
        $properties = [
            'LDAP_HOST' => "''",
            'LDAP_PORT' => '389',
            'LDAP_ENCRYPTION' => "'tls'",
            'LDAP_PROTOCOL_VERSION' => '3',
            'LDAP_REFERRALS' => 'false',
            'LDAP_DN_STRING' => "''",
            'LDAP_QUERY_STRING' => "''",
            'LDAP_SEARCH_DN' => "''",
            'LDAP_SEARCH_PASSWORD' => "''"
        ];
        $wrapperStart = '###> LDAP CONFIG ###';
        $wrapperEnd = '###< LDAP CONFIG ###';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added LDAP CONFIG to .env.');

            return;
        }

        $this->log('LDAP CONFIG already in .env, skipping.');
    }

    /**
     * check and add missing ldap auto create configs
     * @param $envContents
     * @param string $envFile
     */
    protected function addLdapAutoCreateConfig(&$envContents, string $envFile): void
    {
        $properties = [
            'LDAP_AUTO_CREATE' => "disabled",
            'LDAP_PROVIDER_BASE_DN' => "''",
            'LDAP_PROVIDER_SEARCH_DN' => "''",
            'LDAP_PROVIDER_SEARCH_PASSWORD' => "''",
            'LDAP_PROVIDER_DEFAULT_ROLES' => "ROLE_USER",
            'LDAP_PROVIDER_UID_KEY' => "''",
            'LDAP_PROVIDER_FILTER' => "''"
        ];
        $wrapperStart = '###> LDAP AUTO CREATE CONFIG ###';
        $wrapperEnd = '###< LDAP AUTO CREATE CONFIG ##';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added LDAP AUTO CREATE CONFIG to .env.');

            return;
        }

        $this->log('LDAP AUTO CREATE CONFIG already in .env, skipping.');
    }

    /**
     * Check and add missing saml config
     * @param $envContents
     * @param string $envFile
     */
    protected function addSamlConfig(&$envContents, string $envFile): void
    {
        $properties = [
            'SAML_USERNAME_ATTRIBUTE' => "uid",
            'SAML_USE_ATTRIBUTE_FRIENDLY_NAME' => "true"
        ];
        $wrapperStart = '###> SAML CONFIG ###';
        $wrapperEnd = '###< SAML CONFIG ###';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added SAML CONFIG to .env.');

            return;
        }

        $this->log('SAML CONFIG already in .env, skipping.');
    }

    /**
     * Check and add missing login throttling config
     * @param $envContents
     * @param string $envFile
     */
    protected function addLoginThrottlingConfig(&$envContents, string $envFile): void
    {
        $properties = [
            'LOGIN_THROTTLING_MAX_ATTEMPTS' => '5'
        ];
        $wrapperStart = '###> login throttling ###';
        $wrapperEnd = '###< login throttling ###';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added LOGIN_THROTTLING_MAX_ATTEMPTS to .env.');

            return;
        }

        $this->log('LOGIN_THROTTLING_MAX_ATTEMPTS already in .env, skipping.');
    }

    public function down(Schema $schema): void
    {
    }
}
