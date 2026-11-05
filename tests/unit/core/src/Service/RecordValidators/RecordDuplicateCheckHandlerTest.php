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

namespace App\Tests\unit\core\src\Service\RecordValidators;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Data\LegacyHandler\PresetDataHandlers\RecordIdsDataHandler;
use App\Data\Service\Record\DuplicateCheck\DuplicateCheckProviderInterface;
use App\Data\Service\Record\DuplicateCheck\DuplicateCheckProviderRegistry;
use App\Process\Entity\Process;
use App\Process\Service\RecordValidators\RecordDuplicateCheckHandler;
use Codeception\Test\Unit;

/**
 * Class RecordDuplicateCheckHandlerTest
 * @package App\Tests
 */
class RecordDuplicateCheckHandlerTest extends Unit
{
    /**
     * Ids returned by the fake contacts provider
     * @var string[]
     */
    protected array $duplicateIds = [];

    /**
     * Calls received by the fake contacts provider
     * @var array
     */
    protected array $providerCalls = [];

    /**
     * Test the process requires edit access to the module
     */
    public function testRequiresEditAccess(): void
    {
        $handler = $this->buildHandler();

        static::assertSame(
            ['contacts' => [['action' => 'edit']]],
            $handler->getRequiredACLs($this->buildProcess(['module' => 'contacts']))
        );
    }

    /**
     * Test modules without an enabled provider are rejected
     */
    public function testValidateRejectsModuleWithoutProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buildHandler()->validate($this->buildProcess(['module' => 'opportunities']));
    }

    /**
     * Test providers of modules missing from the configuration are not used
     */
    public function testValidateRejectsDisabledModule(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buildHandler(['check_on_edit' => true, 'modules' => []])
            ->validate($this->buildProcess(['module' => 'contacts']));
    }

    /**
     * Test saving a record without duplicates succeeds
     */
    public function testNoDuplicatePasses(): void
    {
        $process = $this->buildProcess([
            'module' => 'contacts',
            'attributes' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
            'originalAttributes' => [],
        ]);

        $this->buildHandler()->run($process);

        static::assertSame('success', $process->getStatus());
        static::assertSame([], $process->getData());
        static::assertSame([['Jane', '']], $this->providerCalls);
    }

    /**
     * Test duplicates hold the save and are listed in a confirmation modal
     */
    public function testDuplicatesRequestListConfirmation(): void
    {
        $this->duplicateIds = ['id-1', 'id-2', 'id-1'];

        $process = $this->buildProcess([
            'module' => 'contacts',
            'attributes' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
            'originalAttributes' => [],
        ]);

        $this->buildHandler()->run($process);
        $data = $process->getData();

        static::assertSame('error', $process->getStatus());
        static::assertTrue($data['displayListConfirmation']);
        static::assertSame('contacts', $data['module']);
        static::assertSame('LBL_DUPLICATE_CHECK_TITLE', $data['confirmationTitle']);
        static::assertSame(RecordIdsDataHandler::PRESET_TYPE, $data['preset']['type']);
        static::assertSame(['id-1', 'id-2'], $data['preset']['params']['ids']);
    }

    /**
     * Test edits are passed to the provider with the record id when check_on_edit is set
     */
    public function testEditIsCheckedWhenEnabled(): void
    {
        $process = $this->buildProcess([
            'module' => 'contacts',
            'attributes' => ['id' => 'record-id', 'first_name' => 'Jane'],
            'originalAttributes' => ['id' => 'record-id', 'first_name' => 'John'],
        ]);

        $this->buildHandler()->run($process);

        static::assertSame([['Jane', 'record-id']], $this->providerCalls);
    }

    /**
     * Test edits are not checked when check_on_edit is off, like the legacy create screens
     */
    public function testEditIsSkippedWhenDisabled(): void
    {
        $this->duplicateIds = ['id-1'];

        $process = $this->buildProcess([
            'module' => 'contacts',
            'attributes' => ['id' => 'record-id', 'first_name' => 'Jane'],
            'originalAttributes' => ['id' => 'record-id', 'first_name' => 'John'],
        ]);

        $this->buildHandler(['check_on_edit' => false, 'modules' => ['contacts']])->run($process);

        static::assertSame('success', $process->getStatus());
        static::assertSame([], $this->providerCalls);
    }

    /**
     * Test the list preset keeps only valid, distinct record ids
     */
    public function testRecordIdsPresetFiltersIds(): void
    {
        $presetHandler = $this->make(RecordIdsDataHandler::class);

        $ids = $presetHandler->getIds([
            'preset' => [
                'type' => 'record-ids',
                'params' => [
                    'ids' => ['id-1', '', 'id-2', 'id-1', ['nested'], null],
                ],
            ],
        ]);

        static::assertSame(['id-1', 'id-2'], $ids);
        static::assertSame([], $presetHandler->getIds(['preset' => ['params' => ['ids' => 'id-1']]]));
    }

    /**
     * @param array|null $config
     * @return RecordDuplicateCheckHandler
     */
    protected function buildHandler(?array $config = null): RecordDuplicateCheckHandler
    {
        $test = $this;

        $provider = new class($test) implements DuplicateCheckProviderInterface {
            public function __construct(protected RecordDuplicateCheckHandlerTest $test)
            {
            }

            public function getModule(): string
            {
                return 'contacts';
            }

            public function getDuplicateIds(array $attributes, array $originalAttributes, string $recordId): array
            {
                return $this->test->recordProviderCall($attributes['first_name'] ?? '', $recordId);
            }
        };

        $registry = new DuplicateCheckProviderRegistry(
            [$provider],
            $config ?? ['check_on_edit' => true, 'modules' => ['contacts']]
        );

        return new RecordDuplicateCheckHandler($registry);
    }

    /**
     * Record a call of the fake provider and return the configured duplicates
     * @param string $firstName
     * @param string $recordId
     * @return string[]
     */
    public function recordProviderCall(string $firstName, string $recordId): array
    {
        $this->providerCalls[] = [$firstName, $recordId];

        return $this->duplicateIds;
    }

    /**
     * @param array $options
     * @return Process
     */
    protected function buildProcess(array $options): Process
    {
        $process = new Process();
        $process->setType(RecordDuplicateCheckHandler::PROCESS_TYPE);
        $process->setOptions($options);

        return $process;
    }
}
