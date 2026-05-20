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

namespace App\Security;

use App\Authentication\LegacyHandler\Authentication;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessEventListener implements EventSubscriberInterface
{

    /**
     * @var Authentication
     */
    private $authentication;
    protected SystemConfigHandler $config;

    public function __construct(Authentication $authentication, SystemConfigHandler $config)
    {
        $this->authentication = $authentication;
        $this->config = $config;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if (null === $this->authentication) {
            return;
        }

        $user = $event->getUser();

        $authType = $this->config->getSystemConfig('auth_type')->getValue();

        if (!$user->isTotpAuthenticationEnabled() ||  $authType === 'saml') {
            $result = $this->authentication->initLegacyUserSession($user->getUsername());

            if ($result === false) {
                throw new CustomUserMessageAuthenticationException('Authentication: Invalid login credentials');
            }
        }


    }
}
