<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Data\LegacyHandler\RecordTemplate;

use App\Data\Entity\Record;
use App\Data\Service\RecordTemplate\RecordTemplateManagerInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RecordTemplateHandler extends LegacyHandler implements RecordTemplateManagerInterface
{
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {

        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return 'record-template-handler';
    }

    public function isType(Record $record, string $templateName): bool
    {
        $module = $record->getModule();
        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);

        $this->init();

        $bean = \BeanFactory::newBean($legacyModuleName);

        $isOfType = false;
        if (!empty($bean)) {
            $isOfType = $this->isOfType($templateName, $bean);
        }


        $this->close();

        return $isOfType;
    }

    /**
     * @param string $templateName
     * @param \SugarBean $bean
     * @return bool
     */
    protected function isOfType(string $templateName, \SugarBean $bean): bool
    {
        $isOfType = false;
        switch ($templateName) {
            case 'person':
                $isOfType = $bean instanceof \Person;
                break;

            case 'company':
                $isOfType = $bean instanceof \Company;
                break;

            case 'file':
                $isOfType = $bean instanceof \File;
                break;

            case 'issue':
                $isOfType = $bean instanceof \Issue;
                break;

            case 'sale':
                $isOfType = $bean instanceof \Sale;
                break;
            case 'basic':
                $isOfType = $bean instanceof \Basic;
                break;
        }

        return $isOfType;
    }
}
