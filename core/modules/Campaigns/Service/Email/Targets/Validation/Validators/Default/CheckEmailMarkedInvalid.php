<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Module\Campaigns\Service\Email\Targets\Validation\Validators\Default;

use App\Data\Entity\Record;
use App\Emails\Service\Validation\EmailValidationHelper;
use App\Module\Campaigns\Service\Email\Targets\Validation\BaseEmailTargetValidator;
use App\Module\Campaigns\Service\Email\Targets\Validation\ValidationFeedback;

class CheckEmailMarkedInvalid extends BaseEmailTargetValidator
{

    public function __construct(protected EmailValidationHelper $helper)
    {
    }

    public function getKey(): string
    {
        return 'email-marked-invalid';
    }

    public function getPriority(): int
    {
        return 20;
    }

    public function applies(
        Record $targetRecord,
        Record $marketingRecord,
        string $campaignId,
        string $prospectListId
    ): bool
    {
        return true;
    }

    public function validate(
        Record $targetRecord,
        Record $marketingRecord,
        string $campaignId,
        string $prospectListId
    ): ValidationFeedback {

        $isMarkedAsInValid = $this->helper->isEmailMarkedInvalid($targetRecord->getAttributes()['email1'] ?? '');

        if ($isMarkedAsInValid) {
            return $this->buildFailureFeedback();
        }

        return $this->buildSuccessFeedback();
    }
}
