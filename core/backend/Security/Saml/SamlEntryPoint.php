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
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\Security\Saml;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Entry point of the SAML firewall.
 *
 * The front end uses hash routing: the page asked for (#/accounts/record/<id>) is in the url
 * fragment, which the browser does not send to the server. A redirect to the SAML login loses it,
 * and the user lands on the home page after the login.
 *
 * Instead, a browser navigation gets a small page that stores the fragment as the pre-login url
 * of the front end (session storage, see AppStateStore) and then goes to the SAML login.
 * Without JavaScript, the page goes to the SAML login (meta refresh).
 * Other requests are redirected to the SAML login, as before.
 */
class SamlEntryPoint implements AuthenticationEntryPointInterface
{
    protected const PRE_LOGIN_URL_STORAGE_KEY = 'scrm-pre-login-url';

    public function __construct(protected UrlGeneratorInterface $urlGenerator)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $loginUrl = $this->urlGenerator->generate('saml_login');

        if (!$this->isHtmlNavigation($request)) {
            return new RedirectResponse($loginUrl);
        }

        $jsLoginUrl = json_encode($loginUrl, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
        $jsKey = json_encode(self::PRE_LOGIN_URL_STORAGE_KEY, JSON_THROW_ON_ERROR);
        $htmlLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex,nofollow">
<noscript><meta http-equiv="refresh" content="0;url={$htmlLoginUrl}"></noscript>
<title>Signing in&hellip;</title>
</head>
<body>
<script>
(function () {
    try {
        var hash = window.location.hash || '';
        var url = hash.charAt(0) === '#' ? hash.slice(1) : hash;
        if (url && url !== '/' && url.toLowerCase() !== '/home') {
            sessionStorage.setItem({$jsKey}, url);
        }
    } catch (e) {
    }
    window.location.replace({$jsLoginUrl});
})();
</script>
</body>
</html>
HTML;

        // a 2xx status would be replaced by the 403 of the access denied exception
        return new Response($html, Response::HTTP_UNAUTHORIZED, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Browser navigations accept html; other requests (xhr, api) are redirected to the SAML login
     */
    protected function isHtmlNavigation(Request $request): bool
    {
        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $accept = (string)$request->headers->get('Accept', '');

        return $accept === '' || str_contains($accept, 'text/html') || str_contains($accept, '*/*');
    }
}
