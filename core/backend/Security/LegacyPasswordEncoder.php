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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class LegacyPasswordEncoder
{

    use CheckPasswordLengthTrait;
    /**
     * @inheritDoc
     */
    public function encodePassword($raw, $salt): string
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        return password_hash(strtolower(md5($raw)), PASSWORD_DEFAULT);
    }

    /**
     * @inheritDoc
     */
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        $userHash = $encoded;
        $password = (md5($raw));

        $valid = self::checkPasswordMD5($password, $userHash);

        if ($valid) {
            return true;
        }

        return false;
    }

    /**
     * Check that md5-encoded password matches existing hash
     * @param string $passwordMd5 MD5-encoded password
     * @param string $userHash DB hash
     * @return bool Match or not?
     */
    public static function checkPasswordMD5(string $passwordMd5, string $userHash): bool
    {
        if (empty($userHash)) {
            return false;
        }

        if ($userHash[0] !== '$' && strlen($userHash) === 32) {
            $valid = strtolower($passwordMd5) === $userHash;
        } else {
            $valid = password_verify(strtolower($passwordMd5), $userHash);
        }

        return $valid;
    }
}
