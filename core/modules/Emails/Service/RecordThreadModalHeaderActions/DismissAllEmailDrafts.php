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


namespace App\Module\Emails\Service\RecordThreadModalHeaderActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Authentication\LegacyHandler\UserHandler;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordDeletionServiceInterface;
use App\Data\Service\RecordProviderInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Doctrine\DBAL\ArrayParameterType;
use Psr\Log\LoggerInterface;

class DismissAllEmailDrafts implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-thread-modal-dismiss-all-drafts';

    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected RecordDeletionServiceInterface $recordDeleteHandler,
        protected UserHandler $userHandler,
        protected LoggerInterface $logger,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        $module = 'Emails';
        return [
            $module => [
                'action' => 'delete',
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * @inheritDoc
     *
     */
    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $criteria = $options['criteria'] ?? null;

        if (empty($criteria)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function run(Process $process): void
    {

        $options = $process->getOptions();
        $criteria = $options['criteria'] ?? null;
        $ids = $this->getIdsToExclude($criteria);

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('emails')
            ->where('type = :type')
            ->andWhere('status = :status')
            ->andWhere('created_by = :created_by')
            ->andWhere('deleted = 0')
            ->setParameters([
                'type' => 'draft',
                'status' => 'draft',
                'created_by' => $this->userHandler->getCurrentUser()?->id,
            ]);

        if (!empty($ids)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->notIn('id', ':ids'))
                ->setParameter('ids', $ids, ArrayParameterType::STRING);
        }

        $result = $queryBuilder->fetchAllAssociative();

        foreach ($result as $row) {
            $deleted = $this->recordDeleteHandler->deleteRecord('Emails', $row['id']);
            if (!$deleted) {
                $this->logger->error("Failed to delete draft email with ID: " . $row['id']);
                $result = false;
            }
        }

        if (!$result) {
            $process->setStatus('error');
            $process->setMessages(['LBL_DRAFTS_DELETED_UNSUCCESSFULLY']);

            return;
        }

        $process->setStatus('success');
        $process->setMessages(['LBL_DRAFTS_DELETED_SUCCESSFULLY']);

        $responseData = [
            'reloadThread' => true,
        ];

        $process->setData($responseData);
    }

    protected function getIdsToExclude(array $criteria)
    {
        $ids = [];

        foreach ($criteria['filters'] as $filter) {
            if ($filter['field'] === 'id' && $filter['operator'] === 'not_in') {
                $ids = $filter['values'];
                break;
            }
        }

        return $ids;
    }
}
