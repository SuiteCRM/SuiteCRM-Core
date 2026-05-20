<?php

namespace App\Security\TwoFactor;

use App\Authentication\LegacyHandler\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct(Authentication $authentication)
    {
        $this->authentication = $authentication;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if ($user->isTotpAuthenticationEnabled()) {
            $result = $this->authentication->initLegacyUserSession($user->getUsername());

            if ($result === false) {
                return new Response('{"login_success": false, "two_factor_complete": false}');
            }
        }

        $redirect = $this->authentication->needsRedirect($user);

        if (!empty($redirect)) {

            $response = [
                'redirect' => $redirect,
                'login_success' => true,
                'two_factor_complete' => true,
            ];

            return new Response(json_encode($response));
        }


        return new Response('{"login_success": true, "two_factor_complete": true}');
    }
}
