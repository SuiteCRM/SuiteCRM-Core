<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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

use App\Authentication\LegacyHandler\UserHandler;
use App\Logging\Services\AppLoggingTrait;
use Nbgrp\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;


class AppSamlUserFactory implements SamlUserFactoryInterface
{
    use AppLoggingTrait;

    /**
     * @var UserHandler
     */
    protected $userHandler;

    /**
     * @var array|null
     */
    protected $samlAutoCreateAttributesMap;

    /**
     * @var LoggerInterface
     */
    protected $authLogger;

    /**
     * @param UserHandler $userHandler
     * @param array|null $samlAutoCreateAttributesMap
     * @param LoggerInterface $authLogger
     */
    public function __construct(
        UserHandler $userHandler,
        ?array $samlAutoCreateAttributesMap,
        LoggerInterface $authLogger
    ) {
        $this->userHandler = $userHandler;
        $this->samlAutoCreateAttributesMap = $samlAutoCreateAttributesMap;
        $this->authLogger = $authLogger;
    }

    /**
     * @inheritDoc
     */
    public function createUser(string $identifier, array $attributes): UserInterface
    {
        $this->log('createUser identifier: ' . $identifier);
        $this->logArray('createUser attributes', $attributes);

        $userInfo = $this->mapAttributes($attributes);


        $legacyUser = $this->userHandler->createExternalAuthUser($identifier, $userInfo);
        if ($legacyUser === null) {
            $this->log('createUser - Not able to create user');
            throw new RuntimeException('Not able to create user');
        }

        return $this->userHandler->mapUser($legacyUser);
    }


    /**
     * @param array $attributes
     * @return array
     */
    protected function mapAttributes(array $attributes): array
    {
        $userInfo = $attributes;
        if (empty($attributes) || empty($this->samlAutoCreateAttributesMap)) {
            return $userInfo;
        }

        $userInfo = [];
        foreach ($this->samlAutoCreateAttributesMap as $attributeKey => $fieldKey) {
            if (isset($attributes[$attributeKey][0])) {
                $userInfo[$fieldKey] = $attributes[$attributeKey][0];
            }
        }

        return $userInfo;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->authLogger;
    }
}
