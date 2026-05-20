<?php

namespace App\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorAuthenticationRequiredHandler implements AuthenticationRequiredHandlerInterface
{
    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        // Return the response to tell the client that authentication hasn't completed yet and
        // two-factor authentication is required.
        return new Response('{"error": "access_denied", "two_factor_complete": false}');
    }
}
