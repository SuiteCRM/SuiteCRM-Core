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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\Module\OAuth2Tokens\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Revoke Tokens of the OAuth2 tokens list, as the legacy list (OAuth2TokensController mass
 * update: the tokens marked revoked and saved). A user revokes the tokens the list shows him:
 * all for an admin, his own otherwise (OAuth2Tokens::create_new_list_query()).
 */
class OAuth2TokensHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'oauth2-tokens';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @param string[] $ids
     * @return int the tokens revoked
     */
    public function revoke(array $ids): int
    {
        $this->init();
        $this->startLegacyApp();

        try {
            global $current_user;

            $revoked = 0;
            foreach (array_unique($ids) as $id) {
                $token = \BeanFactory::getBean('OAuth2Tokens', (string)$id);
                if (!$token || empty($token->id)) {
                    continue;
                }
                if (!is_admin($current_user) && $token->assigned_user_id !== $current_user->id) {
                    continue;
                }

                $token->token_is_revoked = true;
                $token->save();
                $revoked++;
            }

            return $revoked;
        } finally {
            $this->close();
        }
    }
}
