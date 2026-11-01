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

namespace App\Module\Users\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Users\LegacyHandler\UserProfileHandler;

/**
 * Saves the preferences, type and password of the user record view.
 *
 * The legacy User::save() saves the preferences and the type from the posted form
 * (User::saveFormPreferences()): before the save, the profile fields are given to it as the
 * inputs of that form, then removed from $_POST after the save. The new password is checked
 * before the save and set after it (User::change_password()).
 */
class UserProfileSaveHandler implements RecordSaveHandlerInterface
{
    /**
     * Form inputs added to $_POST for the save in progress.
     */
    protected array $postedInputs = [];

    /**
     * Password change of the save in progress: [old, new], or null.
     */
    protected ?array $passwordChange = null;

    public function __construct(protected UserProfileHandler $handler)
    {
    }

    public function getKey(): string
    {
        return 'user-profile';
    }

    public function getModule(): string
    {
        return 'users';
    }

    public function getModes(): array
    {
        return ['before-save', 'after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        if ($savedRecord === null) {
            $this->beforeSave($previousVersion, $inputRecord);

            return;
        }

        foreach (array_keys($this->postedInputs) as $input) {
            unset($_POST[$input], $_REQUEST[$input]);
        }
        $this->postedInputs = [];

        if ($this->passwordChange !== null) {
            [$old, $new] = $this->passwordChange;
            $this->passwordChange = null;
            $this->handler->changePassword((string)$savedRecord->getId(), $old, $new);
        }
    }

    protected function beforeSave(?Record $previousVersion, Record $inputRecord): void
    {
        $this->postedInputs = [];
        $this->passwordChange = null;

        $attributes = $inputRecord->getAttributes() ?? [];
        $profileFields = array_merge(
            array_keys(UserProfileHandler::PREFERENCES),
            UserProfileHandler::PASSWORD_FIELDS,
            [UserProfileHandler::USER_TYPE_FIELD, UserProfileHandler::NEW_USER_TYPE_FIELD]
        );

        $values = array_intersect_key($attributes, array_flip($profileFields));
        $attributes = array_diff_key($attributes, array_flip($profileFields));

        // A group user (legacy usertype=group) has no password and no preferences.
        if ($previousVersion === null && ($values[UserProfileHandler::NEW_USER_TYPE_FIELD] ?? '') === 'group') {
            $attributes['is_group'] = 1;
            $inputRecord->setAttributes($attributes);

            return;
        }
        $inputRecord->setAttributes($attributes);

        // Some inputs are checked in $_POST and read from $_REQUEST (email_link_type, editor_type).
        $this->postedInputs = $this->handler->toFormInputs($values);
        foreach ($this->postedInputs as $input => $value) {
            $_POST[$input] = $value;
            $_REQUEST[$input] = $value;
        }

        $new = (string)($values['new_password'] ?? '');
        if ($new === '') {
            if ($previousVersion === null) {
                $this->handler->checkNewUserPassword($attributes);
            }

            return;
        }

        $old = (string)($values['old_password'] ?? '');
        $this->handler->validatePassword(
            (string)($previousVersion?->getId() ?? ''),
            (string)($attributes['user_name'] ?? ''),
            $old,
            $new,
            (string)($values['confirm_new_password'] ?? '')
        );
        $this->passwordChange = [$old, $new];
    }
}
