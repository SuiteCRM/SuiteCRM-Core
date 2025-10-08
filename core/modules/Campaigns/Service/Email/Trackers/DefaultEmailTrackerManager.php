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

namespace App\Module\Campaigns\Service\Email\Trackers;

use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use App\SystemConfig\Service\SettingsProviderInterface;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailTrackerManager implements EmailTrackerManagerInterface
{

    public function __construct(
        protected SettingsProviderInterface $settingsProvider,
        protected SystemConfigProviderInterface $systemConfigHandler,
        protected RecordProviderInterface $recordProvider,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
    }

    public function addViewedTracker(string $trackerId, string $emailBody, array $context = []): string
    {

        $url = $this->getTrackingUrl() . "index.php?entryPoint=image&identifier=$trackerId";
        $trackerTag = "<br /><img alt='' height='1' width='1' src='$url'/>";
        $emailBody .= $trackerTag;

        $this->logger->debug(
            'Campaigns:DefaultEmailTrackerManager::addViewedTracker - Add viewed tracker - id: ' . $trackerId, [
                'campaignLogId' => $trackerId,
                'trackerUrl' => $url,
                'trackerTag' => $trackerTag,
                'emailBody' => $emailBody,
            ]
        );

        return $emailBody;
    }

    public function getTrackingUrl(): string
    {

        $trackerLocationType = $this->settingsProvider->get('massemailer', 'tracking_entities_location_type');
        $trackerLocation = $this->settingsProvider->get('massemailer', 'tracking_entities_location');

        if ($trackerLocationType === '2' && !empty($trackerLocation)) {
            $trackingUrl = $trackerLocation;
        } else {
            $trackingUrl = ($this->systemConfigHandler->getSystemConfig('site_url')?->getValue() ?? '');
        }

        //make sure tracking url ends with '/' character
        $strLen = strlen((string)$trackingUrl);
        if ($trackingUrl[$strLen - 1] !== '/') {
            $trackingUrl .= '/';
        }

        return $trackingUrl;
    }

    public function isTrackingEnabled(): bool
    {
        $trackersEnabled = $this->settingsProvider->get('massemailer', 'trackers_enabled');
        $this->logger->debug(
            'Campaigns:DefaultEmailTrackerManager::isTrackingEnabled - Check if tracking is enabled', [
                'trackersEnabled' => $trackersEnabled,
            ]
        );

        if (isTrue($trackersEnabled)) {
            return true;
        }

        return false;
    }

    public function addTrackerLinks(string $trackerId, string $emailBody, array $context = []): string
    {
        $trackingUrlTemplate = $this->getTrackingUrl() . "index.php?entryPoint=campaign_trackerv2&track=%s&identifier=$trackerId";

        /** @var Record $campaign */
        $campaign = $context['campaignRecord'] ?? null;
        $campaignId = $campaign?->getId() ?? '';

        if (empty($campaignId)) {
            $this->logger->debug(
                'Campaigns:DefaultEmailTrackerManager::addTrackerLinks - No campaign in context stopping ', [
                    'campaignId' => $campaignId,
                    'emailBody' => $emailBody,
                    'campaignAttributes' => $campaign?->getAttributes(),
                ]
            );
            return $emailBody;
        }

        $this->logger->debug(
            'Campaigns:DefaultEmailTrackerManager::addTrackerLinks - preg_match result', [
                'matches' => preg_match('/<a[^>]*href=\\?["\']([^\\"\']+)\\?["\'][^>]*>/i', $emailBody),
                'emailBody' => $emailBody,
                'campaignId' => $campaignId
            ]
        );

        // Use preg_replace_callback to find and replace all href attributes in <a> tags
        $emailBody = preg_replace_callback(
            '/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/i',
            function ($matches) use ($trackingUrlTemplate, $campaignId, $trackerId) {
                $originalUrl = $matches[1];

                $this->logger->debug(
                    'Campaigns:DefaultEmailTrackerManager::addTrackerLinks - Found href - ' . $originalUrl . ' | $campaignId id: ' . $campaignId, [
                        'campaignId' => $campaignId,
                        'originalUrl' => $originalUrl,
                    ]
                );

                if (empty($originalUrl) || $this->containsUnsubscribeLinkVariable($originalUrl)) {
                    return $matches[0];
                }

                $trackerUrl = $originalUrl;
                if (!str_contains($trackerUrl, 'http://') && !str_contains($trackerUrl, 'https://')) {
                    $trackerUrl = 'http://' . $trackerUrl;
                }


                $trackerLink = $this->getTracker($campaignId, $trackerUrl, $originalUrl, $trackerId);
                if (empty($trackerLink)) {
                    $trackerLink = $this->addTracker($campaignId, $trackerUrl);
                }

                $uniqueTrackerUrl = '';

                if (isTrue($trackerLink['is_optout'])){
                    $replaced = str_replace($originalUrl, $trackerLink['tracker_url'], $matches[0]);
                } else {
                    $uniqueTrackerUrl = sprintf($trackingUrlTemplate, $trackerLink['id']);
                    $replaced = str_replace($originalUrl, $uniqueTrackerUrl, $matches[0]);
                }

                $this->logger->debug(
                    'Campaigns:DefaultEmailTrackerManager::addTrackerLinks - Add tracker link - id: ' . $trackerLink['id'], [
                        'trackerLinkId' => $trackerLink['id'],
                        'campaignId' => $campaignId,
                        'originalUrl' => $originalUrl,
                        'trackerUrl' => !empty($uniqueTrackerUrl) ? $uniqueTrackerUrl : $trackerLink['tracker_url'],
                        'replaced' => $replaced,
                    ]
                );

                return $replaced;
            },
            $emailBody
        );

        return $emailBody;
    }

    /**
     * @throws \Exception
     */
    public function addSurveyLink(string $surveyId, string $trackerId, string $emailBody, string $contactId = '', array $context = []): string
    {
        $trackingUrlTemplate = $this->getTrackingUrl() . "index.php?entryPoint=survey&id=%s&contact=%s&tracker=" . ($trackerId ?? '');

        /** @var Record $campaign */
        $campaign = $context['campaignRecord'] ?? null;
        $campaignId = $campaign?->getId() ?? '';

        if (empty($campaignId) || empty($trackerId)) {
            $this->logger->debug(
                'Campaigns:DefaultEmailTrackerManager::addSurveyLink - Missing required inputs ', [
                    'surveyId' => $surveyId,
                    'campaignId' => $campaignId,
                    'trackerId' => $trackerId,
                    'contactId' => $contactId,
                    'emailBody' => $emailBody,
                    'campaignAttributes' => $campaign?->getAttributes(),
                ]
            );
            return $emailBody;
        }

        $survey = $this->recordProvider->getRecord('Surveys', $surveyId);
        $status = $survey->getAttributes()['status'] ?? '';

        if (!empty($status) && $status !== 'Public') {
            return $emailBody;
        }

        $uniqueTrackerUrl = sprintf($trackingUrlTemplate, $surveyId ?? '', $contactId ?? '');

        $replaced = $this->replaceVariables($uniqueTrackerUrl, $emailBody);

        $replaced = preg_replace(
            '/{{\s*survey_link\s*}}/',
            $uniqueTrackerUrl,
            $replaced
        );

        $replaced = preg_replace(
            '/%7B%7B\s*survey_link\s*%7D%7D/',
            $uniqueTrackerUrl,
            $replaced
        );

        $this->logger->debug(
            'Campaigns:DefaultEmailTrackerManager::addSurveyLink - Added survey link: ', [
                'surveyId' => $surveyId,
                'campaignId' => $campaignId,
                'trackerId' => $trackerId,
                'contactId' => $contactId,
                'emailBody' => $emailBody,
                'replaced' => $replaced,
                'campaignAttributes' => $campaign?->getAttributes(),
            ]
        );

        return $replaced;
    }

    public function getTracker(string $campaignId, string $url, string $originalUrl, string $trackerId): ?array
    {
        $trackerLink = [];

        $decodedUrlKey = $this->decode($originalUrl);

        try {
            $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
            $trackerLink = $queryBuilder
                ->select('*')
                ->from('campaign_trkrs', 'trk')
                ->where('trk.deleted = 0')
                ->andWhere("trk.campaign_id = :campaignId")
                ->andWhere("trk.tracker_url = :url")
                ->orWhere("trk.tracker_name = :decoded")
                ->setParameter('campaignId', $campaignId)
                ->setParameter('url', $url)
                ->setParameter('decoded', $decodedUrlKey)
                ->fetchAssociative();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailTrackerManager::getTrackers | Error tracker link record for campaign %s, url %s, from table "%s": %s',
                    $campaignId,
                    $url,
                    'campaign_trkrs',
                    $e->getMessage()
                ),
                ['exception' => $e]
            );

            return $trackerLink;
        }

        if (empty($trackerLink)) {
            return [];
        }

        if (isTrue($trackerLink['is_optout'])){
            $trackerLink['tracker_url'] = $this->getTrackingUrl() . "index.php?entryPoint=removeme&identifier=$trackerId";
        }

        return $trackerLink;
    }

    protected function getTable(): string
    {
        return $this->recordProvider->getTable('email-marketing');
    }

    protected function addTracker(string $campaignId, string $url): array
    {

        $record = new Record();
        $record->setModule('campaign-trackers');
        $attributes = [
            'tracker_name' => $url,
            'tracker_url' => $url,
            'campaign_id' => $campaignId,
            'is_optout' => 0,
        ];

        $record->setAttributes(
            $attributes
        );

        $savedRecord = $this->recordProvider->saveRecord($record);
        $this->logger->debug(
            'Campaigns:DefaultEmailTrackerManager::addTracker - Save new tracker - id: ' . $savedRecord->getId(), [
                'campaignLogId' => $savedRecord->getId(),
                'tracker_name' => $url,
                'tracker_url' => $url,
                'campaign_id' => $campaignId,
            ]
        );

        return $savedRecord->getAttributes() ?? [];
    }

    /**
     * @param string $value
     * @return bool
     */
    protected function containsUnsubscribeLinkVariable(string $value): bool
    {
        return (bool)(preg_match('/{{\s*unsubscribe_link\s*}}/', $value) || preg_match('/%7B%7B\s*unsubscribe_link\s*%7D%7D/', $value));
    }

    protected function replaceVariables(string $uniqueTrackerUrl, string $emailBody): string
    {
        $variables = ['$surveys_survey_url_display', '$survey_url_display', 'survey_url_display'];
        $search = '/(<a\b[^>]*?\btitle=)(["\'])(.*?)\2/i';

        $replaced = $this->replace($search, $variables, $uniqueTrackerUrl, $emailBody);

        $search = '/(<a\b[^>]*?\bhref=)(["\'])(.*?)\2/i';

        $text = $this->replace($search, $variables, $uniqueTrackerUrl, $replaced);

        $linkUrl = "<a title=$uniqueTrackerUrl href=$uniqueTrackerUrl>$uniqueTrackerUrl</a>";

        return str_replace(['$surveys_survey_url_display', '$survey_url_display', 'survey_url_display'],
            [$linkUrl, $linkUrl, $linkUrl],
            $text);
    }

    /**
     * @param string $search
     * @param array $variables
     * @param string $uniqueTrackerUrl
     * @param string $replaced
     * @return string
     */
    protected function replace(string $search, array $variables, string $uniqueTrackerUrl, string $replaced): string
    {
        return preg_replace_callback($search, static function ($matches) use ($variables, $uniqueTrackerUrl) {
            $title = $matches[3];

            foreach ($variables as $variable) {
                if (str_contains($title, $variable)) {
                    $title = str_replace($variable, $uniqueTrackerUrl, $title);
                }
            }

            return $matches[1] . $matches[2] . $title . $matches[2];
        }, $replaced);
    }

    protected function decode(string $originalUrl): string
    {
        $decoded = urldecode($originalUrl);
        return str_replace(['{', '}'], ['', ''], $decoded);
    }
}
