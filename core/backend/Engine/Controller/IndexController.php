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


namespace App\Engine\Controller;

use App\Authentication\LegacyHandler\Authentication;
use App\Authentication\LegacyHandler\UserHandler;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    public const INDEX_HTML_PATH = '/public/dist/index.html';

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var UserHandler
     */
    protected $userHandler;

    /**
     * @var Authentication
     */
    protected $authentication;

    protected string $legacySessionName;
    protected string $defaultSessionName;


    /**
     * IndexController constructor.
     * @param string $projectDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param UserHandler $userHandler
     * @param Authentication $authentication
     */
    public function __construct(
        string         $projectDir,
        string         $legacySessionName,
        string         $defaultSessionName,
        UserHandler    $userHandler,
        Authentication $authentication
    )
    {
        $this->projectDir = $projectDir;

        $this->userHandler = $userHandler;
        $this->authentication = $authentication;
        $this->legacySessionName = $legacySessionName;
        $this->defaultSessionName = $defaultSessionName;
    }

    #[Route('/', name: 'index', methods: ["GET"], stateless: false)]
    public function index(Security $security, Request $request): Response
    {
        $indexHtmlPath = $this->projectDir . self::INDEX_HTML_PATH;

        if (!is_file($indexHtmlPath)) {
            throw new RuntimeException('Please run ng build from terminal');
        }

        $response = new Response(file_get_contents($indexHtmlPath));

        $isAppInstalled = $this->authentication->getAppInstallStatus();
        $isActive = $this->authentication->checkSession();

        if ($isAppInstalled && !$isActive) {
            $request->getSession()->invalidate();
            $request->getSession()->start();
            $this->authentication->initLegacySystemSession();
        }

        return $response;
    }

    #[Route('/auth', name: 'nativeAuth', methods: ["GET"])]
    public function nativeAuth(Security $security): Response
    {
        $indexHtmlPath = $this->projectDir . self::INDEX_HTML_PATH;

        if (!is_file($indexHtmlPath)) {
            throw new RuntimeException('Please run ng build from terminal');
        }

        $response = new Response(file_get_contents($indexHtmlPath));

        $user = $security->getUser();
        if ($user === null) {
            $response->headers->clearCookie('XSRF-TOKEN');
        }

        return $response;
    }

    #[Route('/logged-out', name: 'logged-out', methods: ["GET", "POST"], stateless: false)]
    public function loggedOut(Request $request, LoggerInterface $logger): Response
    {
        $indexHtmlPath = $this->projectDir . self::INDEX_HTML_PATH;

        if (!is_file($indexHtmlPath)) {
            throw new RuntimeException('Please run ng build from terminal');
        }

        $response = new RedirectResponse("./auth#logged-out");

        try {
            $request->getSession()->clear();
        } catch (\Exception $e) {
        }
        $response->headers->clearCookie('XSRF-TOKEN');
        $response->headers->clearCookie('TOKEN');
        $response->headers->clearCookie($this->legacySessionName);
        $response->headers->clearCookie($this->defaultSessionName);

        $this->authentication->logout();

        return $response;
    }
}
