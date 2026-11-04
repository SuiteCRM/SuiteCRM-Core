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

namespace App\Module\SavedSearch\Service\ProcessHandlers;

use App\Module\SavedSearch\LegacyHandler\SavedSearchHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * `saved-search` process of the saved filter view (SavedSearchHandler): for the admins and the
 * user of the filter.
 *
 * Options, by action:
 * - load: id;
 * - save: id, name, userId (admins), quickFilter (bool);
 * - delete: id.
 */
class SavedSearchProcessHandler implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'saved-search';

    protected const ACTIONS = ['load', 'save', 'delete'];

    public function __construct(protected SavedSearchHandler $savedSearches, protected LoggerInterface $logger)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        return [];
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
     */
    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)
            || empty($options['id']) || !is_string($options['id'])) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = $options['id'];

        try {
            $data = match ($options['action']) {
                'save' => $this->savedSearches->save(
                    $id,
                    (string)($options['name'] ?? ''),
                    (string)($options['userId'] ?? ''),
                    !empty($options['quickFilter'])
                ),
                'delete' => $this->delete($id),
                default => $this->savedSearches->load($id),
            };
            $process->setStatus('success');
            $process->setMessages([]);
            $process->setData($data);
        } catch (HttpExceptionInterface $exception) {
            $process->setStatus('error');
            $process->setMessages([$exception->getStatusCode() === 404 ? 'LBL_RECORD_NOT_FOUND' : 'LBL_ACCESS_DENIED']);
            $process->setData([]);
        } catch (\Throwable $throwable) {
            $this->logger->error('Saved filter ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
            $process->setData([]);
        }
    }

    protected function delete(string $id): array
    {
        $this->savedSearches->delete($id);

        return ['deleted' => true];
    }
}
