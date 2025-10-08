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


namespace App\Navbar\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use App\Navbar\DataProvider\NavbarStateProvider;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')", provider: NavbarStateProvider::class),
    ],
    security: "is_granted('ROLE_USER')",
    graphQlOperations: [
        new Query(security: "is_granted('ROLE_USER')", provider: NavbarStateProvider::class),
    ]
)]
final class Navbar
{
    /**
     * @var string|null
     */
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'description' => 'user id',
        ]
    )]
    public ?string $userID;

    /**
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'tabs',
        ]
    )]
    public array $tabs;

    /**
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'groupedTabs',
        ]
    )]
    public array $groupedTabs;

    /**
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'userActionMenu',
        ]
    )]
    public array $userActionMenu;

    /**
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'modules',
        ]
    )]
    public array $modules;

    /**
     * @var int
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'int',
            'description' => 'maxTabs',
        ]
    )]
    public int $maxTabs;

    /**
     * @var string
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'description' => 'maxTabs',
        ]
    )]
    public string $type;

    /**
     * @var array
     */
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'description' => 'quickActions',
        ]
    )]
    public array $quickActions;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->userID ?? null,
            '_id' => $this->userID ?? null,
            'userID' => $this->userID ?? null,
            'tabs' => $this->tabs ?? null,
            'groupedTabs' => $this->groupedTabs ?? null,
            'userActionMenu' => $this->userActionMenu ?? null,
            'modules' => $this->modules ?? null,
            'maxTabs' => $this->maxTabs ?? null,
            'type' => $this->type ?? null,
            'quickActions' => $this->quickActions ?? null,
        ];
    }
}
