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

namespace App\Module\Quotes\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Module\Quotes\LegacyHandler\QuoteConverter;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Quote record actions that create a record from the quote (QuoteConverter),
 * then open it in the edit view, as the legacy actions did.
 */
abstract class QuoteConvertAction implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';

    public function __construct(
        protected QuoteConverter $converter,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
    }

    /**
     * Legacy module of the record created.
     */
    abstract protected function getTargetModule(): string;

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();

        return [
            'quotes' => [
                [
                    'action' => 'view',
                    'record' => $options['id'] ?? '',
                ],
            ],
            $this->moduleNameMapper->toFrontEnd($this->getTargetModule()) => [
                [
                    'action' => 'edit',
                ],
            ],
        ];
    }

    public function configure(Process $process): void
    {
        $process->setId($this->getProcessType());
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options['id']) || ($options['module'] ?? '') !== 'quotes') {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $id = $this->converter->convert((string)$process->getOptions()['id'], $this->getTargetModule());

        if ($id === null) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
            $process->setData([]);

            return;
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData([
            'handler' => 'redirect',
            'params' => [
                'route' => '/' . $this->moduleNameMapper->toFrontEnd($this->getTargetModule()) . '/edit/' . $id,
            ],
        ]);
    }
}
