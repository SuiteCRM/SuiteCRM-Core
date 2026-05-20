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
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

class LegacyBridgeSessionStorage extends PhpBridgeSessionStorage
{
    private DefaultLegacyHandler $legacyHandler;

    public function __construct(
        DefaultLegacyHandler $legacyHandler,
        AbstractProxy|\SessionHandlerInterface $handler = null,
        MetadataBag $metaBag = null
    )
    {
        parent::__construct($handler, $metaBag);
        $this->legacyHandler = $legacyHandler;
    }

    public function start(): bool
    {
        $this->legacyHandler->init();

        $result = parent::start();

        $this->legacyHandler->close();
        return $result;
    }

    public function isStarted(): bool
    {
        $this->legacyHandler->init();

        $result = parent::isStarted();

        $this->legacyHandler->close();
        return $result;
    }

    public function getId(): string
    {
        $this->legacyHandler->init();

        $result = parent::getId();

        $this->legacyHandler->close();
        return $result;
    }

    public function setId(string $id)
    {
        $this->legacyHandler->init();

        parent::setId($id);

        $this->legacyHandler->close();
    }

    public function getName(): string
    {
        $this->legacyHandler->init();

        $result = parent::getName();

        $this->legacyHandler->close();
        return $result;
    }

    public function setName(string $name)
    {
        $this->legacyHandler->init();

        parent::setName($name);

        $this->legacyHandler->close();
    }

    public function regenerate(bool $destroy = false, int $lifetime = null): bool
    {
        $this->legacyHandler->init();

        $result = parent::regenerate($destroy, $lifetime);

        $this->legacyHandler->close();
        return $result;
    }

    public function save()
    {
        $this->legacyHandler->init();

        parent::save();

        $this->legacyHandler->close();
    }

    public function clear()
    {
        $this->legacyHandler->init();

        parent::clear();

        $this->legacyHandler->close();
    }

    public function getBag(string $name): SessionBagInterface
    {
        $this->legacyHandler->init();

        $result = parent::getBag($name);

        $this->legacyHandler->close();
        return $result;
    }

    public function registerBag(SessionBagInterface $bag)
    {
        $this->legacyHandler->init();

        parent::registerBag($bag);

        $this->legacyHandler->close();
    }

    public function getMetadataBag(): MetadataBag
    {
        $this->legacyHandler->init();

        $result = parent::getMetadataBag();

        $this->legacyHandler->close();
        return $result;
    }
}
