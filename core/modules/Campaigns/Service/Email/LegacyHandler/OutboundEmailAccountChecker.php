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

namespace App\Module\Campaigns\Service\Email\LegacyHandler;

use App\Emails\LegacyHandler\Mailers\LegacyMailer;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use BeanFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OutboundEmailAccountChecker extends LegacyHandler
{
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected LoggerInterface $logger,
        protected LegacyMailer $legacyMailer
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return 'outbound-email-account-checker';
    }
    public function isConnected($outboundEmailId): bool
    {
        return $this->legacyMailer->isConnected($outboundEmailId);
    }

    public function isConfigured(string $outboundEmailId): bool
    {
        $this->init();

        $bean = BeanFactory::getBean('OutboundEmailAccounts', $outboundEmailId);


        $this->close();

        if (empty($bean)) {
            $this->logger->warning(
                'Campaigns:OutboundEmailAccountChecker::isConfigured - Outbound Email Account not found | id - ' . $outboundEmailId
            );
            return false;
        }

        if (empty($bean->mail_smtpserver) || empty($bean->mail_smtpport)) {
            return false;
        }

        if (empty($bean->mail_smtpuser)) {
            return false;
        }

        if ($bean->auth_type === 'oauth') {
            $externalOAuthConnection = BeanFactory::getBean('ExternalOAuthConnection', $bean->external_oauth_connection_id);

            if (empty($externalOAuthConnection) || empty($externalOAuthConnection->id)) {
                $this->logger->warning(
                    'Campaigns:OutboundEmailAccountChecker::isConfigured - Outbound Email Account has OAuth authentication but External OAuth Connection not found | id - ' . $outboundEmailId
                );
                return false;
            }

            return true;
        }

        $authRequired = !empty($bean->mail_smtpauth_req);

        if ($authRequired && (empty($bean->mail_smtppass))) {
            return false;
        }

        return true;
    }
}
