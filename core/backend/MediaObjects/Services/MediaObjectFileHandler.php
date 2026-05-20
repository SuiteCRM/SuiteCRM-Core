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

namespace App\MediaObjects\Services;

use Vich\UploaderBundle\Exception\NoFileFoundException;
use Vich\UploaderBundle\Handler\AbstractHandler;

class MediaObjectFileHandler extends AbstractHandler
{

    /**
     * Retrieves the file stream, filename and mime type for the given object and field.
     *
     * @param object|array $object The object or array containing the file field.
     * @param string $field The field name where the file is stored.
     * @param string|null $className The class name of the object, if $object is an array.
     * @param string|null $fileName The desired filename for the download. If true, uses the original name.
     *
     * @return array An array containing 'stream', 'fileName', and 'mimeType'.
     *
     * @throws NoFileFoundException If no file is found in the specified field.
     */
    public function getObjectStream (
        object|array $object,
        string $field,
        ?string $className = null,
        string|null $fileName = null
    ): array
    {
        $stream = $this->storage->resolveStream($object, $field, $className);

        if (null === $stream) {
            throw new NoFileFoundException(\sprintf('No file found in field "%s".', $field));
        }

        if (null === $fileName) {
            $fileName = $object->originalName;
        }

        $mimeType = $object->mimeType ?? null;

        $metadata = stream_get_meta_data($stream);

        return [
            'stream' => $stream,
            'fileName' => $fileName,
            'mimeType' => $mimeType,
            'uri' => $metadata['uri'] ?? null,
        ];
    }
}
