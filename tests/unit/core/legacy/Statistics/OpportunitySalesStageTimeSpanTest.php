<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2021 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */


namespace App\Tests\unit\core\legacy\Statistics;

use App\Module\LegacyHandler\ModuleNameMapperHandler;
use App\Tests\UnitTester;
use BeanFactory;
use Codeception\Test\Unit;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use App\Tests\_mock\Mock\core\legacy\Statistics\OpportunitySalesStageTimeSpanMock;
use App\Tests\_mock\Helpers\core\legacy\Data\DBQueryResultsMocking;
use Opportunity;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class OpportunitySalesStageTimeSpanTest
 * @package App\Tests\unit\core\legacy\Statistics
 */
class OpportunitySalesStageTimeSpanTest extends Unit
{
    use DBQueryResultsMocking;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var OpportunitySalesStageTimeSpanMock
     */
    private $handler;

    /**
     * @throws Exception
     */
    protected function _before(): void
    {
        $session = new Session(new MockArraySessionStorage('PHPSESSID'));
        $session->start();

        $projectDir = $this->tester->getProjectDir();
        $legacyDir = $this->tester->getLegacyDir();
        $legacySessionName = $this->tester->getLegacySessionName();
        $defaultSessionName = $this->tester->getDefaultSessionName();

        $legacyScope = $this->tester->getLegacyScope();

        $moduleNameMapper = new ModuleNameMapperHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $session
        );

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->makeEmpty(
            EntityManagerInterface::class
        );

