<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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

namespace App\Security\Session;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\StrictSessionHandler;

class LegacyBridgeSessionHandler extends StrictSessionHandler
{
    private DefaultLegacyHandler $legacyHandler;

    public function __construct(\SessionHandlerInterface $handler, DefaultLegacyHandler $legacyHandler)
    {
        parent::__construct($handler);
        $this->legacyHandler = $legacyHandler;
    }

    public function read(#[\SensitiveParameter] string $sessionId): string
    {
        $this->legacyHandler->init();

        $result = parent::read($sessionId);

        $this->legacyHandler->close();
        return $result;
    }

    public function write(#[\SensitiveParameter] string $sessionId, string $data): bool
    {
        $this->legacyHandler->init();
        $result = parent::write($sessionId, $data);

        $this->legacyHandler->close();
        return $result;
    }

    public function open(string $savePath, string $sessionName): bool
    {
        $this->legacyHandler->init();
        $result = parent::open($savePath, $sessionName);

        $this->legacyHandler->close();
        return $result;
    }

    public function destroy(#[\SensitiveParameter] string $sessionId): bool
    {
        $this->legacyHandler->init();
        $result = parent::destroy($sessionId);

        $this->legacyHandler->close();
        return $result;
    }

    public function close(): bool
    {
        $this->legacyHandler->init();
        $result = parent::close();
        $this->legacyHandler->close();
        return $result;
    }

    public function gc(int $maxlifetime): int|false
    {
        $this->legacyHandler->init();
        $result = parent::gc($maxlifetime);
        $this->legacyHandler->close();
        return $result;
    }
}
