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

namespace App\Data\EventSubscriber;

use App\Authentication\LegacyHandler\UserHandler;
use App\Data\Entity\DefaultRecordInterface;
use Closure;
use DateTimeInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class RecordInitDoctrineEventListener
{

    public function __construct(protected UserHandler $userHandler)
    {
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        if (!($entity instanceof DefaultRecordInterface)) {
            return;
        }

        $this->initRecordEntity($entity);
    }

    protected function initDefaultDateTime(DefaultRecordInterface $entity, Closure $getValue, Closure $setValue): void
    {
        $value = $getValue($entity);

        if (empty($value)) {
            $setValue(new \DateTime());
        }
    }

    protected function initDefaultUser(DefaultRecordInterface $entity, Closure $getValue, Closure $setValue): void
    {
        $value = $getValue($entity);
        $user = $this->userHandler->getCurrentUser();

        if (empty($value) && !empty($user) && !empty($user->id)) {

            $setValue($user->id);
        }
    }

    /**
     * @param DefaultRecordInterface $entity
     * @return void
     */
    protected function initRecordEntity(DefaultRecordInterface $entity): void
    {
        $this->initDefaultDateTime(
            $entity,
            static function () use ($entity): ?DateTimeInterface {
                return $entity->getDateEntered();
            },
            static function ($value) use ($entity): void {
                $entity->setDateEntered($value);
            }
        );

        $this->initDefaultDateTime(
            $entity,
            static function () use ($entity): ?DateTimeInterface {
                return $entity->getDateModified();
            },
            static function ($value) use ($entity): void {
                $entity->setDateModified($value);
            }
        );

        $this->initDefaultUser(
            $entity,
            static function () use ($entity): ?string {
                return $entity->getCreatedBy();
            },
            static function ($value) use ($entity): void {
                $entity->setCreatedBy($value);
            }
        );

        $this->initDefaultUser(
            $entity,
            static function () use ($entity): ?string {
                return $entity->getModifiedUserId();
            },
            static function ($value) use ($entity): void {
                $entity->setModifiedUserId($value);
            }
        );

        $this->initDefaultUser(
            $entity,
            static function () use ($entity): ?string {
                return $entity->getAssignedUserId();
            },
            static function ($value) use ($entity): void {
                $entity->setAssignedUserId($value);
            }
        );
    }


}



