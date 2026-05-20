<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Module\Contacts\Statistics;

use App\Statistics\DateTimeStatisticsHandlingTrait;
use App\Statistics\Entity\Statistic;
use App\Data\LegacyHandler\PresetDataHandlers\SubpanelDataQueryHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\Service\StatisticsProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContactLastTouchPoint
 * @package App\Legacy\Statistics
 */
class ContactLastTouchPoint extends SubpanelDataQueryHandler implements StatisticsProviderInterface
{
    use DateTimeStatisticsHandlingTrait;

    public const HANDLER_KEY = 'contact-last-touchpoint';
    public const KEY = 'contact-last-touchpoint';

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * ListDataHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $moduleNameMapper,
            $session
        );
        $this->moduleNameMapper = $moduleNameMapper;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @inheritDoc
     */
    public function getData(array $query): Statistic
    {
        [$module, $id] = $this->extractContext($query);
        $subpanel = 'history';
        if (empty($module) || empty($id)) {
            return $this->buildNoInteractionResponse();
        }
        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);
        if ($legacyModuleName !== 'Contacts') {
            return $this->buildNoInteractionResponse();
        }
        $this->init();
        $this->startLegacyApp();
        $queries = $this->getQueries($module, $id, $subpanel);


        $parts = $queries[2];
        $parts['select'] = 'SELECT meetings.`date_end` AS `meetings_date_end`';
        $parts['order_by'] = ' ORDER BY `meetings_date_end` DESC LIMIT 1';
        $innerQuery = $this->joinQueryParts($parts);
        $meetingsResult = $this->fetchRow($innerQuery);

        $parts = $queries[3];
        $parts['select'] = 'SELECT calls.`date_end` ';
        $parts['order_by'] = ' ORDER BY calls.`date_end` DESC LIMIT 1';
        $innerQuery = $this->joinQueryParts($parts);
        $callsResult = $this->fetchRow($innerQuery);


        $parts = $queries[5];
        $parts['select'] = 'SELECT  emails.`date_sent_received` ';
        $parts['order_by'] = ' ORDER BY  emails.`date_sent_received` DESC LIMIT 1';
        $innerQuery = $this->joinQueryParts($parts);
        $emailsResult1 = $this->fetchRow($innerQuery);

        $parts = $queries[6];
        $parts['select'] = 'SELECT  emails.`date_sent_received` as `emails_date_sent` ';
        $parts['order_by'] = ' ORDER BY  `emails_date_sent` DESC LIMIT 1';
        $innerQuery = $this->joinQueryParts($parts);
        $emailsResult2 = $this->fetchRow($innerQuery);

        $date = [];
        $positions = [];
        $i = 0;

        if (!empty($meetingsResult['meetings_date_end'])) {
            $date[$i] = $meetingsResult['meetings_date_end'];
            $positions[$date[$i]] = 'meetings_date_end';
            $i++;
        }
        if (!empty($callsResult['date_end'])) {
            $date[$i] = $callsResult['date_end'];
            $positions[$date[$i]] = 'date_end';
            $i++;
        }
        if (!empty($emailsResult1['date_sent_received'])) {
            $date[$i] = $emailsResult1['date_sent_received'];
            $positions[$date[$i]] = 'date_sent_received';
            $i++;
        }
        if (!empty($emailsResult2['emails_date_sent'])) {
            $date[$i] = $emailsResult2['emails_date_sent'];
            $positions[$date[$i]] = 'emails_date_sent';
        }
        if (empty($date)) {
            return $this->buildNoInteractionResponse();
        }
        $max = max($date);

        if ('meetings_date_end' === $positions[$max]) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'datetime', ["value" => $max]);
            $this->addMetadata($statistic, ['labelKey' => 'LBL_LAST_MEETING']);
        } elseif ('date_end' === $positions[$max]) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'datetime', ["value" => $max]);
            $this->addMetadata($statistic, ['labelKey' => 'LBL_LAST_CALL']);
        } elseif ('date_sent_received' === $positions[$max]) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'datetime', ["value" => $max]);
            $this->addMetadata($statistic, ['labelKey' => 'LBL_LAST_EMAIL']);
        } elseif ('emails_date_sent' === $positions[$max]) {
            $statistic = $this->buildSingleValueResponse(self::KEY, 'datetime', ["value" => $max]);
            $this->addMetadata($statistic, ['labelKey' => 'LBL_LAST_EMAIL']);
        } else {
            return $this->buildNoInteractionResponse();
        }

        $this->close();

        return $statistic;
    }

    public function buildNoInteractionResponse(): Statistic {
        $statistic = $this->getEmptyResponse(self::KEY);
        $this->addMetadata($statistic, ['labelKey' => 'LBL_NO_INTERACTION']);
        return $statistic;
    }

}
