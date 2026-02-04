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


namespace App\Module\Emails\Service\ModalHeaderActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\EmailMarketing\Service\Actions\DeleteTestMailMarketingEntriesService;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CloseDraftAction extends LegacyHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'modal-close-draft-email';

    public function __construct(
        protected array $draftsConfigs,
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected DeleteTestMailMarketingEntriesService $deleteTestEntriesService,
        protected RecordProviderInterface $recordProvider,
        protected ModuleNameMapperInterface $moduleNameMapper,
    )
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
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
        $options = $process->getOptions();
        $id = $options['id'] ?? null;
        $module = $options['module'] ?? null;

        if (!empty($id)) {
            return [
                $module => [
                    [
                        'action' => 'edit',
                        'record' => $id
                    ],
                ],
            ];
        }

        return [
            $module => [
                ['action' => 'edit'],
            ],
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
     * @throws InvalidArgumentException
     */
    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $module = $options['module'] ?? '';

        if (empty($module)) {
            throw new InvalidArgumentException('Process option "module" is not defined');
        }

        if ($module !== 'emails'){
            throw new InvalidArgumentException('Module is not supported for draft saving');
        }

    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $record = $options['record'] ?? null;
        $attributes = $record['attributes'] ?? [];

        if (!empty($record['id'])) {
            $newRecord = $this->mapToRecord($record['module'] ?? '', $attributes, $record['id']);
            $this->recordProvider->saveRecord($newRecord);
            $process->setStatus('success');
            $process->setMessages(['LBL_EMAIL_DRAFT_SAVED']);
            return;
        }

        $shouldSave = $this->shouldSaveDraft($attributes);

        if (!$shouldSave) {
            $process->setStatus('success');
            return;
        }

        $attributes['status'] = 'draft';
        $attributes['type'] = 'draft';

        $module = $options['module'] ?? '';

        $record = $this->mapToRecord($module, $attributes);

        $this->recordProvider->saveRecord($record);

        $data = [
            'handler' => 'emit-event',
            'params' => [
                'event' => 'refresh-drafts',
                'payload' => true,
            ],
        ];

        $process->setStatus('success');
        $process->setMessages(['LBL_EMAIL_DRAFT_SAVED']);
        $process->setData($data);
    }

    /**
     * @throws \Exception
     */
    protected function shouldSaveDraft(array $attributes): bool
    {
        if (!empty($attributes['name'])) {
            return true;
        }

        if (!$this->isEmptyDescription($attributes)) {
            return true;
        }

        if (!empty($attributes['email_attachments'])) {
            return true;
        }

        return false;
    }

    protected function mapToRecord(string $module, array $attributes, $id = null): Record
    {
        $record = new Record();
        $record->setModule($module);
        $record->setId($id);

        if (!isset($attributes['id'])){
            $attributes['id'] = $id;
        }

        $record->setAttributes($attributes);

        return $record;
    }

    /**
     * @throws \Exception
     */
    protected function isEmptyDescription(array $attributes): bool
    {
        $outboundEmail = $this->recordProvider->getRecord('OutboundEmailAccounts', $attributes['outbound_email_id'] ?? '');

        $signature = $outboundEmail->getAttributes()['signature'] ?? '';
        $trimmedSignature = $this->stripString($signature);

        $description = $attributes['description_html'] ?? '';
        $trimmedDescription = $this->stripString($description);

        if ($trimmedSignature === $trimmedDescription){
            return true;
        }

        return false;
    }

    protected function stripString(string $value): string
    {
        return trim(strip_tags(html_entity_decode($value, ENT_QUOTES)));
    }
}
