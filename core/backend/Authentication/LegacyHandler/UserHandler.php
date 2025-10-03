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


namespace App\Authentication\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Users\Entity\User;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use App\UserPreferences\Service\UserPreferencesProviderInterface;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class UserHandler
 * @package App\Authentication\LegacyHandler
 */
class UserHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'user-handler';

    /**
     * @var SystemConfigProviderInterface
     */
    private $systemConfigProvider;

    /**
     * @var UserPreferencesProviderInterface
     */
    private $userPreferenceService;

    /**
     * LegacyHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param SystemConfigProviderInterface $systemConfigProvider
     * @param UserPreferencesProviderInterface $userPreferenceService
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        SystemConfigProviderInterface $systemConfigProvider,
        UserPreferencesProviderInterface $userPreferenceService
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->systemConfigProvider = $systemConfigProvider;
        $this->userPreferenceService = $userPreferenceService;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentUser(): ?SugarBean
    {
        $this->init();
        $this->startLegacyApp();

        global $current_user;

        $this->close();

        return $current_user;
    }

    /**
     * Is this the current user a system wide admin
     */
    public function isCurrentUserAdmin(): bool
    {
        /** @var \User $currentUser */
        $currentUser = $this->getCurrentUser();

        if (empty($currentUser)) {
            return false;
        }

        return $currentUser->isAdmin();
    }

    /**
     * Get current language
     * @return string
     */
    public function getCurrentLanguage(): string
    {
        $language = $this->getSessionLanguage();
        if (empty($language)) {
            $language = $this->getSystemLanguage();
        }

        return $language;
    }

    /**
     * Get current session language
     * @return string
     */
    public function getSessionLanguage(): string
    {
        $this->init();
        global $current_language;
        $this->close();

        return $current_language;
    }

    /**
     * Set current session language
     */
    public function setSessionLanguage(string $language): void
    {
        $this->init();
        set_current_language($language);
        $this->close();

        $this->requestStack->getMainRequest()?->getSession()->set('ui_language', $language);
    }

    /**
     * @param $key
     * @param $value
     * @param string $category
     */
    public function setUserPreference($key, $value, string $category = 'global'): void
    {
        $currentUser = $this->getCurrentUser();

        $this->init();
        $currentUser->setPreference($key, $value, 0,$category);

        $this->close();
    }
    /**
     * Get system default language
     * @return string
     */
    public function getSystemLanguage(): string
    {
        $language = 'en_us';

        $languageConfig = $this->systemConfigProvider->getSystemConfig('default_language');
        if ($languageConfig !== null) {
            $configLanguage = $languageConfig->getValue() ?? '';
            if ($configLanguage !== '') {
                $language = $configLanguage;
            }
        }

        return $language ?? 'en_us';
    }

    /**
     * Get user preferences language
     * @return string
     */
    public function getUserPreferencesLanguage(): string
    {
        $language = '';
        $languagePreference = $this->userPreferenceService->getUserPreference('global');
        if ($languagePreference !== null && !empty($languagePreference->getItems())) {
            $language = $languagePreference->getItems()['language'] ?? '';
        }

        $this->init();

        global $sugar_config;

        $loginLanguage = $this->systemConfigProvider->getSystemConfig('login_language')->getValue() ?? false;

        if (isset($_SESSION['authenticated_user_language']) && isTrue($loginLanguage)) {
            return $_SESSION['authenticated_user_language'];
        }

        $languages = get_languages();

        $enabled = false;

        foreach ($languages as $key => $item){
            if ($language === $key) {
                $enabled = true;
            }
        }

        if (!$enabled){
            $language = $sugar_config['default_language'];
        }

        $this->close();

        return $language ?? '';
    }

    /**
     * Create user
     * @param string $name
     * @param array $fields
     * @return SugarBean|null
     */
    public function createUser(string $name, array $fields): ?SugarBean
    {
        $this->init();
        $this->startLegacyApp();

        /* @noinspection PhpIncludeInspection */
        require_once 'portability/User/CreateUser.php';

        $service = new \CreateUser();
        $user = $service->create($name, $fields);

        $this->close();

        return $user;
    }

    /**
     * Create external auth user
     * @param string $name
     * @param array $fields
     * @return SugarBean|null
     */
    public function createExternalAuthUser(string $name, array $fields): ?SugarBean
    {
        $this->init();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/User/CreateUser.php';

        $service = new \CreateUser();
        $user = $service->createExternalAuthUser($name, $fields);

        $this->close();

        return $user;
    }

    /**
     * Check if user exists
     * @param string $name
     * @return bool
     */
    public function userExists(string $name): bool
    {
        $this->init();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/User/CreateUser.php';

        $service = new \CreateUser();
        $exists = $service->userExists($name);

        $this->close();

        return $exists;
    }

    /**
     * Map user bean to user entity
     * @param SugarBean $legacyUser
     * @return User
     */
    public function mapUser(SugarBean $legacyUser): User
    {
        $this->init();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/DateTime/DateFormatService.php';

        $formatter = new \DateFormatService();

        /** @var \User $legacyUser */
        $entityUser = new User();
        $entityUser->setId($legacyUser->id);
        $entityUser->setSystemGeneratedPassword($legacyUser->system_generated_password);
        $entityUser->setPwdLastChanged($legacyUser->pwd_last_changed);
        $entityUser->setAuthenticateId($legacyUser->authenticate_id);
        $entityUser->setSugarLogin($legacyUser->sugar_login);
        $entityUser->setFirstName($legacyUser->first_name);
        $entityUser->setLastName($legacyUser->last_name);
        $entityUser->setIsAdmin($legacyUser->is_admin);
        $entityUser->setExternalAuthOnly($legacyUser->external_auth_only);
        $entityUser->setReceiveNotifications($legacyUser->receive_notifications);
        $entityUser->setDescription($legacyUser->description);
        $entityUser->setDateEntered($formatter->toDateTime($legacyUser->date_entered));
        $entityUser->setDateModified($formatter->toDateTime($legacyUser->date_modified));
        $entityUser->setModifiedUserId($legacyUser->modified_user_id);
        $entityUser->setCreatedBy($legacyUser->created_by);
        $entityUser->setTitle($legacyUser->title);
        $entityUser->setPhoto($legacyUser->photo);
        $entityUser->setDepartment($legacyUser->department);
        $entityUser->setPhoneHome($legacyUser->phone_home);
        $entityUser->setPhoneMobile($legacyUser->phone_mobile);
        $entityUser->setPhoneWork($legacyUser->phone_work);
        $entityUser->setPhoneOther($legacyUser->phone_other);
        $entityUser->setPhoneFax($legacyUser->phone_fax);
        $entityUser->setStatus($legacyUser->status);
        $entityUser->setAddressStreet($legacyUser->address_street);
        $entityUser->setAddressCity($legacyUser->address_city);
        $entityUser->setAddressState($legacyUser->address_state);
        $entityUser->setAddressCountry($legacyUser->address_country);
        $entityUser->setAddressPostalcode($legacyUser->address_postalcode);
        $entityUser->setDeleted($legacyUser->deleted);
        $entityUser->setPortalOnly($legacyUser->portal_only);
        $entityUser->setShowOnEmployees($legacyUser->show_on_employees);
        $entityUser->setEmployeeStatus($legacyUser->employee_status);
        $entityUser->setMessengerId($legacyUser->messenger_id);
        $entityUser->setMessengerType($legacyUser->messenger_type);
        $entityUser->setReportsToId($legacyUser->reports_to_id);
        $entityUser->setFactorAuth($legacyUser->factor_auth);
        $entityUser->setFactorAuthInterface($legacyUser->factor_auth_interface);
        $entityUser->setUserHash($legacyUser->user_hash);
        $entityUser->setUserName($legacyUser->user_name);

        $this->close();

        return $entityUser;
    }
}
