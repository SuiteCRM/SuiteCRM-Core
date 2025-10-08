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

namespace App\Security\TwoFactor\EventListener;

use App\Module\Users\Entity\User;
use App\Security\TwoFactor\LegacyHandler\BlowfishCodeHandler;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TotpEncryptionListener
{
    protected BlowfishCodeHandler $blowfish;

    public function __construct(
        BlowfishCodeHandler $blowfish
    ) {
        $this->blowfish = $blowfish;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // only act on some "Product" entity
        if (!$entity instanceof User) {
            return;
        }

        $this->encodeTotpFields($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // only act on some "Product" entity
        if (!$entity instanceof User) {
            return;
        }
        $this->encodeTotpFields($args);
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $user = $args->getObject();

        // only act on some "Product" entity
        if (!$user instanceof User) {
            return;
        }

        $backupCodes = $user->getBackupCodes();
        $totpSecret = $user->getTotpSecret();

        if (!empty($backupCodes)) {
            $user->setBackupCodes($this->blowfish->decode('Users', $backupCodes));
        }
        if (!empty($totpSecret)) {
            $user->setTotpSecret($this->blowfish->decode('Users', $totpSecret));
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function encodeTotpFields(LifecycleEventArgs $args): void
    {
        $user = $args->getObject();
        $backupCodes = $user->getBackupCodes();
        $totpSecret = $user->getTotpSecret();

        if (!empty($backupCodes)) {
            $user->setBackupCodes($this->blowfish->encode('Users', $backupCodes));
        }
        if (!empty($totpSecret)) {
            $user->setTotpSecret($this->blowfish->encode('Users', $totpSecret));
        }
    }
}
