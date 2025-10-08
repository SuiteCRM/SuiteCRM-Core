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


namespace App\Module\Users\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use App\Module\Users\Repository\UserRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
    ],
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(security: "is_granted('ROLE_USER')"),
    ]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[ORM\Index(
    columns: ["user_name", "is_group", "status", "last_name", "first_name", "id"],
    name: "idx_user_name",
    options: ['lengths' => [null, null, null, 30, 30]]
)]
class User implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface, BackupCodeInterface
{
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'The user id'
        ]
    )]
    #[ORM\Column(
        name: "id",
        type: "string",
        length: 36,
        nullable: false,
        options: ["fixed" => true]
    )]
    #[ORM\Id()]
    private string $id;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The username'
        ]
    )]
    #[ORM\Column(
        name: "user_name",
        type: "string",
        length: 60,
        nullable: true,
    )]
    private ?string $user_name;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The user hash'
        ]
    )]
    #[ORM\Column(
        name: "user_hash",
        type: "string",
        length: 255,
        nullable: true,
    )]
    private ?string $userHash;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The system generated password hash'
        ]
    )]
    #[ORM\Column(
        name: "system_generated_password",
        type: "boolean",
        length: 1,
        nullable: true,
    )]
    private ?bool $systemGeneratedPassword;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The date when password last changed'
        ]
    )]
    #[ORM\Column(
        name: "pwd_last_changed",
        type: "datetime",
        nullable: true
    )]
    private ?DateTime $pwdLastChanged;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The authenticate id'
        ]
    )]
    #[ORM\Column(
        name: "authenticate_id",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $authenticateId;

    #[ApiProperty(
        openapiContext: [
            'type' => 'boolean',
            'description' => 'sugar login'
        ]
    )]
    #[ORM\Column(
        name: "sugar_login",
        type: "boolean",
        length: 1,
        nullable: true,
        options: ["default" => 1]
    )]
    private ?bool $sugarLogin = true;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The first name'
        ]
    )]
    #[ORM\Column(
        name: "first_name",
        type: "string",
        length: 255,
        nullable: true
    )]
    private ?string $firstName;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'The last name'
        ]
    )]
    #[ORM\Column(
        name: "last_name",
        type: "string",
        length: 255,
        nullable: true
    )]
    private ?string $lastName;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'Is admin'
        ]
    )]
    #[ORM\Column(
        name: "is_admin",
        type: "boolean",
        nullable: true,
        length: 1,
        options: ["default" => 0]
    )]
    private ?bool $isAdmin = false;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'external auth only'
        ]
    )]
    #[ORM\Column(
        name: "external_auth_only",
        type: "boolean",
        nullable: true,
        length: 1,
        options: ["default" => 0]
    )]
    private ?bool $externalAuthOnly = false;

    #[ApiProperty(
        openapiContext: [
            'type' => 'boolean',
            'description' => 'receive notifications'
        ]
    )]
    #[ORM\Column(
        name: "receive_notifications",
        type: "boolean",
        nullable: true,
        length: 1,
        options: ["default" => 1]
    )]
    private ?bool $receiveNotifications = true;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'description'
        ]
    )]
    #[ORM\Column(
        name: "description",
        type: "text",
        length: 65535,
        nullable: true,
    )]
    private ?string $description;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'date entered'
        ]
    )]
    #[ORM\Column(
        name: "date_entered",
        type: "datetime",
        nullable: true
    )]
    private ?DateTime $dateEntered;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'date modified'
        ]
    )]
    #[ORM\Column(
        name: "date_modified",
        type: "datetime",
        nullable: true
    )]
    private ?DateTime $dateModified;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'modified by user id'
        ]
    )]
    #[ORM\Column(
        name: "modified_user_id",
        type: "string",
        length: 36,
        nullable: true,
        options: ["fixed" => true]
    )]
    private ?string $modifiedUserId;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'created by user id'
        ]
    )]
    #[ORM\Column(
        name: "created_by",
        type: "string",
        length: 36,
        nullable: true,
        options: ["fixed" => true]
    )]
    private ?string $createdBy;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'title'
        ]
    )]
    #[ORM\Column(
        name: "title",
        type: "string",
        length: 50,
        nullable: true,
    )]
    private ?string $title;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'photo'
        ]
    )]
    #[ORM\Column(
        name: "photo",
        type: "string",
        length: 255,
        nullable: true,
    )]
    private ?string $photo;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'department'
        ]
    )]
    #[ORM\Column(
        name: "department",
        type: "string",
        length: 50,
        nullable: true,
    )]
    private ?string $department;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'home phone'
        ]
    )]
    #[ORM\Column(
        name: "phone_home",
        type: "string",
        length: 50,
        nullable: true,
    )]
    private ?string $phoneHome;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'mobile phone'
        ]
    )]
    #[ORM\Column(
        name: "phone_mobile",
        type: "string",
        length: 50,
        nullable: true,
    )]
    private ?string $phoneMobile;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'work phone'
        ]
    )]
    #[ORM\Column(
        name: "phone_work",
        type: "string",
        length: 50,
        nullable: true,
    )]
    private ?string $phoneWork;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'other phone'
        ]
    )]
    #[ORM\Column(
        name: "phone_other",
        type: "string",
        length: 50,
        nullable: true,
    )]
    private ?string $phoneOther;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'fax phone'
        ]
    )]
    #[ORM\Column(
        name: "phone_fax",
        type: "string",
        length: 50,
        nullable: true,
    )]
    private ?string $phoneFax;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'status'
        ]
    )]
    #[ORM\Column(
        name: "status",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $status;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'address street'
        ]
    )]
    #[ORM\Column(
        name: "address_street",
        type: "string",
        length: 150,
        nullable: true,
    )]
    private ?string $addressStreet;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'address city'
        ]
    )]
    #[ORM\Column(
        name: "address_city",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $addressCity;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'address state'
        ]
    )]
    #[ORM\Column(
        name: "address_state",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $addressState;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'address country'
        ]
    )]
    #[ORM\Column(
        name: "address_country",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $addressCountry;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'address postal code'
        ]
    )]
    #[ORM\Column(
        name: "address_postalcode",
        type: "string",
        length: 20,
        nullable: true,
    )]
    private ?string $addressPostalcode;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'deleted'
        ]
    )]
    #[ORM\Column(
        name: "deleted",
        type: "boolean",
        length: 1,
        nullable: true,
    )]
    private ?bool $deleted = false;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'portal only'
        ]
    )]
    #[ORM\Column(
        name: "portal_only",
        type: "boolean",
        nullable: true,
        length: 1,
        options: ["default" => 0]
    )]
    private ?bool $portalOnly = false;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'show on employees'
        ]
    )]
    #[ORM\Column(
        name: "show_on_employees",
        type: "boolean",
        length: 1,
        nullable: true,
        options: ["default" => 1]
    )]
    private ?bool $showOnEmployees = true;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'employee status'
        ]
    )]
    #[ORM\Column(
        name: "employee_status",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $employeeStatus;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'messenger id'
        ]
    )]
    #[ORM\Column(
        name: "messenger_id",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $messengerId;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'messenger type'
        ]
    )]
    #[ORM\Column(
        name: "messenger_type",
        type: "string",
        length: 100,
        nullable: true,
    )]
    private ?string $messengerType;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'reports to id'
        ]
    )]
    #[ORM\Column(
        name: "reports_to_id",
        type: "string",
        length: 36,
        nullable: true,
        options: ["fixed" => true]
    )]
    private ?string $reportsToId;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_group", type="boolean", nullable=true)
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'is group'
        ]
    )]
    #[ORM\Column(
        name: "is_group",
        type: "boolean",
        length: 1,
        nullable: true
    )]
    private ?bool $isGroup;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'factor auth'
        ]
    )]
    #[ORM\Column(
        name: "factor_auth",
        type: "boolean",
        nullable: true
    )]
    private ?bool $factorAuth;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'factor auth interface',
        ]
    )]
    #[ORM\Column(
        name: "factor_auth_interface",
        type: "string",
        length: 255,
        nullable: true,
    )]
    private ?string $factorAuthInterface;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'totp secret',
        ]
    )]
    #[ORM\Column(name: "totp_secret", type: 'string', length: 255, nullable: true)]
    private ?string $totpSecret;

    #[ORM\Column(
        name: "is_totp_enabled",
        type: "boolean",
        nullable: true
    )]
    private ?bool $isTotpEnabled = false;

    #[ORM\Column(
        name: "backup_codes",
        type: "json",
        nullable: true
    )]
    private ?array $backupCodes = [];

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id ?? null;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getSystemGeneratedPassword(): ?bool
    {
        return $this->systemGeneratedPassword ?? null;
    }

    public function setSystemGeneratedPassword(?bool $systemGeneratedPassword): self
    {
        $this->systemGeneratedPassword = $systemGeneratedPassword;

        return $this;
    }

    public function getPwdLastChanged(): ?DateTimeInterface
    {
        return $this->pwdLastChanged ?? null;
    }

    public function setPwdLastChanged(?DateTimeInterface $pwdLastChanged): self
    {
        $this->pwdLastChanged = $pwdLastChanged;

        return $this;
    }

    public function getAuthenticateId(): ?string
    {
        return $this->authenticateId ?? null;
    }

    public function setAuthenticateId(?string $authenticateId): self
    {
        $this->authenticateId = $authenticateId;

        return $this;
    }

    public function getSugarLogin(): ?bool
    {
        return $this->sugarLogin ?? null;
    }

    public function setSugarLogin(?bool $sugarLogin): self
    {
        $this->sugarLogin = $sugarLogin;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName ?? null;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName ?? null;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        return $this->isAdmin ?? null;
    }

    public function setIsAdmin(?bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getExternalAuthOnly(): ?bool
    {
        return $this->externalAuthOnly ?? null;
    }

    public function setExternalAuthOnly(?bool $externalAuthOnly): self
    {
        $this->externalAuthOnly = $externalAuthOnly;

        return $this;
    }

    public function getReceiveNotifications(): ?bool
    {
        return $this->receiveNotifications ?? null;
    }

    public function setReceiveNotifications(?bool $receiveNotifications): self
    {
        $this->receiveNotifications = $receiveNotifications;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDateEntered(): ?DateTimeInterface
    {
        return $this->dateEntered ?? null;
    }

    public function setDateEntered(?DateTimeInterface $dateEntered): self
    {
        $this->dateEntered = $dateEntered;

        return $this;
    }

    public function getDateModified(): ?DateTimeInterface
    {
        return $this->dateModified ?? null;
    }

    public function setDateModified(?DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getModifiedUserId(): ?string
    {
        return $this->modifiedUserId ?? null;
    }

    public function setModifiedUserId(?string $modifiedUserId): self
    {
        $this->modifiedUserId = $modifiedUserId;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy ?? null;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title ?? null;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo ?? null;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department ?? null;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function getPhoneHome(): ?string
    {
        return $this->phoneHome ?? null;
    }

    public function setPhoneHome(?string $phoneHome): self
    {
        $this->phoneHome = $phoneHome;

        return $this;
    }

    public function getPhoneMobile(): ?string
    {
        return $this->phoneMobile ?? null;
    }

    public function setPhoneMobile(?string $phoneMobile): self
    {
        $this->phoneMobile = $phoneMobile;

        return $this;
    }

    public function getPhoneWork(): ?string
    {
        return $this->phoneWork ?? null;
    }

    public function setPhoneWork(?string $phoneWork): self
    {
        $this->phoneWork = $phoneWork;

        return $this;
    }

    public function getPhoneOther(): ?string
    {
        return $this->phoneOther ?? null;
    }

    public function setPhoneOther(?string $phoneOther): self
    {
        $this->phoneOther = $phoneOther;

        return $this;
    }

    public function getPhoneFax(): ?string
    {
        return $this->phoneFax ?? null;
    }

    public function setPhoneFax(?string $phoneFax): self
    {
        $this->phoneFax = $phoneFax;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status ?? null;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAddressStreet(): ?string
    {
        return $this->addressStreet ?? null;
    }

    public function setAddressStreet(?string $addressStreet): self
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    public function getAddressCity(): ?string
    {
        return $this->addressCity ?? null;
    }

    public function setAddressCity(?string $addressCity): self
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    public function getAddressState(): ?string
    {
        return $this->addressState ?? null;
    }

    public function setAddressState(?string $addressState): self
    {
        $this->addressState = $addressState;

        return $this;
    }

    public function getAddressCountry(): ?string
    {
        return $this->addressCountry ?? null;
    }

    public function setAddressCountry(?string $addressCountry): self
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }

    public function getAddressPostalcode(): ?string
    {
        return $this->addressPostalcode ?? null;
    }

    public function setAddressPostalcode(?string $addressPostalcode): self
    {
        $this->addressPostalcode = $addressPostalcode;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted ?? null;
    }

    public function setDeleted(?bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getPortalOnly(): ?bool
    {
        return $this->portalOnly ?? null;
    }

    public function setPortalOnly(?bool $portalOnly): self
    {
        $this->portalOnly = $portalOnly;

        return $this;
    }

    public function getShowOnEmployees(): ?bool
    {
        return $this->showOnEmployees ?? null;
    }

    public function setShowOnEmployees(?bool $showOnEmployees): self
    {
        $this->showOnEmployees = $showOnEmployees;

        return $this;
    }

    public function getEmployeeStatus(): ?string
    {
        return $this->employeeStatus ?? null;
    }

    public function setEmployeeStatus(?string $employeeStatus): self
    {
        $this->employeeStatus = $employeeStatus;

        return $this;
    }

    public function getMessengerId(): ?string
    {
        return $this->messengerId ?? null;
    }

    public function setMessengerId(?string $messengerId): self
    {
        $this->messengerId = $messengerId;

        return $this;
    }

    public function getMessengerType(): ?string
    {
        return $this->messengerType ?? null;
    }

    public function setMessengerType(?string $messengerType): self
    {
        $this->messengerType = $messengerType;

        return $this;
    }

    public function getReportsToId(): ?string
    {
        return $this->reportsToId ?? null;
    }

    public function setReportsToId(?string $reportsToId): self
    {
        $this->reportsToId = $reportsToId;

        return $this;
    }

    public function getIsGroup(): ?bool
    {
        return $this->isGroup ?? null;
    }

    public function setIsGroup(?bool $isGroup): self
    {
        $this->isGroup = $isGroup;

        return $this;
    }

    public function getFactorAuth(): ?bool
    {
        return $this->factorAuth ?? null;
    }

    public function setFactorAuth(?bool $factorAuth): self
    {
        $this->factorAuth = $factorAuth;

        return $this;
    }

    public function getFactorAuthInterface(): ?string
    {
        return $this->factorAuthInterface ?? null;
    }

    public function setFactorAuthInterface(?string $factorAuthInterface): self
    {
        $this->factorAuthInterface = $factorAuthInterface;

        return $this;
    }

    public function getBackupCodes(): array
    {
        return $this->backupCodes ?? [];
    }

    public function setBackupCodes(?array $backupCodes): self
    {
        $this->backupCodes = $backupCodes ?? [];
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->userHash !== $user->getPassword()) {
            return false;
        }

        if ($this->user_name !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return $this->getUserHash();
    }

    public function getUserHash(): ?string
    {
        return $this->userHash ?? null;
    }

    public function setUserHash(?string $userHash): self
    {
        $this->userHash = $userHash;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserName(): ?string
    {
        return (string)$this->user_name;
    }

    public function setUserName(?string $userName): self
    {
        $this->user_name = $userName;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUserName();
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return !empty($this->getTotpSecret()) && $this->getIsTotpEnabled();
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserName();
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return new TotpConfiguration($this->getTotpSecret(), TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret ?? '';
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;

        return $this;
    }

    public function getIsTotpEnabled(): ?bool
    {
        return $this->isTotpEnabled ?? false;
    }

    public function setIsTotpEnabled(?bool $isTotpEnabled): self
    {
        $this->isTotpEnabled = $isTotpEnabled;

        return $this;
    }

    /**
     * Check if it is a valid backup code.
     */
    public function isBackupCode(string $code): bool
    {
        $correctCode = false;
        $backupCodes = $this->getBackupCodes();

        if (in_array($code, $backupCodes, true)){
            $correctCode = true;
            $this->invalidateBackupCode($code);
        }

        return $correctCode;
    }

    /**
     * Invalidate a backup code
     */
    public function invalidateBackupCode(string $code): void
    {
        $backupCodes = $this->getBackupCodes();
        $key = array_search($code, $backupCodes, true);
        if ($key !== false){
            unset($backupCodes[$key]);
        }

        $this->setBackupCodes(array_values($backupCodes));
    }

}
