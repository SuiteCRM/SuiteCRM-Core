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


namespace App\Tests\unit\core\src\Service\RecordActions;

use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\RecordActions\EditAction;
use App\Tests\UnitTester;
use Codeception\Test\Unit;

/**
 * Class EditActionTest
 * @package App\Tests\unit\core\src\Service\RecordActions
 */
class EditActionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var EditAction
     */
    protected $service;

    protected function _before(): void
    {
        /** @var ModuleNameMapperInterface $moduleNameMapper */
        $moduleNameMapper = $this->makeEmpty(
            ModuleNameMapperInterface::class,
            [
                'toLegacy' => function (string $module): string {
                    $map = [
                        'contacts' => 'Contacts',
                        'opportunities' => 'Opportunities',
                    ];

                    return $map[$module] ?? $module;
                }
            ]
        );

        $this->service = new EditAction(
            $moduleNameMapper,
            $this->tester->getLegacyDir()
        );
    }

    /**
     * Regression: relation edit must redirect to legacy relation action when it exists.
     */
    public function testRelationshipEditRedirectWhenLegacyActionExists(): void
    {
        $process = new Process();
        $process->setType('record-edit');
        $process->setOptions([
            'action' => 'record-edit',
            'id' => '19b870a2-8d0b-4f4b-9116-67b5e501590f',
            'module' => 'contacts',
            'payload' => [
                'baseModule' => 'opportunities',
                'baseRecordId' => 'b641b285-1f5e-47a3-8a8d-0508aa20c2b1',
                'linkField' => 'contacts',
                'recordModule' => 'contacts',
                'relationshipEdit' => [
                    'enabled' => true,
                    'module' => 'contacts',
                    'action' => 'ContactOpportunityRelationshipEdit',
                    'recordId' => '90d6129c-2a2b-4160-804e-f16ebe230443',
                    'fallbackToRecordEdit' => true
                ]
            ]
        ]);

        $this->service->run($process);

        static::assertSame([
            'handler' => 'redirect',
            'params' => [
                'route' => 'contacts/ContactOpportunityRelationshipEdit/90d6129c-2a2b-4160-804e-f16ebe230443',
                'queryParams' => [
                    'action_module' => 'contacts',
                    'return_action' => 'DetailView',
                    'return_module' => 'Opportunities',
                    'return_id' => 'b641b285-1f5e-47a3-8a8d-0508aa20c2b1'
                ]
            ]
        ], $process->getData());

        static::assertSame('success', $process->getStatus());
        static::assertSame([], $process->getMessages());
    }

    /**
     * Regression: fallback must go to related record edit when relation action is not implemented.
     */
    public function testRelationshipEditFallbackToRecordEditWhenLegacyActionMissing(): void
    {
        $process = new Process();
        $process->setType('record-edit');
        $process->setOptions([
            'action' => 'record-edit',
            'id' => '19b870a2-8d0b-4f4b-9116-67b5e501590f',
            'module' => 'contacts',
            'payload' => [
                'baseModule' => 'opportunities',
                'baseRecordId' => 'b641b285-1f5e-47a3-8a8d-0508aa20c2b1',
                'linkField' => 'contacts',
                'recordModule' => 'contacts',
                'relationshipEdit' => [
                    'enabled' => true,
                    'module' => 'contacts',
                    'action' => 'ActionThatDoesNotExist',
                    'recordId' => '90d6129c-2a2b-4160-804e-f16ebe230443',
                    'fallbackToRecordEdit' => true
                ]
            ]
        ]);

        $this->service->run($process);

        static::assertSame([
            'handler' => 'redirect',
            'params' => [
                'route' => 'contacts/edit/19b870a2-8d0b-4f4b-9116-67b5e501590f',
                'queryParams' => [
                    'action_module' => 'contacts',
                    'return_action' => 'DetailView',
                    'return_module' => 'Opportunities',
                    'return_id' => 'b641b285-1f5e-47a3-8a8d-0508aa20c2b1'
                ]
            ]
        ], $process->getData());

        static::assertSame('success', $process->getStatus());
        static::assertSame([], $process->getMessages());
    }
}
