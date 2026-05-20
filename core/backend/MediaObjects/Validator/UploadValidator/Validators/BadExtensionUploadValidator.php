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

namespace App\MediaObjects\Validator\UploadValidator\Validators;

use App\MediaObjects\Validator\UploadValidator\UploadValidatorInterface;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BadExtensionUploadValidator implements UploadValidatorInterface
{
    public function __construct(
        protected SystemConfigProviderInterface $config
    ) {
    }

    public function getKey(): string
    {
        return 'bad-extension-upload-validator';
    }

    public function getStorageType(): string
    {
        return 'default';
    }

    public function getErrorMessageLabelKey(): string
    {
        return 'LBL_UNSUPPORTED_FILE_TYPE';
    }

    public function validate($value, string $storageType, Constraint $constraint, ExecutionContextInterface $context): void
    {

        if (!$value || !$value instanceof File) {
            return;
        }

        $notSupportedTypes = $this->config->getConfigs()['upload_badext'] ?? null;

        if (empty($notSupportedTypes) || !is_array($notSupportedTypes)) {
            $notSupportedTypes = [
                'php',
                'php3',
                'php4',
                'php5',
                'php6',
                'php7',
                'php8',
                'pl',
                'cgi',
                'py',
                'asp',
                'cfm',
                'js',
                'vbs',
                'html',
                'htm',
                'phtml',
                'phar',
                'exe',
                'bat',
                'cmd',
                'sh'
            ];
        }

        $fileExtension = strtolower($value->getClientOriginalExtension());
        if (in_array($fileExtension, $notSupportedTypes)) {
            $context->buildViolation($this->getErrorMessageLabelKey())
                    ->addViolation();
        }
    }
}
