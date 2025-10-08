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


namespace App\Module\Campaigns\Service\Email\Targets;

use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Module\Campaigns\Service\Email\Trackers\EmailTrackerManagerInterface;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailOptInManager implements EmailOptInManagerInterface
{
    public function __construct(
        protected SystemConfigHandler $systemConfigHandler,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
        protected EmailTrackerManagerInterface $emailTrackerManager
    ) {
    }

    public function isOptedIn(
        Record $targetRecord,
        Record $marketingRecord,
        string $campaignId,
        string $prospectListId
    ): bool {
        $optInLevel = '';

        $config = $this->systemConfigHandler->getSystemConfig('email_enable_confirm_opt_in');
        if ($config !== null) {
            $optInLevel = $config->getValue() ?? '';
        }

        // Find email address
        $email_address = trim($targetRecord->getAttributes()['email1'] ?? '');

        if (empty($email_address)) {
            return false;
        }

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder->select('*')
                     ->from('email_addr_bean_rel')
                     ->innerJoin(
                         'email_addr_bean_rel',
                         'email_addresses',
                         'ea',
                         'ea.id = email_addr_bean_rel.email_address_id'
                     )
                     ->where('email_addr_bean_rel.bean_id = :beanId')
                     ->andWhere('email_addr_bean_rel.deleted = 0')
                     ->andWhere('email_addr_bean_rel.primary_address = 1')
                     ->andWhere('ea.email_address = :emailAddress')
                     ->setParameter('beanId', $targetRecord->getId())
                     ->setParameter('emailAddress', $email_address);

        try {
            $emailAddressInfo = $queryBuilder->fetchAssociative();
        } catch (Exception $e) {
            $this->logger->error('EmailOptInManager::isOptedIn query failed  |  target - ' . $targetRecord->getId() . ' | ' . $e->getMessage());
        }

        $this->logger->debug(
            'EmailOptInManager::isOptedIn - Email address info | target - ' . $targetRecord->getId() . ' | email address - ' . $email_address, [
                'emailAddressInfo' => $emailAddressInfo,
            ]
        );

        if (!empty($emailAddressInfo)) {
            if ((int)$emailAddressInfo['opt_out'] === 1) {
                return false;
            }

            if ((int)$emailAddressInfo['invalid_email'] === 1) {
                return false;
            }

            if (
                $optInLevel === \SugarEmailAddress::COI_STAT_DISABLED
                && (int)$emailAddressInfo['opt_out'] === 0
            ) {
                return true;
            }

            if (
                $optInLevel === \SugarEmailAddress::COI_STAT_OPT_IN
                && false === ($emailAddressInfo['confirm_opt_in'] === \SugarEmailAddress::COI_STAT_OPT_IN
                    || $emailAddressInfo['confirm_opt_in'] === \SugarEmailAddress::COI_STAT_CONFIRMED_OPT_IN)
            ) {
                return false;
            }

            if (
                $optInLevel == \SugarEmailAddress::COI_STAT_CONFIRMED_OPT_IN
                && $emailAddressInfo['confirm_opt_in'] !== \SugarEmailAddress::COI_STAT_CONFIRMED_OPT_IN
            ) {
                return false;
            }
        }

        return true;
    }

    public function addUnsubscribeLink(string $trackerId, string $emailBody, array $context): string
    {
        $url = $this->emailTrackerManager->getTrackingUrl() . "index.php?entryPoint=removeme&identifier=$trackerId";

        $containsLinkVariable = $this->containsUnsubscribeLinkVariable($emailBody);

        if ($containsLinkVariable) {

            $replaced = preg_replace(
                '/{{\s*unsubscribe_link\s*}}/',
                $url,
                $emailBody
            );

            $replaced = preg_replace(
                '/%7B%7B\s*unsubscribe_link\s*%7D%7D/',
                $url,
                $replaced
            );

            $this->logger->debug(
                'Campaigns:DefaultEmailOptInManager::addUnsubscribeLink - Added unsubscribe link to tag - id: ' . $trackerId, [
                    'trackerId' => $trackerId,
                    'unsubscribeUrl' => $url,
                    'newEmailBody' => $replaced,
                ]
            );

            return $replaced;
        }

        $emailBody .= "<br /><span style='font-size:0.8em'><a href='" . $url . " '>Unsubscribe</a></span>";

        $this->logger->debug(
            'Campaigns:DefaultEmailOptInManager::addUnsubscribeLink - Added new tag with unsubscribe link - id: ' . $trackerId, [
                'trackerId' => $trackerId,
                'unsubscribeUrl' => $url,
                'newEmailBody' => $emailBody,
            ]
        );

        return $emailBody;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function containsUnsubscribeLinkVariable(string $value): bool
    {
        return (bool)(preg_match('/{{\s*unsubscribe_link\s*}}/', $value) || preg_match('/%7B%7B\s*unsubscribe_link\s*%7D%7D/', $value));
    }
}
