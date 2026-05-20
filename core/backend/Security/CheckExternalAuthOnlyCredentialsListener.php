<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

namespace App\Security;

use App\Module\Users\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Ldap\Security\LdapBadge;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CheckExternalAuthOnlyCredentialsListener implements EventSubscriberInterface
{

    /**
     * @param CheckPassportEvent $event
     * @return void
     */
    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if ($passport->hasBadge(LdapBadge::class)) {
            return;
        }

        /** @var PasswordCredentials $passwordCredentials */
        $passwordCredentials = $passport->getBadge(PasswordCredentials::class);

        if ($passwordCredentials === null) {
            return;
        }

        $presentedPassword = $passwordCredentials->getPassword();
        if (empty($presentedPassword)) {
            throw new BadCredentialsException('The presented password cannot be empty.');
        }

        $user = $this->getUser($passport);

        if ($user === null) {
            return;
        }

        $isExternalAuthOnly = !empty($this->getExternalAuthOnly($passport));

        if ($isExternalAuthOnly) {
            throw new AccessDeniedException();
        }

    }

    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['onCheckPassport', 200]];
    }

    /**
     * @param Passport $passport
     * @return string|null
     */
    protected function getExternalAuthOnly(Passport $passport): ?string
    {
        $user = $this->getUser($passport);

        if ($user === null) {
            return null;
        }

        return $user->getExternalAuthOnly();
    }

    /**
     * @param Passport $passport
     * @return User|null
     */
    protected function getUser(Passport $passport): ?User
    {
        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);

        if ($userBadge === null) {
            return null;
        }

        $user = $userBadge->getUser();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}
