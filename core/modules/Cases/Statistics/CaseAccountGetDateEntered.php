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


namespace App\Module\Cases\Statistics;

use App\Statistics\Entity\Statistic;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Statistics\Service\StatisticsProviderInterface;
use App\Statistics\StatisticsHandlingTrait;
use BeanFactory;
use DateTime;
use Exception;

/**
 * Class CaseAccountGetDateEntered
 * @package App\Legacy\Statistics
 */
class CaseAccountGetDateEntered extends LegacyHandler implements StatisticsProviderInterface

{
    use StatisticsHandlingTrait;

    public const HANDLER_KEY = 'get-account-date-entered';
    public const KEY = 'get-account-date-entered';

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
     * @throws Exception
     */
    public function getData(array $query): Statistic
    {
        [$module, $id] = $this->extractContext($query);
        if (empty($module) || empty($id)) {
            return $this->getEmptyResponse(self::KEY);
        }
        $this->init();
        $this->startLegacyApp();
        $accountId = $this->getAccountId($id);
        $module = 'Accounts';
        $moduleDateEntered = $this->getAccountDateEntered($accountId, $module);
        $date = (new DateTime($moduleDateEntered))->format("Y");
        $statistic = $this->buildSingleValueResponse(self::KEY, 'varchar', ["value" => $date]);
        $this->close();

        return $statistic;
    }

    /**
     * @param string $id
     * @return string
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    protected function getAccountId(string $id): string
    {
        $case = BeanFactory::getBean('Cases', $id);
        if (!$case->account_id) {
            return $this->getEmptyResponse(self::KEY);
        }

        return $case->account_id;
    }

    /**
     * @param string $accountId
     * @param string $module
     * @return string
     */
    protected function getAccountDateEntered(string $accountId, string $module): string
    {
        return BeanFactory::getBean($module, $accountId)->date_entered;
    }
}
