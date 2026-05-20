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

namespace App\Data\Entity;

use DateTimeInterface;

trait DefaultRecordTrait
{
    public ?string $id;
    public ?string $name = null;
    public ?DateTimeInterface $dateEntered = null;
    public ?DateTimeInterface $dateModified = null;
    public ?string $modifiedUserId = null;
    public ?string $createdBy = null;
    public ?string $description = null;
    public bool $deleted = false;
    public ?string $assignedUserId = null;

    // Getters and Setters
    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDateEntered(): ?DateTimeInterface
    {
        return $this->dateEntered;
    }

    public function setDateEntered(?DateTimeInterface $date): void
    {
        $this->dateEntered = $date;
    }

    public function getDateModified(): ?DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(?DateTimeInterface $date): void
    {
        $this->dateModified = $date;
    }

    public function getModifiedUserId(): ?string
    {
        return $this->modifiedUserId;
    }

    public function setModifiedUserId(?string $modifiedUserId): void
    {
        $this->modifiedUserId = $modifiedUserId;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getAssignedUserId(): ?string
    {
        return $this->assignedUserId;
    }

    public function setAssignedUserId(?string $assignedUserId): void
    {
        $this->assignedUserId = $assignedUserId;
    }

}
