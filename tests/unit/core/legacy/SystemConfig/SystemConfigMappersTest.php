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


namespace App\Tests\unit\core\legacy\SystemConfig;

use ApiPlatform\Exception\ItemNotFoundException;
use App\Module\LegacyHandler\ModuleNameMapperHandler;
use App\SystemConfig\LegacyHandler\DefaultModuleConfigMapper;
use App\SystemConfig\LegacyHandler\SystemConfigMappers;
use App\Tests\UnitTester;
use ArrayObject;
use Codeception\Test\Unit;
use Exception;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class SystemConfigMappersTest
 * @package App\Tests\unit\core\legacy\SystemConfig
 */
class SystemConfigMappersTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var SystemConfigMappers
     */
    protected $handler;

    /**
     * Test get existing mapper
     */
    public function testGetExistingConfigMapper(): void
    {
        $handler = $this->handler->get('default_module');
        static::assertNotNull($handler);
        static::assertEquals('default_module', $handler->getKey());
    }

    /**
     * Test get non existing mapper
     */
    public function testGetNonExistingConfigMapper(): void
    {
        try {
            $this->handler->get('test');
        } catch (ItemNotFoundException $e) {
            static::assertEquals('SystemConfig mapper is not defined', $e->getMessage());
        }
    }

    /**
     * Test get non existing mapper
     */
    public function testCheckNonExistingConfigMapper(): void
    {
        $exists = $this->handler->hasMapper('test');
        static::assertFalse($exists);
    }

    /**
     * Test get non existing mapper
     */
    public function testCheckExistingConfigMapper(): void
    {
        $exists = $this->handler->hasMapper('default_module');
        static::assertTrue($exists);
    }

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

        $obj = new ArrayObject([
            new DefaultModuleConfigMapper($moduleNameMapper),
        ]);
        $it = $obj->getIterator();

        $this->handler = new SystemConfigMappers($it);
    }
}
