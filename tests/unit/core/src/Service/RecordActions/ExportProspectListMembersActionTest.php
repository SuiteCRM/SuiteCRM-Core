<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\Tests\unit\core\src\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Module\LegacyHandler\ModuleNameMapperHandler;
use App\Process\Entity\Process;
use App\Module\ProspectLists\Service\RecordActions\ExportProspectListMembersAction;
use App\Tests\UnitTester;
use Codeception\Test\Unit;
use Exception;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class ExportProspectListMembersActionTest
 * @package App\Tests
 */
class ExportProspectListMembersActionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var ExportProspectListMembersAction
     */
    protected $service;

    /**
     * @throws Exception
     */
    protected function _before(): void
    {
        $session = new Session(new MockArraySessionStorage('PHPSESSID'));
        $session->start();

        $moduleNameMapper = new ModuleNameMapperHandler(
            $this->tester->getProjectDir(),
            $this->tester->getLegacyDir(),
            $this->tester->getLegacySessionName(),
            $this->tester->getDefaultSessionName(),
            $this->tester->getLegacyScope(),
            $session
        );

        $this->service = new ExportProspectListMembersAction(
            $moduleNameMapper
        );
    }

    /**
     * Test members export download info
     */
    public function testExportMembersDownloadInfo(): void
    {
        $process = new Process();
        $process->setType('record-export-members');
        $process->setOptions([
            'action' => 'record-export-members',
            'id' => 'e24be4e0-4424-9728-9294-5ea315eef53e',
            'module' => 'prospect-lists'
        ]);

        $this->service->validate($process);
        $this->service->run($process);

        static::assertSame([
            'handler' => 'export',
            'params' => [
                'url' => 'legacy/index.php?entryPoint=export',
                'formData' => [
                    'uid' => 'e24be4e0-4424-9728-9294-5ea315eef53e',
                    'module' => 'ProspectLists',
                    'members' => '1',
                    'action' => 'index'
                ]
            ]
        ], $process->getData());

        static::assertEquals('success', $process->getStatus());
        static::assertSame([], $process->getMessages());
    }

    /**
     * Test export access is required on the target list
     */
    public function testRequiresExportAccessOnTheTargetList(): void
    {
        $process = new Process();
        $process->setOptions([
            'id' => 'e24be4e0-4424-9728-9294-5ea315eef53e',
            'module' => 'prospect-lists'
        ]);

        static::assertSame([
            'prospect-lists' => [
                [
                    'action' => 'export',
                    'record' => 'e24be4e0-4424-9728-9294-5ea315eef53e'
                ]
            ],
        ], $this->service->getRequiredACLs($process));
    }

    /**
     * Test other modules are rejected
     */
    public function testOtherModulesAreRejected(): void
    {
        $process = new Process();
        $process->setOptions([
            'id' => 'e24be4e0-4424-9728-9294-5ea315eef53e',
            'module' => 'contacts'
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->validate($process);
    }
}
