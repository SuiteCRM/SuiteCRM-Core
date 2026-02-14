<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 */

namespace App\Data\Service\Record\RecordSaveHandlers;

use App\Authentication\LegacyHandler\UserHandler;
use App\Data\Entity\Record;
use App\FieldDefinitions\Entity\FieldDefinition;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmailTemplatesSystemTypeGuardSaveHandler implements RecordSaveHandlerInterface
{
    public function __construct(
        protected UserHandler $userHandler
    ) {
    }

    public function getKey(): string
    {
        return 'email-templates-system-type-guard';
    }

    public function getModule(): string
    {
        return 'email-templates';
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function getModes(): array
    {
        return ['before-save'];
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        if ($this->userHandler->isCurrentUserAdmin()) {
            return;
        }

        $previousType = $previousVersion?->getAttributes()['type'] ?? null;
        if ($previousType === 'system') {
            throw new AccessDeniedHttpException('Only administrators can modify system email templates.');
        }

        $newType = $inputRecord->getAttributes()['type'] ?? null;
        if ($newType === 'system') {
            throw new AccessDeniedHttpException('Only administrators can create system email templates.');
        }
    }
}
