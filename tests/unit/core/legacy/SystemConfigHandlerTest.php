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


namespace App\Tests\unit\core\legacy;

use ApiPlatform\Exception\ItemNotFoundException;
use App\Engine\LegacyHandler\ActionNameMapperHandler;
use App\Routes\LegacyHandler\ClassicViewRoutingExclusionsHandler;
use App\Currency\LegacyHandler\CurrencyHandler;
use App\DateTime\LegacyHandler\DateTimeHandler;
use App\Module\LegacyHandler\ModuleNameMapperHandler;
use App\SystemConfig\LegacyHandler\DateFormatConfigMapper;
use App\SystemConfig\LegacyHandler\DefaultCurrencyConfigMapper;
use App\SystemConfig\LegacyHandler\SystemConfigMappers;
use App\SystemConfig\LegacyHandler\TimeFormatConfigMapper;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use App\Tests\UnitTester;
use Codeception\Test\Unit;
use Exception;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class SystemConfigHandlerTest
 * @package App\Tests\unit\core\legacy
 */
class SystemConfigHandlerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var SystemConfigHandler
     */
    protected $handler;

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

        $exposedSystemConfigs = [
            'default_language' => true,
            'passwordsetting' => [
                'forgotpasswordON' => true
            ],
            'search' => [
                'controller' => true,
                'pagination' => [
                    'min' => true,
                ]
            ],
            'languages' => true,
            'module_name_map' => true,
            'action_name_map' => true,
            'datef' => true,
            'timef' => true,
            'currency' => true,
            'cache_reset_actions' => true,
            'module_routing' => true,
            'recordview_actions_limits' => true
        ];

        $moduleMapper = new ModuleNameMapperHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $session
        );

        $actionMapper = new ActionNameMapperHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $session
        );

        $classicViewExclusionHandler = new ClassicViewRoutingExclusionsHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $session
        );

        $dateTimeHandler = new DateTimeHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $this->tester->getDatetimeFormatMap(),
            $session
        );

        $currencyHandler = new CurrencyHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $session
        );

        $mappersArray = [
            'datef' => new DateFormatConfigMapper($dateTimeHandler),
            'timef' => new TimeFormatConfigMapper($dateTimeHandler),
            'currency' => new DefaultCurrencyConfigMapper($currencyHandler)
        ];

        /** @var SystemConfigMappers $mappers */
        $mappers = $this->make(
            SystemConfigMappers::class,
            [
                'get' => static function (string $key) use ($mappersArray) {
                    return $mappersArray[$key] ?? null;
                },
                'hasMapper' => static function (string $key) use ($mappersArray) {
                    if (isset($mappersArray[$key])) {
                        return true;
                    }

                    return false;
                },
            ]
        );
        $systemConfigKeyMap = [
            'datef' => 'date_format',
            'timef' => 'time_format'
        ];

        global $sugar_config;
        $sugar_config['datef'] = 'm/d/Y';
        $sugar_config['timef'] = 'H:i';

        $cacheResetActions = [
            'users' => [
                'edit'
            ]
        ];

        $navigationTabLimits = [
            'XSmall' => 4,
            'Small' => 4,
            'Medium' => 6,
            'Large' => 10,
            'XLarge' => 12
        ];

        $listViewColumnLimits = [
            'without_sidebar' => [
                'XSmall' => 4,
                'Small' => 4,
                'Medium' => 6,
                'Large' => 10,
                'XLarge' => 12
            ],
            'with_sidebar' => [
                'XSmall' => 4,
                'Small' => 4,
                'Medium' => 6,
                'Large' => 10,
                'XLarge' => 12
            ]
        ];

        $listViewSettingsLimits = [
            'XSmall' => 1,
            'Small' => 2,
            'Medium' => 3,
            'Large' => 3,
            'XLarge' => 4
        ];

        $listViewActionsLimits = [
            'XSmall' => 1,
            'Small' => 2,
            'Medium' => 5,
            'Large' => 5,
            'XLarge' => 14
        ];

        $recordViewActionsLimits = [
            'XSmall' => 3,
            'Small' => 3,
            'Medium' => 3,
            'Large' => 3,
            'XLarge' => 3
        ];

        $moduleRouting = [
            'saved-search' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'calls-reschedule' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'calls' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'tasks' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'meetings' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'notes' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'leads' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'contacts' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'accounts' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'opportunities' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'email-templates' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'campaigns' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'prospects' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'prospect-lists' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'documents' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'cases' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'project' => [
                'index' => true,
                'list' => true,
                'record' => false
            ],
            'project-task' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'bugs' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'business-hours' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'roles' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'users' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'employees' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'template-section-line' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'surveys' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'survey-responses' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'survey-question-responses' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'survey-questions' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'survey-question-options' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'project-templates' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'task-templates' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'knowledge-base-categories' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'knowledge-base' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'events' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'event-locations' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'contracts' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'invoices' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'pdf-templates' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'product-categories' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'products' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'quotes' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'products-quotes' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'maps' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'markers' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'areas' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'address-cache' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'case-events' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'case-updates' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'reports' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'scheduled-reports' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'workflow' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'workflow-processed' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'security-groups' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'acl-roles' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'schedulers' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'oauth-keys' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],
            'outbound-email-accounts' => [
                'index' => true,
                'list' => true,
                'record' => true
            ],

        ];

        $ui = [
            'alert_timeout' => 3
        ];

        $this->handler = new SystemConfigHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $exposedSystemConfigs,
            $actionMapper,
            $moduleMapper,
            $classicViewExclusionHandler,
            $mappers,
            $systemConfigKeyMap,
            $cacheResetActions,
            $moduleRouting,
            $navigationTabLimits,
            $listViewColumnLimits,
            $listViewSettingsLimits,
            $listViewActionsLimits,
            $recordViewActionsLimits,
            $ui,
            $session
        );
    }

    // tests

    /**
     * Test empty config key handling in SystemConfigHandler
     */
    public function testEmptySystemConfigKeyCheck(): void
    {
        $emptyConfig = $this->handler->getSystemConfig('');
        static::assertNull($emptyConfig);
    }

    /**
     * Test invalid config key handling in SystemConfigHandler
     */
    public function testInvalidExposedSystemConfigCheck(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->handler->getSystemConfig('dbconfig');
    }

    /**
     * Test retrieval of first level system config in SystemConfigHandler
     */
    public function testGetValidOneLevelSystemConfig(): void
    {
        $defaultLanguage = $this->handler->getSystemConfig('default_language');
        static::assertNotNull($defaultLanguage);
        static::assertEquals('default_language', $defaultLanguage->getId());
        static::assertEquals('en_us', $defaultLanguage->getValue());
        static::assertEmpty($defaultLanguage->getItems());
    }

    /**
     * Test retrieval of second level deep system config in SystemConfigHandler
     */
    public function testGetValidTwoLevelSystemConfig(): void
    {
        $passwordSetting = $this->handler->getSystemConfig('passwordsetting');
        static::assertNotNull($passwordSetting);
        static::assertEquals('passwordsetting', $passwordSetting->getId());
        static::assertNull($passwordSetting->getValue());
        static::assertIsArray($passwordSetting->getItems());

        static::assertArrayHasKey('forgotpasswordON', $passwordSetting->getItems());
        static::assertIsNotArray($passwordSetting->getItems()['forgotpasswordON']);
    }

    /**
     * Test retrieval of third level deep system config in SystemConfigHandler
     */
    public function testGetValidThreeLevelSystemConfig(): void
    {
        $searchConfig = $this->handler->getSystemConfig('search');
        static::assertNotNull($searchConfig);
        static::assertEquals('search', $searchConfig->getId());
        static::assertNull($searchConfig->getValue());
        static::assertIsArray($searchConfig->getItems());

        static::assertArrayHasKey('controller', $searchConfig->getItems());
        static::assertArrayHasKey('pagination', $searchConfig->getItems());
        static::assertIsArray($searchConfig->getItems()['pagination']);
        static::assertArrayHasKey('min', $searchConfig->getItems()['pagination']);
    }

    /**
     * Test injected module name map config
     */
    public function testInjectedModuleNameMapConfig(): void
    {
        $moduleNameMap = $this->handler->getSystemConfig('module_name_map');
        static::assertNotNull($moduleNameMap);
        static::assertEquals('module_name_map', $moduleNameMap->getId());
        static::assertNull($moduleNameMap->getValue());
        static::assertIsArray($moduleNameMap->getItems());

        static::assertArrayHasKey('Contacts', $moduleNameMap->getItems());
    }

    /**
     * Test injected action name map config
     */
    public function testInjectedActionNameMapConfig(): void
    {
        $actionNameMap = $this->handler->getSystemConfig('action_name_map');
        static::assertNotNull($actionNameMap);
        static::assertEquals('action_name_map', $actionNameMap->getId());
        static::assertNull($actionNameMap->getValue());
        static::assertIsArray($actionNameMap->getItems());

        static::assertArrayHasKey('DetailView', $actionNameMap->getItems());
    }

    /**
     * Test date format config transformation
     */
    public function testDateFormatMapping(): void
    {
        $format = $this->handler->getSystemConfig('datef');
        static::assertNotNull($format);
        static::assertEquals('date_format', $format->getId());
        static::assertNotNull($format->getValue());
        static::assertIsArray($format->getItems());
        static::assertEmpty($format->getItems());

        static::assertEquals('MM/dd/yyyy', $format->getValue());
    }

    /**
     * Test time format config transformation
     */
    public function testTimeFormatMapping(): void
    {
        $format = $this->handler->getSystemConfig('timef');
        static::assertNotNull($format);
        static::assertEquals('time_format', $format->getId());
        static::assertNotNull($format->getValue());
        static::assertIsArray($format->getItems());
        static::assertEmpty($format->getItems());

        static::assertEquals('HH:mm', $format->getValue());
    }

    /**
     * Test default currency mapping
     */
    public function testDefaultCurrencyMapping(): void
    {
        $currencyConfig = $this->handler->getSystemConfig('currency');
        static::assertNotNull($currencyConfig);
        static::assertEquals('currency', $currencyConfig->getId());
        static::assertNull($currencyConfig->getValue());
        static::assertIsArray($currencyConfig->getItems());
        static::assertNotEmpty($currencyConfig->getItems());

        $currency = $currencyConfig->getItems();
        static::assertNotNull($currency);
        static::assertNotEmpty($currency);

        static::assertArrayHasKey('id', $currency);
        static::assertNotEmpty($currency['id']);
        static::assertEquals($currency['id'], -99);
        static::assertArrayHasKey('name', $currency);
        static::assertNotEmpty($currency['name']);
        static::assertArrayHasKey('symbol', $currency);
        static::assertNotEmpty($currency['symbol']);
        static::assertArrayHasKey('iso4217', $currency);
        static::assertNotEmpty($currency['iso4217']);
    }

    /**
     * Test cache reset actions
     */
    public function testCacheResetActionsConfig(): void
    {
        $cacheClearActionsConfig = $this->handler->getSystemConfig('cache_reset_actions');
        static::assertNotNull($cacheClearActionsConfig);
        static::assertEquals('cache_reset_actions', $cacheClearActionsConfig->getId());
        static::assertNull($cacheClearActionsConfig->getValue());
        static::assertIsArray($cacheClearActionsConfig->getItems());
        static::assertNotEmpty($cacheClearActionsConfig->getItems());

        $cacheClearActions = $cacheClearActionsConfig->getItems();
        static::assertNotNull($cacheClearActions);
        static::assertNotEmpty($cacheClearActions);

        static::assertArrayHasKey('users', $cacheClearActions);
        static::assertNotEmpty($cacheClearActions['users']);
        static::assertContains('edit', $cacheClearActions['users']);
    }

    /**
     * Test cache reset actions
     */
    public function testRecordViewActionsLimitsConfig(): void
    {
        $limitsConfig = $this->handler->getSystemConfig('recordview_actions_limits');
        static::assertNotNull($limitsConfig);
        static::assertEquals('recordview_actions_limits', $limitsConfig->getId());
        static::assertNull($limitsConfig->getValue());
        static::assertIsArray($limitsConfig->getItems());
        static::assertNotEmpty($limitsConfig->getItems());

        $limits = $limitsConfig->getItems();
        static::assertNotNull($limits);
        static::assertNotEmpty($limits);
        static::assertEquals(
            [
                'XSmall' => 3,
                'Small' => 3,
                'Medium' => 3,
                'Large' => 3,
                'XLarge' => 3
            ],
            $limits
        );
    }
}
