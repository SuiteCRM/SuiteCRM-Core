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

namespace App\Tests\unit\core\src\Service\RecordView;

use App\Data\Service\Record\DuplicateCheck\DuplicateCheckProviderInterface;
use App\Data\Service\Record\DuplicateCheck\DuplicateCheckProviderRegistry;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\RecordView\RecordViewDuplicateCheckMapper;
use Codeception\Test\Unit;

/**
 * Class RecordViewDuplicateCheckMapperTest
 * @package App\Tests
 */
class RecordViewDuplicateCheckMapperTest extends Unit
{
    /**
     * @var RecordViewDuplicateCheckMapper
     */
    protected $mapper;

    protected function _before(): void
    {
        $provider = $this->makeEmpty(DuplicateCheckProviderInterface::class, [
            'getModule' => 'contacts',
        ]);

        $this->mapper = new RecordViewDuplicateCheckMapper(
            new DuplicateCheckProviderRegistry([$provider], ['check_on_edit' => true, 'modules' => ['contacts']])
        );
    }

    /**
     * Test the validator is added to the record view of a configured module
     */
    public function testAddsValidatorToConfiguredModule(): void
    {
        $definition = $this->buildDefinition('contacts', [
            'panels' => [],
            'asyncValidators' => [
                'existing-validator' => ['key' => 'existing-validator'],
            ],
        ]);

        $this->mapper->map($definition, new FieldDefinition());

        $validators = $definition->getRecordView()['asyncValidators'] ?? [];

        static::assertArrayHasKey('existing-validator', $validators);
        static::assertSame(['key' => 'record-duplicate-check'], $validators['record-duplicate-check'] ?? null);
    }

    /**
     * Test modules without duplicate check configuration are left untouched
     */
    public function testIgnoresModuleWithoutConfiguration(): void
    {
        $recordView = ['panels' => [], 'asyncValidators' => []];
        $definition = $this->buildDefinition('opportunities', $recordView);

        $this->mapper->map($definition, new FieldDefinition());

        static::assertSame($recordView, $definition->getRecordView());
    }

    /**
     * @param string $module
     * @param array $recordView
     * @return ViewDefinition
     */
    protected function buildDefinition(string $module, array $recordView): ViewDefinition
    {
        $definition = new ViewDefinition();
        $definition->setId($module);
        $definition->setRecordView($recordView);

        return $definition;
    }
}
