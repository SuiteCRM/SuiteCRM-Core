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


namespace App\Authentication\Controller;

use App\Authentication\LegacyHandler\UserHandler;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\LegacyHandler\CacheManagerHandler;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use App\Authentication\LegacyHandler\Authentication;
use App\Module\Users\Entity\User;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use RuntimeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController
 * @package App\Controller
 */
class SecurityController extends AbstractController
{
    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    private PreparedStatementHandler $preparedStatementHandler;


    private UserHandler $userHandler;

    private CacheManagerHandler $cacheManagerHandler;
    /**
     * SecurityController constructor.
     * @param Authentication $authentication
     * @param RequestStack $requestStack
     */
    public function __construct(
        Authentication           $authentication,
        RequestStack             $requestStack,
        EntityManagerInterface   $entityManager,
        PreparedStatementHandler $preparedStatementHandler,
        UserHandler $userHandler,
        CacheManagerHandler $cacheManagerHandler
    )
    {
        $this->authentication = $authentication;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->preparedStatementHandler = $preparedStatementHandler;
        $this->userHandler = $userHandler;
        $this->cacheManagerHandler = $cacheManagerHandler;
    }

    #[Route('/login', name: 'app_login', methods: ["GET", "POST"])]
    public function login(AuthenticationUtils $authenticationUtils, #[CurrentUser] ?User $user): JsonResponse
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $isAppInstalled = $this->authentication->getAppInstallStatus();
        $isAppInstallerLocked = $this->authentication->getAppInstallerLockStatus();
        $appStatus = [
            'installed' => $isAppInstalled,
            'locked' => $isAppInstallerLocked
        ];

        if ($error) {
            return $this->json([
                'active' => false,
                'message' => 'missing credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (null === $user) {
            return $this->json([
                'active' => false,
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->getResponseData($user, $appStatus);

        $needsRedirect = $this->authentication->needsRedirect($user);
        if (!empty($needsRedirect)) {
            $data['redirect'] = $needsRedirect;
        }

        $data['user'] = $user->getUserIdentifier();

        return $this->json($data, Response::HTTP_OK);
    }


    /**
     * @throws Exception
     */
    #[Route('/2fa/enable', name: 'app_2fa_enable', methods: ["GET", "POST"])]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function enable2fa(#[CurrentUser] ?User $user, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $secret = $totpAuthenticator->generateSecret();

        $user->setTotpSecret($secret);

        $qrCodeUrl = $totpAuthenticator->getQRContent($user);

        $this->preparedStatementHandler->update(
            'UPDATE users SET totp_secret = :totp_secret WHERE id = :id',
            ['totp_secret' => $secret, 'id' => $user->getId()],
            [['param' => 'totp_secret', 'type' => 'string'], ['param' => 'id', 'type' => 'string']]
        );

        $this->entityManager->flush();

        $response = [
            'url' => $qrCodeUrl,
            'svg' => $this->displayQRCode($qrCodeUrl),
            'secret' => $secret,
        ];

        return new Response(json_encode($response), Response::HTTP_OK);
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable', methods: ["GET"])]
    public function disable2fa(#[CurrentUser] ?User $user, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $id = $user->getId();

        $this->userHandler->setUserPreference('is_two_factor_enabled', false);
        $user->setTotpSecret(null);
        $user->setBackupCodes(null);

        $this->preparedStatementHandler->update(
            'UPDATE users SET totp_secret = NULL, is_totp_enabled = 0, backup_codes = NULL WHERE id = :id',
            ['id' => $id],
            [['param' => 'id', 'type' => 'string']]
        );

        $this->cacheManagerHandler->markAsNeedsUpdate('app-metadata-user-preferences-' . $user->getId());

        $response = [
            'two_factor_disabled' => true
        ];

        return new Response(json_encode($response), Response::HTTP_OK);
    }

    #[Route('/2fa/enable-finalize', name: 'app_2fa_enable_finalize', methods: ["GET", "POST"])]
    public function enableFinalize2fa(#[CurrentUser] ?User $user, Security $security, Request $request, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $auth_code = $request->getPayload()->get('auth_code') ?? '';

        $correctCode = $totpAuthenticator->checkCode($user, $auth_code);

        if ($correctCode) {
            $this->userHandler->setUserPreference('is_two_factor_enabled', true);
            $this->preparedStatementHandler->update(
                'UPDATE users SET is_totp_enabled = true WHERE id = :id',
                ['id' => $user->getId()],
                [['param' => 'id', 'type' => 'string']]
            );
        }

        $this->cacheManagerHandler->markAsNeedsUpdate('app-metadata-user-preferences-' . $user->getId());

        $response = ['two_factor_setup_complete' => $correctCode];

        return new Response(json_encode($response), Response::HTTP_OK);
    }

    #[Route('/logout', name: 'app_logout', methods: ["GET", "POST"])]
    public function logout(): void
    {
        throw new RuntimeException('This will be intercepted by the logout key');
    }

    #[Route('/session-status', name: 'app_session_status', methods: ["GET"])]
    public function sessionStatus(Security $security): JsonResponse
    {
        $isAppInstalled = $this->authentication->getAppInstallStatus();
        $isAppInstallerLocked = $this->authentication->getAppInstallerLockStatus();
        $appStatus = [
            'installed' => $isAppInstalled,
            'locked' => $isAppInstallerLocked
        ];

        if (!$isAppInstalled) {
            $response = new JsonResponse(['appStatus' => $appStatus], Response::HTTP_OK);
            $response->headers->clearCookie('XSRF-TOKEN');
            $this->requestStack->getSession()->invalidate();
            $this->requestStack->getSession()->start();

            return $response;
        }

        $isActive = $this->authentication->checkSession();

        if ($isActive !== true) {
            $response = new JsonResponse(['active' => false, 'appStatus' => $appStatus], Response::HTTP_OK);
            $this->requestStack->getSession()->invalidate();
            $this->requestStack->getSession()->start();
            $this->authentication->initLegacySystemSession();

            return $response;
        }

        $user = $security->getUser();
        if ($user === null) {
            $response = new JsonResponse(['active' => false, 'appStatus' => $appStatus], Response::HTTP_OK);
            $this->requestStack->getSession()->invalidate();
            $this->requestStack->getSession()->start();

            return $response;
        }

        $isUserActive = $this->authentication->isUserActive();
        if ($isUserActive !== true) {
            $response = new JsonResponse(['active' => false, 'appStatus' => $appStatus], Response::HTTP_OK);
            $this->requestStack->getSession()->invalidate();
            $this->requestStack->getSession()->start();

            return $response;
        }

        $isLoginWizardCompleteStatus = $this->authentication->getLoginWizardCompletedStatus();

        if ($isLoginWizardCompleteStatus) {
            $appStatus['loginWizardCompleted'] = true;
        } else {
            $appStatus['loginWizardCompleted'] = false;
        }

        $data = $this->getResponseData($user, $appStatus);

        if (!isset($data['redirect'])){
            $needsRedirect = $this->authentication->needsRedirect($user);
            if (!empty($needsRedirect)) {
                $data['redirect'] = $needsRedirect;
            }
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/auth/login', name: 'native_auth_login', methods: ["GET", "POST"])]
    public function nativeAuthLogin(AuthenticationUtils $authenticationUtils, #[CurrentUser] ?User $user): JsonResponse
    {
        return $this->login($authenticationUtils, $user);
    }

    #[Route('/auth/logout', name: 'native_auth_logout', methods: ["GET", "POST"])]
    public function nativeAuthLogout(): void
    {
        $this->logout();
    }

    #[Route('/auth/session-status', name: 'native_auth_session_status', methods: ["GET"])]
    public function nativeAuthSessionStatus(Security $security): JsonResponse
    {
        return $this->sessionStatus($security);
    }

    #[Route('/auth/2fa_check', name: 'native_auth_2fa_check', methods: ["GET", "POST"])]
    public function nativeCheckTwoFactorCode(Request $request): Response
    {
        return $this->redirectToRoute('app_2fa_check', $request->query->all());
    }

    /**
     * @param UserInterface $user
     * @param array $appStatus
     * @return array
     */
    private function getResponseData(UserInterface $user, array $appStatus): array
    {
        $id = $user->getId();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $userName = $user->getUsername();

        if ($user->isTotpAuthenticationEnabled()) {
            return [
                'appStatus' => $appStatus,
                'active' => true,
                'id' => $id,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'userName' => $userName,
                'two_factor_complete' => 'false'
            ];
        }

        return [
            'appStatus' => $appStatus,
            'active' => true,
            'id' => $id,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'userName' => $userName
        ];
    }

    private function displayQrCode(string $qrCodeContent): string
    {
        // ErrorCorrectionLevelHigh
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->writerOptions(['exclude_xml_declaration' => true])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(200)
            ->margin(0)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getString();
    }
}
