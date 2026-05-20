<?php

namespace App\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {

        if ($token instanceof TwoFactorTokenInterface) {
            $user = $token->getAuthenticatedToken()->getUser();

            if (isTrue($user->getIsTotpEnabled())) {
                return new Response('{"login_success": "true", "two_factor_complete": "false"}');
            }
        }

        return new Response('{"login_success": "true"}');
    }
}
