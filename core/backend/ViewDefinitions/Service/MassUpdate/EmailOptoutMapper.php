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

namespace App\ViewDefinitions\Service\MassUpdate;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\ViewDefinitions\Service\FieldAliasMapper;
use App\ViewDefinitions\Service\MassUpdateDefinitionMapperInterface;
use Configurator;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class EmailOptoutMapper extends LegacyHandler implements MassUpdateDefinitionMapperInterface
{
    use MassUpdateFieldDefinitionsInjectorTrait;

    /**
     * @var FieldAliasMapper
     */
    private $fieldAliasMapper;

    /**
     * EmailOptoutMapper constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param FieldAliasMapper $fieldAliasMapper
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        FieldAliasMapper $fieldAliasMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->fieldAliasMapper = $fieldAliasMapper;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'email-optout';
    }

    /**
     * @inheritDoc
     */
    public function map(string $module, array &$fields, array $vardefs): void
    {
        $this->init();

        if (!in_array($module, ['contacts', 'accounts', 'leads', 'prospects'])) {
            return;
        }

        $fields[] = $this->buildField(
            [
                'name' => 'optout_primary',
                'label' => 'LBL_OPT_OUT_FLAG_PRIMARY'
            ],
            'optout_primary',
            [
                'optout_primary' => [
                    'name' => 'optout_primary',
                    'vname' => 'LBL_OPT_OUT_FLAG_PRIMARY',
                    'type' => 'bool',
                ]
            ],
            $this->fieldAliasMapper
        );

        $configurator = new Configurator();

        if ($configurator->isConfirmOptInEnabled() || $configurator->isOptInEnabled()) {
            $fields[] = $this->buildField(
                [
                    'name' => 'optin_primary',
                    'label' => 'LBL_OPT_IN_FLAG_PRIMARY'
                ],
                'optout_primary',
                [
                    'name' => 'optin_primary',
                    'vname' => 'LBL_OPT_IN_FLAG_PRIMARY',
                    'type' => 'bool',
                ],
                $this->fieldAliasMapper
            );
        }

        $this->close();
    }

}
