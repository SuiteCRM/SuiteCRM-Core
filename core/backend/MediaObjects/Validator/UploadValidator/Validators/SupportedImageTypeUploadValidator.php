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

class SupportedImageTypeUploadValidator implements UploadValidatorInterface
{
    public function __construct(
        protected SystemConfigProviderInterface $config
    ) {
    }

    public function getKey(): string
    {
        return 'supported-image-type-upload-validator';
    }

    public function getStorageType(): string
    {
        return 'default';
    }

    public function getErrorMessageLabelKey(): string
    {
        return 'LBL_UNSUPPORTED_IMAGE_TYPE';
    }

    public function validate($value, string $storageType, Constraint $constraint, ExecutionContextInterface $context): void
    {

        if (!$value || !$value instanceof File) {
            return;
        }

        $mimeType = $value->getMimeType();
        if (!str_starts_with($mimeType, 'image/')) {
            return;
        }

        $supportedTypes = $this->config->getConfigs()['valid_image_ext'] ?? null;

        if (empty($supportedTypes) || !is_array($supportedTypes)) {
            $supportedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        }

        $fileExtension = strtolower($value->getClientOriginalExtension());
        if (!in_array($fileExtension, $supportedTypes)) {
            $context->buildViolation($this->getErrorMessageLabelKey())
                    ->addViolation();
        }
    }
}
