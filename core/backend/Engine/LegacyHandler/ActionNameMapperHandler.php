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

namespace App\Engine\LegacyHandler;

use ActionNameMapper;
use App\Process\Service\ActionNameMapperInterface;

class ActionNameMapperHandler extends LegacyHandler implements ActionNameMapperInterface
{
    public const HANDLER_KEY = 'action-name-mapper';
    /**
     * Lazy initialized mapper
     * @var ActionNameMapper
     */
    protected $mapper;

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function toFrontend(string $action): string
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->toFrontend($action);

        $this->close();

        return $result;
    }

    /**
     * Get mapper. Initialize it if needed
     * @return ActionNameMapper
     */
    protected function getMapper(): ActionNameMapper
    {
        if ($this->mapper !== null) {
            return $this->mapper;
        }

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/ActionNameMapper.php';

        $this->mapper = new ActionNameMapper();

        return $this->mapper;
    }

    /**
     * @inheritDoc
     */
    public function toLegacy(string $action): string
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->toLegacy($action);

        $this->close();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function isValidAction(?string $action): bool
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->isValidAction($action);

        $this->close();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMap(): array
    {
        $this->init();

        $mapper = $this->getMapper();

        $result = $mapper->getMap();

        $this->close();

        return $result;
    }
}
