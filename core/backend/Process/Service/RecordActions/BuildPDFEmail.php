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

namespace App\Process\Service\RecordActions;

use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;

abstract class BuildPDFEmail
{

    /**
     * BuildPDFEmail constructor.
     */
    public function __construct(
        protected RecordProviderInterface $recordProvider,
    )
    {
    }

    abstract protected function getToFieldKeys(string $module = ''): array;

    /**
     * @throws \Exception
     */
    protected function calculateToField(Record $record): array
    {
        $keys = $this->getToFieldKeys($record->getModule());
        $contact = $this->getRelatedRecordId($record, $keys['contactKey']);
        if ($contact) {
            return $this->buildToField('Contacts', $contact);
        }

        $account = $this->getRelatedRecordId($record, $keys['contactKey']);
        if ($account) {
            return $this->buildToField('Accounts', $account);
        }

        return $this->buildToField($record->getModule(), $record->getId());
    }

    /**
     * @throws \Exception
     */
    protected function buildToField($module, $id): array
    {
        $record = $this->recordProvider->getRecord($module, $id);

        if ($record === null) {
            return [];
        }

        $email = $record->getAttributes()['email1'] ?? $record->getAttributes()['email'] ?? '';

        if (empty($email)) {
            return [];
        }

        return [
            'id' => $record->getId(),
            'name' => $record->getAttributes()['name'] ?? '',
            'email1' => $email,
            'module_name' => $module,
        ];
    }

    protected function getRelatedRecordId(Record $record, string $key): ?string
    {
        if (!isset($record->getAttributes()[$key])) {
            return null;
        }

        if (!isset($record->getAttributes()[$key]['id'])) {
            return null;
        }

        return $record->getAttributes()[$key]['id'] ?? null;
    }

    protected function getEmailBaseOptions(): array
    {
        return [
            'module' => 'emails',
            'metadataView' => 'modalComposeView',
            'detached' => true,
            'headerClass' => 'left-aligned-title',
            'dynamicTitleKey' => 'LBL_EMAIL_MODAL_DYNAMIC_TITLE',
            'modalOptions' => [
                'size' => 'lg',
                'scrollable' => true,
            ],
        ];
    }
}
