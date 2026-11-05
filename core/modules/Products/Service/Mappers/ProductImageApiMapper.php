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

namespace App\Module\Products\Service\Mappers;

use App\Data\Entity\Record;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordFieldMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;

/**
 * Product image in the record view.
 *
 * product_image keeps the URL of the image uploaded in the legacy edit view
 * (AOS_Products::save()), which the legacy views and the PDF templates use. The record
 * view shows it with the image field (read only in the layout): the URL is sent as the
 * media object the image field shows, and back as the URL when the record is saved.
 */
class ProductImageApiMapper implements ApiRecordFieldMapperInterface
{
    public function getField(): string
    {
        return 'product_image';
    }

    public function replaceDefaultTypeMapper(): bool
    {
        return true;
    }

    public function getKey(): string
    {
        return 'product-image-api-mapper';
    }

    public function getModule(): string
    {
        return 'products';
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function getModes(): array
    {
        return ['retrieve', 'save'];
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $attributes = $record->getAttributes();
        $value = $attributes[$this->getField()] ?? null;

        if (!is_array($value)) {
            return;
        }

        $attributes[$this->getField()] = $value['attributes']['contentUrl'] ?? '';
        $record->setAttributes($attributes);
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $attributes = $record->getAttributes();
        $url = $attributes[$this->getField()] ?? '';

        if (!is_string($url) || $url === '') {
            return;
        }

        $attributes[$this->getField()] = [
            'id' => $record->getId(),
            'module' => 'media-objects',
            'attributes' => [
                'contentUrl' => $url,
                'thumbnailUrl' => '',
                'original_name' => basename((string)parse_url($url, PHP_URL_PATH)),
            ],
        ];
        $record->setAttributes($attributes);
    }
}
