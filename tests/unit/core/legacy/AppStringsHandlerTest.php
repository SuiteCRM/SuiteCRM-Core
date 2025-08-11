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
use App\Languages\Entity\AppStrings;
use App\Tests\UnitTester;
use Codeception\Test\Unit;
use App\Languages\LegacyHandler\AppStringsHandler;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class AppStringsHandlerTest
 * @package App\Tests\unit\core\legacy
 */
class AppStringsHandlerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var AppStringsHandler
     */
    protected $handler;

    protected function _before(): void
    {
        $session = new Session(new MockArraySessionStorage('PHPSESSID'));
        $session->start();

        $this->handler = new AppStringsHandler(
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
     * Test Invalid language handling in AppStringsHandler
     */
    public function testInvalidLanguageCheck(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->handler->getAppStrings('invalid_lang');
    }

    /**
     * Test default language retrieval in AppStringsHandler
     */
    public function testDefaultLanguageKey(): void
    {
        $appStrings = $this->handler->getAppStrings('en_us');
        static::assertNotNull($appStrings);
        static::assertEquals('en_us', $appStrings->getId());
        static::assertIsArray($appStrings->getItems());
        $this->assertLanguageKey('LBL_LOGOUT', $appStrings);
        $this->assertLanguageKey('LBL_LOGIN_BUTTON_LABEL', $appStrings);
        $this->assertLanguageKey('LBL_LOGIN_BUTTON_TITLE', $appStrings);
        $this->assertLanguageKey('LBL_LOGIN_FORGOT_PASSWORD', $appStrings);
        $this->assertLanguageKey('LBL_LOGIN_SUBMIT', $appStrings);
        $this->assertLanguageKey('LBL_USER_NAME', $appStrings);
        $this->assertLanguageKey('LBL_USER_NAME', $appStrings);
        $this->assertLanguageKey('ERR_INVALID_PASSWORD', $appStrings);
        $this->assertLanguageKey('LBL_PASSWORD', $appStrings);
    }

    /**
     * Asserts that the given label $key exists in appStrings
     * @param string $key
     * @param AppStrings $appStrings
     */
    protected function assertLanguageKey($key, AppStrings $appStrings): void
    {
        static::assertArrayHasKey($key, $appStrings->getItems());
        static::assertNotEmpty($appStrings->getItems()[$key]);
    }

}
