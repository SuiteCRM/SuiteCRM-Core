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

require_once __DIR__ . '/../../ApiBeanModuleMappers.php';
require_once __DIR__ . '/../../FieldMappers/FieldMapperInterface.php';

/**
 * The values of an inbound email account in the SuiteCRM 8 record view that the legacy
 * detail view computes (function fields of modules/InboundEmail/utils.php): the case distribution
 * options (stored as a list, that the core mapper cannot show) and the signature name.
 */
class InboundEmailMappers extends ApiBeanModuleMappers
{
    public const MODULE = 'InboundEmail';

    public function __construct()
    {
        $this->fieldMappers['distribution_options'] = new InboundEmailDetailMapper('distribution_options');
        $this->fieldMappers['account_signature_id'] = new InboundEmailDetailMapper('account_signature_id');
    }

    public static function getModule(): string
    {
        return self::MODULE;
    }
}

/**
 * A field of an inbound email account shown as the legacy detail view shows it (read only: the
 * account is saved by the legacy Save.php).
 */
class InboundEmailDetailMapper implements FieldMapperInterface
{
    public function __construct(protected string $field)
    {
    }

    public static function getField(): string
    {
        return '';
    }

    public function toApi(SugarBean $bean, array &$container, string $alternativeName = ''): void
    {
        require_once 'modules/AOP_Case_Updates/util.php';
        require_once 'modules/InboundEmail/utils.php';

        $name = $alternativeName !== '' ? $alternativeName : $this->field;
        $value = $this->field === 'distribution_options'
            ? getAOPAssignFieldDetailView($bean->distribution_options ?? '')
            : getUserSignature($bean, 'account_signature_id', '', 'DetailView');
        $container[$name] = html_entity_decode((string)$value, ENT_QUOTES);
    }

    public function toBean(SugarBean $bean, array &$container, string $alternativeName = ''): void
    {
    }
}
