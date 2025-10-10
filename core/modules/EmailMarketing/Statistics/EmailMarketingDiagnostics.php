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

namespace App\Module\EmailMarketing\Statistics;

use App\Authentication\LegacyHandler\UserHandler;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Languages\LegacyHandler\AppStringsHandler;
use App\Statistics\Entity\Statistic;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use App\SystemConfig\Service\SettingsProviderInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CampaignSettingsStatistic
 * @package App\Legacy\Statistics
 */
class EmailMarketingDiagnostics extends LegacyHandler implements StatisticsProviderInterface
{
    use StatisticsHandlingTrait;

    public const KEY = 'email-marketing-diagnostics';

    /**
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param SettingsProviderInterface $settingsProvider
     * @param SystemConfigHandler $configHandler
     * @param PreparedStatementHandler $preparedStatementHandler
     * @param RecordProviderInterface $recordProvider
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        protected SettingsProviderInterface $settingsProvider,
        protected SystemConfigHandler $configHandler,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected RecordProviderInterface $recordProvider,
        protected AppStringsHandler $appStringsHandler,
        protected UserHandler $userHandler,
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::KEY;
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
     * @throws Exception
     */
    public function getData(array $query): Statistic
    {
        [$module, $id] = $this->extractContext($query);

        if (empty($module) || empty($id)) {
            return $this->getEmptyResponse(self::KEY);
        }

        $params = $query['params'] ?? [];

        $this->init();
        $this->startLegacyApp();

        $result = [
            'fields' => []
        ];

        $result = $this->addSettingValues($params['settings'], $result);

        $result = $this->addJobIntervalValues($params['jobs'], $result);

        $result = $this->addBounceExists($result);

        $statistic = $this->buildSingleValueResponse(self::KEY, 'string', $result);

        $this->close();

        return $statistic;
    }

    protected function getSetting(string $setting, array $params, string $defaultKey, mixed $default, bool $hasConfigValue): string
    {
        $value = $this->settingsProvider->get('massemailer', $setting);

        if ($value === null || $value === '') {

            if ($hasConfigValue) {
                return $this->configHandler->getSystemConfig($defaultKey)?->getValue() ?? $default;
            }

            $value = $default;
        }

        if (($params['type'] ?? '') === 'bool') {
            return $this->mapBoolValue($value);
        }

        return $value;
    }

    protected function mapBoolValue(mixed $default): string
    {
        $strings = $this->appStringsHandler->getAppStrings($this->userHandler->getCurrentLanguage())->getItems();

        if (is_bool($default)) {
            $default = $default ? $strings['LBL_YES'] : $strings['LBL_NO'];
        }

        if (isTrue($default)) {
            $default = $strings['LBL_YES'];
        }

        if (isFalse($default)) {
            $default = $strings['LBL_NO'];
        }

        return $default;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    protected function getSchedulerBean(string $job): \SugarBean
    {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder->select('id')
                     ->from('schedulers')
                     ->where('job = :job')
                     ->setParameter('job', $job);

        $result = $queryBuilder->fetchAssociative();

        return \BeanFactory::getBean('Schedulers', $result['id']);
    }

    /**
     * @param $settings
     * @param array $result
     * @return array
     */
    protected function addSettingValues($settings, array $result): array
    {
        $settings = $settings ?? null;

        if ($settings === null) {
            return $result;
        }

        foreach ($settings as $setting) {
            $key = $setting['key'] ?? '';
            $defaultKey = $setting['defaultKey'] ?? '';
            $default = $setting['default'] ?? '';
            $hasConfig = $setting['hasConfig'] ?? true;
            $value = $this->getSetting($key, $setting, $defaultKey, $default, $hasConfig);

            $result['fields'][$key] = [
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * @param $jobs1
     * @param array $result
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    protected function addJobIntervalValues($jobs1, array $result): array
    {
        $jobs = $jobs1 ?? null;

        if ($jobs === null) {
            return $result;
        }

        foreach ($jobs as $job) {
            $this->init();
            $bean = $this->getSchedulerBean($job);

            $key = explode('::', $job);
            $key = $key[1];

            if ($bean->status === 'Inactive') {
                $this->close();
                $result['fields'][$key] = [
                    'value' => '',
                ];
                continue;
            }

            $bean->setIntervalHumanReadable();

            $this->close();

            $result['fields'][$key] = [
                'value' => $bean->intervalHumanReadable
            ];

        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    protected function addBounceExists(array $result): array
    {
        $recordModule = 'InboundEmail';

        $value = $this->getRecord($recordModule);

        $result['fields']['bounce_exists'] = [
            'value' => $value,
        ];

        return $result;
    }


    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getRecord(string $recordModule): string
    {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $table = $this->recordProvider->getTable($recordModule);

        $queryBuilder->select('id')
                     ->from($table, 'module')
                     ->where('module.type = :value')
                     ->andWhere('deleted = 0')
                     ->setParameter('value', 'bounce');

        $result = $queryBuilder->fetchAssociative();

        if ($result) {
            return 'true';
        }

        return 'false';
    }
}
