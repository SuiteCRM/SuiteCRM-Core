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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20240617150845 extends BaseMigration implements ContainerAwareInterface
{
    use EnvHandlingMigrationTrait;

    public function getDescription(): string
    {
        return 'Add saml configs to .env';
    }

    public function up(Schema $schema): void
    {
        $envFile = $this->getProjectDir() . "/.env";

        if (!file_exists($envFile)) {
            $this->log('File .env does not exist, skipping.');
            return;
        }

        $envContents = file_get_contents($envFile);

        $this->addSamlConfig($envContents, $envFile);

    }

    public function down(Schema $schema): void
    {
    }

    /**
     * Check and add missing saml configs
     * @param $envContents
     * @param string $envFile
     */
    protected function addSamlConfig(&$envContents, string $envFile): void
    {
        $properties = [
            'SAML_AUTOCREATE_ATTRIBUTES_MAP' => '{}',
            'SAML_IDP_ENTITY_ID' => '',
            'SAML_IDP_SSO_URL' => '',
            'SAML_IDP_SLO_URL' => '',
            'SAML_IDP_X509CERT' => '',
            'SAML_SP_ENTITY_ID' => '',
            'SAML_SP_PRIVATE_KEY' => '',
            'SAML_SP_CERT' => '',
            'SAML_STRICT' => '',
            'SAML_DEBUG' => '',
            'SAML_NAME_ID_ENCRYPTED' => false,
            'SAML_AUTHN_REQUESTS_SIGNED' => false,
            'SAML_LOGOUT_REQUEST_SIGNED' => false,
            'SAML_LOGOUT_RESPONSE_SIGNED' => false,
            'SAML_SIGN_METADATA' => false,
            'SAML_WANT_MESSAGES_SIGNED' => false,
            'SAML_WANT_ASSERTIONS_ENCRYPTED' => false,
            'SAML_WANT_ASSERTIONS_SIGNED' => false,
            'SAML_WANT_NAME_ID' => false,
            'SAML_WANT_NAME_ID_ENCRYPTED' => false,
            'SAML_REQUESTED_AUTHN_CONTEXT' => false,
            'SAML_WANT_XML_VALIDATION' => false,
            'SAML_RELAX_DESTINATION_VALIDATION' => false,
            'SAML_DESTINATION_STRICTLY_MATCHES' => false,
            'SAML_ALLOW_REPEAT_ATTRIBUTE_NAME' => false,
            'SAML_REJECT_UNSOLICITED_RESPONSES_WITH_IN_RESPONSE_TO' => false,
            'SAML_LOWERCASE_URL_ENCODING' => false,
            'SAML_COMPRESS_REQUESTS' => false,
            'SAML_COMPRESS_RESPONSES' => false,
            'SAML_CONTACT_TECHNICAL_GIVEN_NAME' => 'Tech User',
            'SAML_CONTACT_TECHNICAL_EMAIL_ADDRESS' => 'techuser@example.com',
            'SAML_CONTACT_SUPPORT_GIVEN_NAME' => 'Support User',
            'SAML_CONTACT_SUPPORT_EMAIL_ADDRESS' => 'supportuser@example.com',
            'SAML_CONTACT_ADMINISTRATIVE_GIVEN_NAME' => 'Administrative User',
            'SAML_CONTACT_ADMINISTRATIVE_EMAIL_ADDRESS' => 'administrativeuser@example.com',
            'SAML_ORGANIZATION_NAME' => 'Example',
            'SAML_ORGANIZATION_DISPLAY_NAME' => 'Example',
            'SAML_ORGANIZATION_URL' => 'http://example.com'
        ];

        $wrapperStart = '###> SAML CONFIG ###';
        $wrapperEnd = '###< SAML CONFIG ###';

        $propertiesToAdd = $this->getContentToAdd($envContents, $properties, $wrapperStart, $wrapperEnd);
        if (!empty($propertiesToAdd)) {
            $envContents .= $propertiesToAdd;
            file_put_contents($envFile, $envContents);
            $this->log('Added SAML CONFIGS to .env.');

            return;
        }

        $this->log('SAML CONFIGS already in .env, skipping.');
    }
}
