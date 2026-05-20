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

namespace App\Module\EmailMarketing\LegacyHandler\Validation\Validators;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class UnsubscribeValidator implements ProcessHandlerInterface
{

    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const MSG_VALUE_NOT_DEFINED = 'Field Value is not defined';
    protected const PROCESS_TYPE = 'unsubscribe-link-validation';

    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    )
    {
    }

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        if (!isset($process->getOptions()['value'])){
            throw new InvalidArgumentException(self::MSG_VALUE_NOT_DEFINED);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        $value = $options['value'] ?? '';
        $attributes = $options['attributes'] ?? [];

        $trackers = $this->getTrackers();

        $data = [
          'errors' => []
        ];

        if ($attributes['type'] === 'transactional') {

            if ($this->containsTrackers($value, $trackers)){
                $process->setStatus('error');
                $data['errors'] = [
                  'startLabelKey' => 'LBL_VALIDATION_ERROR_REMOVE_UNSUBSCRIBE_LINK'
                ];
                $process->setData($data);
                return;
            }

            $process->setStatus('success');
            return;
        }

        if (!$this->containsTrackers($value, $trackers)){
            $process->setStatus('error');
            $data = [
                'errors' => [
                    'startLabelKey' => 'LBL_VALIDATION_ERROR_UNSUBSCRIBE_LINK',
                    'icon' => 'unsubscribe'
                ]
            ];
            $process->setData($data);
            return;
        }

        $process->setStatus('success');
    }

    /**
     * @param string $value
     * @param string $name
     * @return bool
     */
    protected function containsTrackerVariable(string $value, string $name): bool
    {
        return (bool)(preg_match("/{{\s*$name\s*}}/", $value) || preg_match("/%7B%7B\s*$name\s*%7D%7D/", $value));
    }

    protected function getTrackers(): array
    {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $trackers = [];

        try {
            $trackers = $queryBuilder
                ->select('*')
                ->from('campaign_trkrs', 'trk')
                ->where('trk.deleted = 0')
                ->andWhere('trk.is_optout = 1')
                ->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error('Unable to get Trackers for this campaign.');
        }

        return $trackers;
    }

    protected function containsTrackers(string $value, array $trackers): bool
    {
        if ($this->containsTrackerVariable($value, 'unsubscribe_link')) {
            return true;
        }

        foreach ($trackers as $tracker) {
            if ($this->containsTrackerVariable($value, $tracker['tracker_name'])) {
                return true;
            }
        }

        return false;
    }
}
