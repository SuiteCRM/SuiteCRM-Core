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

namespace App\EmailTemplates\FieldDefinitions;

use App\Authentication\LegacyHandler\UserHandler;
use App\FieldDefinitions\Service\VardefConfigMapperInterface;

class EmailTemplatesTypeOptionsVardefMapper implements VardefConfigMapperInterface
{
    public function __construct(
        protected UserHandler $userHandler,
    ) {
    }

    public function getKey(): string
    {
        return 'email-templates-type-options';
    }

    public function getModule(): string
    {
        return 'email-templates';
    }

    public function map(array $vardefs): array
    {
        // Keep system templates available for admins only.
        if ($this->userHandler->isCurrentUserAdmin()) {
            return $vardefs;
        }

        if (($vardefs['type']['options'] ?? '') === 'emailTemplates_type_list_no_workflow') {
            $vardefs['type']['options'] = 'emailTemplates_type_list';
        }

        return $vardefs;
    }
}
