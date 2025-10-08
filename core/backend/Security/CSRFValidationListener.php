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

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class CSRFValidationListener
 * @package App\Security
 */
class CSRFValidationListener
{
    /**
     * @var CSRFTokenManager
     */
    protected $csrfTokenManager;

    /**
     * @var array
     */
    protected $routes;

    /**
     * @var RouteMatcherInterface
     */
    protected $routeMatcher;

    /**
     * @var string
     */
    protected $headerName;

    /**
     * CSRFValidationListener constructor.
     * @param CSRFTokenManager $angularCsrfTokenManager
     * @param RouteMatcherInterface $routeMatcher
     * @param array $routes
     * @param $headerName
     */
    public function __construct(
        CSRFTokenManager $angularCsrfTokenManager,
        RouteMatcherInterface $routeMatcher,
        array $routes,
        $headerName
    ) {
        $this->csrfTokenManager = $angularCsrfTokenManager;
        $this->routeMatcher = $routeMatcher;
        $this->routes = $routes;
        $this->headerName = $headerName;
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (
            HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType() ||
            Request::METHOD_HEAD === $event->getRequest()->getMethod() ||
            !$this->routeMatcher->match($event->getRequest(), $this->routes)
        ) {
            return;
        }

        $value = $event->getRequest()->headers->get($this->headerName);
        if (empty($value) || !$this->csrfTokenManager->isTokenValid($value)) {
            throw new AccessDeniedHttpException('Invalid CSRF token');
        }
    }
}
