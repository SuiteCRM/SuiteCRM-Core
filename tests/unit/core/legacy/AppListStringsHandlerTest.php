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
use App\Languages\Entity\AppListStrings;
use App\Languages\LegacyHandler\AppListStringsHandler;
use App\Tests\UnitTester;
use Codeception\Test\Unit;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class AppListStringsHandlerTest
 * @package App\Tests\unit\core\legacy
 */
class AppListStringsHandlerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var AppListStringsHandler
     */
    protected $handler;

    protected function _before(): void
    {
        $session = new Session(new MockArraySessionStorage('PHPSESSID'));
        $session->start();

        $this->handler = new AppListStringsHandler(
            $this->tester->getProjectDir(),
            $this->tester->getLegacyDir(),
            $this->tester->getLegacySessionName(),
            $this->tester->getDefaultSessionName(),
            $this->tester->getLegacyScope(),
            $session
        );
    }

    // tests

    /**
     * Test Invalid language handling in AppListStringsHandler
     */
    public function testInvalidLanguageCheck(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->handler->getAppListStrings('invalid_lang');
    }

    /**
     * Test default language retrieval in AppListStringsHandler
     */
    public function testDefaultLanguageKey(): void
    {
        $appListStrings = $this->handler->getAppListStrings('en_us');
        static::assertNotNull($appListStrings);
        static::assertEquals('en_us', $appListStrings->getId());
        static::assertIsArray($appListStrings->getItems());
        $this->assertLanguageListKey('moduleListSingular', 'Home', $appListStrings);
        $this->assertLanguageListKey('moduleList', 'Home', $appListStrings);
    }

    /**
     * Asserts that the given label $labelKey exists within the given list with $listKey in appListStrings
     * @param string $listKey
     * @param string $labelKey
     * @param AppListStrings $appStrings
     */
    protected function assertLanguageListKey(string $listKey, string $labelKey, AppListStrings $appStrings): void
    {
        static::assertArrayHasKey($listKey, $appStrings->getItems());
        static::assertNotEmpty($appStrings->getItems()[$listKey]);
        static::assertIsArray($appStrings->getItems()[$listKey]);
        static::assertNotEmpty($appStrings->getItems()[$listKey][$labelKey]);
    }
}
