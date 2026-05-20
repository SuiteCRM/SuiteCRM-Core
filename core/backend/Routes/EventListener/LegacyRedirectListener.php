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


namespace App\Routes\EventListener;

use App\Routes\Service\LegacyApiRedirectHandler;
use App\Routes\Service\LegacyAssetRedirectHandler;
use App\Routes\Service\LegacyNonViewActionRedirectHandler;
use App\Routes\Service\RouteConverterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class LegacyRedirectListener
 * @package App\EventListener
 */
class LegacyRedirectListener
{
    /**
     * @var RouteConverterInterface
     */
    private $routeConverter;

    /**
     * @var LegacyAssetRedirectHandler
     */
    private $legacyAssetHandler;

    /**
     * @var LegacyApiRedirectHandler
     */
    private $legacyApiRedirectHandler;
    /**
     * @var LegacyNonViewActionRedirectHandler
     */
    private $legacyNonViewActionRedirectHandler;

    /**
     * LegacyRedirectListener constructor.
     * @param RouteConverterInterface $routeConverter
     * @param LegacyAssetRedirectHandler $legacyAssetHandler
     * @param LegacyApiRedirectHandler $legacyApiRedirectHandler
     * @param LegacyNonViewActionRedirectHandler $legacyNonViewActionRedirectHandler
     */
    public function __construct(
        RouteConverterInterface $routeConverter,
        LegacyAssetRedirectHandler $legacyAssetHandler,
        LegacyApiRedirectHandler $legacyApiRedirectHandler,
        LegacyNonViewActionRedirectHandler $legacyNonViewActionRedirectHandler
    ) {
        $this->routeConverter = $routeConverter;
        $this->legacyAssetHandler = $legacyAssetHandler;
        $this->legacyApiRedirectHandler = $legacyApiRedirectHandler;
        $this->legacyNonViewActionRedirectHandler = $legacyNonViewActionRedirectHandler;
    }

    /**
     * Re-direct user
     * @param RequestEvent $event
     */
    public function __invoke(RequestEvent $event): void
    {
        if ($this->isLegacyAsset($event)) {
            $url = $this->legacyAssetHandler->convert($event->getRequest());
            $response = new RedirectResponse($url);
            $event->setResponse($response);

            return;
        }

        if ($this->isLegacyApi($event)) {
            $url = $this->legacyApiRedirectHandler->convert($event->getRequest());
            $response = new RedirectResponse($url, 307);
            $event->setResponse($response);

            return;
        }

        if ($this->isLegacyViewRoute($event)) {
            $url = $this->routeConverter->convert($event->getRequest());
            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }

        if ($this->isLegacyNonViewActionRoute($event)) {
            $url = $this->legacyNonViewActionRedirectHandler->convert($event->getRequest());
            $response = new RedirectResponse($url, 307);
            $event->setResponse($response);
        }
    }

    /**
     * Check if it is legacy asset request
     * @param RequestEvent $event
     * @return bool
     */
    protected function isLegacyAsset(RequestEvent $event): bool
    {
        return $this->legacyAssetHandler->isAssetRequest($event->getRequest());
    }

    /**
     * Check if it is legacy api request
     * @param RequestEvent $event
     * @return bool
     */
    protected function isLegacyApi(RequestEvent $event): bool
    {
        return $this->legacyApiRedirectHandler->isApiRequest($event->getRequest());
    }

    /**
     * Check if request is a legacy view request
     * @param RequestEvent $event
     * @return bool
     */
    protected function isLegacyViewRoute(RequestEvent $event): bool
    {
        return $this->routeConverter->isLegacyViewRoute($event->getRequest());
    }

    /**
     * Check if it is legacy non view request
     * @param RequestEvent $event
     * @return bool
     */
    protected function isLegacyNonViewActionRoute(RequestEvent $event): bool
    {
        return $this->legacyNonViewActionRedirectHandler->isMatch($event->getRequest());
    }
}