        $this->handler = new OpportunitySalesStageTimeSpanMock(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $moduleNameMapper,
            $entityManager,
            $session
        );
    }

    /**
     * Test Unsupported context module
     * @throws Exception
     */
    public function testUnsupportedContextModule(): void
    {
        $this->reset();
        $dateString = $this->getPastDateString(2) . ' 12:00:00';
        $opportunity = $this->buildOpportunity($dateString);

        $this->handler->setOpportunity($opportunity);

        $result = $this->handler->getData([
            'context' => [
                'module' => 'accounts',
                'id' => '12345',
            ]
        ]);

        static::assertNotNull($result);
        static::assertNotNull($result->getData());
        static::assertNotNull($result->getMetadata());
        static::assertIsArray($result->getData());
        static::assertIsArray($result->getMetadata());
        static::assertEquals('opportunity-sales-stage-time-span', $result->getId());
        static::assertArrayHasKey('type', $result->getMetadata());
        static::assertEquals('single-value-statistic', $result->getMetadata()['type']);
        static::assertArrayHasKey('dataType', $result->getMetadata());
        static::assertEquals('varchar', $result->getMetadata()['dataType']);
        static::assertArrayHasKey('value', $result->getData());
        static::assertEquals('-', $result->getData()['value']);
    }

    /**
     * Test Closed Status Calculation
     * @throws Exception
     */
    public function testClosedWonStatusCalculation(): void
    {
        $this->reset();

        $closedDateString = $this->getPastDateString(2) . ' 19:00:00';
        $rows = [
            [
                'after_value_string' => 'Closed Won',
                'first_update' => $closedDateString,
                'last_update' => $closedDateString,
            ],
        ];
        $this->handler->setMockQueryResult($rows);

        $dateString = $this->getPastDateString(4) . ' 16:00:00';

        $opportunity = $this->buildOpportunity($dateString);
        $opportunity->sales_stage = 'Closed Won';

        $this->handler->setOpportunity($opportunity);

        $result = $this->handler->getData([
            'context' => [
                'module' => 'opportunities',
                'id' => '12345',
            ]
        ]);

        static::assertNotNull($result);
        static::assertNotNull($result->getData());
        static::assertNotNull($result->getMetadata());
        static::assertIsArray($result->getData());
        static::assertIsArray($result->getMetadata());
        static::assertArrayHasKey('value', $result->getData());
        static::assertEquals('2', $result->getData()['value']);
        static::assertEquals('opportunity-sales-stage-time-span', $result->getId());
        static::assertArrayHasKey('type', $result->getMetadata());
        static::assertEquals('single-value-statistic', $result->getMetadata()['type']);
        static::assertArrayHasKey('dataType', $result->getMetadata());
        static::assertEquals('int', $result->getMetadata()['dataType']);
        static::assertArrayHasKey('labelKey', $result->getMetadata());
        static::assertEquals('LBL_DAYS_OPEN_FOR', $result->getMetadata()['labelKey']);

    }

    /**
     * Test Closed Status Calculation
     * @throws Exception
     */
    public function testClosedLostStatusCalculation(): void
    {
        $this->reset();
        $closedDateString = $this->getPastDateString(2) . ' 19:00:00';
        $rows = [
            [
                'after_value_string' => 'Closed Lost',
                'first_update' => $closedDateString,
                'last_update' => $closedDateString,
            ],
        ];
        $this->handler->setMockQueryResult($rows);

        $dateString = $this->getPastDateString(4) . ' 16:00:00';

        $opportunity = $this->buildOpportunity($dateString);
        $opportunity->sales_stage = 'Closed Lost';

        $this->handler->setOpportunity($opportunity);

        $result = $this->handler->getData([
            'context' => [
                'module' => 'opportunities',
                'id' => '12345',
            ]
        ]);

        static::assertNotNull($result);
        static::assertNotNull($result->getData());
        static::assertNotNull($result->getMetadata());
        static::assertIsArray($result->getData());
        static::assertIsArray($result->getMetadata());
        static::assertArrayHasKey('value', $result->getData());
        static::assertEquals('2', $result->getData()['value']);
        static::assertEquals('opportunity-sales-stage-time-span', $result->getId());
        static::assertArrayHasKey('type', $result->getMetadata());
        static::assertEquals('single-value-statistic', $result->getMetadata()['type']);
        static::assertArrayHasKey('dataType', $result->getMetadata());
        static::assertEquals('int', $result->getMetadata()['dataType']);
        static::assertArrayHasKey('labelKey', $result->getMetadata());
        static::assertEquals('LBL_DAYS_OPEN_FOR', $result->getMetadata()['labelKey']);

    }

    /**
     * Test Sales Stage Audit Calculation
     * @throws Exception
     */
    public function testSalesStageAuditCalculation(): void
    {
        $qualificationChangeDateString = $this->getPastDateString(3) . ' 09:00:00';

        $rows = [
            [
                'after_value_string' => 'Qualification',
                'first_update' => $qualificationChangeDateString,
                'last_update' => $qualificationChangeDateString,
            ],
        ];

        $this->handler->setMockQueryResult($rows);

        $dateString = $this->getPastDateString(5) . ' 16:00:00';

        $opportunity = $this->buildOpportunity($dateString);
        $opportunity->sales_stage = 'Qualification';

        $this->handler->setOpportunity($opportunity);

        $result = $this->handler->getData([
            'context' => [
                'module' => 'opportunities',
                'id' => '12345',
            ]
        ]);

        static::assertNotNull($result);
        static::assertNotNull($result->getData());
        static::assertNotNull($result->getMetadata());
        static::assertIsArray($result->getData());
        static::assertIsArray($result->getMetadata());
        static::assertArrayHasKey('value', $result->getData());
        static::assertEquals('3', $result->getData()['value']);
        static::assertEquals('opportunity-sales-stage-time-span', $result->getId());
        static::assertArrayHasKey('type', $result->getMetadata());
        static::assertEquals('single-value-statistic', $result->getMetadata()['type']);
        static::assertArrayHasKey('dataType', $result->getMetadata());
        static::assertEquals('int', $result->getMetadata()['dataType']);
        static::assertArrayHasKey('labelKey', $result->getMetadata());
        static::assertEquals('LBL_DAYS_IN_SALE_STAGE', $result->getMetadata()['endLabelKey']);

    }

    /**
     * @param int $days
     * @return string
     * @throws Exception
     */
    protected function getPastDateString(int $days): string
    {
        $now = new DateTime();
        $now->sub(new DateInterval('P' . $days . 'D'));

        return $now->format('Y-m-d');
    }

    /**
     * @param string $dateString
     * @return Opportunity
     */
    protected function buildOpportunity(string $dateString): Opportunity
    {
        /** @var Opportunity $opportunity */
        $opportunity = BeanFactory::newBean('Opportunities');
        $opportunity->date_entered = $dateString;
        $opportunity->id = '12355';

        return $opportunity;
    }
}
