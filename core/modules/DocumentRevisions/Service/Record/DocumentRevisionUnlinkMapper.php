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
namespace App\Module\DocumentRevisions\Service\Record;

use App\Data\Entity\Record;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Documents\LegacyHandler\DocumentsManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class DocumentRevisionUnlinkMapper implements ApiRecordMapperInterface
{

    public function __construct(
        protected DocumentsManagerInterface $documentsManager,
    )
    {
    }

    public function getModule(): string
    {
        return 'document-revisions';
    }

    public function getKey(): string
    {
        return 'document-revision-unlink-mapper';
    }

    public function getModes(): array
    {
        return ['list', 'retrieve'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $attributes = $record->getAttributes();
        $documentId = $attributes['document_id'] ?? null;
        if (!isset($documentId)) {
            return;
        }

        $latestRevisionId = $this->documentsManager->getLatestRevisionId($documentId);

        if ($attributes['id'] !== $latestRevisionId) {
            return;
        }

        $acls = $record->getAcls();

        $acls = array_filter($acls, static fn($value) => $value !== 'edit');

        $record->setAcls($acls);
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
    }
}
