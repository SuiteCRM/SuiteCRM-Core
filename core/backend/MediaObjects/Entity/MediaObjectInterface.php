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

namespace App\MediaObjects\Entity;

use App\Data\Entity\DefaultRecordInterface;
use Symfony\Component\HttpFoundation\File\File;

interface MediaObjectInterface extends DefaultRecordInterface
{
    public function getContentUrl(): ?string;

    public function setContentUrl(?string $contentUrl): void;

    public function getFile(): ?File;

    public function setFile(?File $file): void;

    public function getFilePath(): ?string;

    public function setFilePath(?string $filePath): void;

    public function getSize(): ?int;

    public function setSize(?int $size): void;

    public function getMimeType(): ?string;

    public function setMimeType(?string $mimeType): void;

    public function getOriginalName(): ?string;

    public function setOriginalName(?string $originalName): void;

    public function getDimensions(): ?array;

    public function setDimensions(?array $dimensions): void;

    public function getParentType(): ?string;

    public function setParentType(?string $parentType): void;

    public function getParentId(): ?string;

    public function setParentId(?string $parentId): void;

    public function getParentField(): ?string;

    public function setParentField(?string $parentField): void;

    public function getTemporary(): ?bool;

    public function setTemporary(?bool $temporary): void;
}
