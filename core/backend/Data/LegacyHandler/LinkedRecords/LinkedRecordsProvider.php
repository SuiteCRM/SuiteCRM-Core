<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

namespace App\Data\LegacyHandler\LinkedRecords;

use App\Data\Entity\Record;
use App\Data\Service\LinkedRecords\LinkedRecordsProviderInterface;
use App\Data\Service\RecordDeletionServiceInterface;
use App\Data\Service\RecordProviderInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LinkedRecordsProvider extends LegacyHandler implements LinkedRecordsProviderInterface
{
    protected ModuleNameMapperInterface $moduleNameMapper;
    protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider;
    protected RecordProviderInterface $recordProvider;
    protected RecordDeletionServiceInterface $recordDeletionService;

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        ModuleNameMapperInterface $moduleNameMapper,
        FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        RecordProviderInterface $recordProvider,
        RecordDeletionServiceInterface $recordDeletionService
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $requestStack);
        $this->moduleNameMapper = $moduleNameMapper;
        $this->fieldDefinitionsProvider = $fieldDefinitionsProvider;
        $this->recordProvider = $recordProvider;
        $this->recordDeletionService = $recordDeletionService;
    }

    public function getHandlerKey(): string
    {
        return 'related-record-handler';
    }

    /**
     * @param Record $record
     * @param string $field
     * @return Record[]
     */
    public function getRelatedRecords(Record $record, string $field): array
    {
        $this->init();
        $definition = $this->getDefinition($record, $field);

        if (empty($definition)) {
            $this->close();
            return [];
        }

        $itemBeans = $this->getItemBeans($record, $definition);

        $relatedRecords = [];

        foreach ($itemBeans as $itemBean) {
            $attributes = $this->mapItem($itemBean);
            $itemModule = $this->moduleNameMapper->toFrontEnd($itemBean->module_name ?? '');

            $relatedRecord = new Record();
            $relatedRecord->setId($attributes['id'] ?? '');
            $relatedRecord->setModule($itemModule);
            $relatedRecord->setAttributes($attributes);

            $relatedRecords[] = $relatedRecord;
        }

        $this->close();

        return $relatedRecords;
    }

    /**
     * @param Record $parent
     * @param Record[] $records
     * @param string $linkField
     * @return void
     */
    public function syncRelatedRecords(Record $parent, array $records, string $linkField): void
    {
        $records = $records ?? [];
        $vardefs = $this->getDefinition($parent, $linkField);

        $onSyncUnlinkUnsubmitted = $vardefs['metadata']['onSyncUnlinkUnsubmitted'] ?? false;
        $onSyncUpdateExisting = $vardefs['metadata']['onSyncUpdateExisting'] ?? true;
        $relatedRecordIds = [];
        if ($onSyncUnlinkUnsubmitted) {
            $relatedRecords = $this->getRelatedRecords($parent, $linkField) ?? [];

            foreach ($relatedRecords as $record) {
                $id = $record->getId();
                if (!empty($id)) {
                    $relatedRecordIds[$id] = true;
                }
            }
        }

        $submittedRecordIds = [];
        foreach ($records as $record) {
            $attributes = $record->getAttributes();
            $isDeleted = !empty($attributes['deleted'] ?? false);
            if ($isDeleted) {
                $this->deleteRecord($record);
                $this->unlinkRecord($parent, $record, $linkField);
                $submittedRecordIds[$record->getId()] = true;
                continue;
            }

            $id = $record->getId();
            $isNew = empty($id);

            if ($isNew || $onSyncUpdateExisting) {
                $savedRecord = $this->saveRecord($record);
            }


            if ($isNew) {
                $this->linkRecord($parent, $savedRecord, $linkField);
                $submittedRecordIds[$savedRecord->getId()] = true;
                continue;
            }

            $submittedRecordIds[$record->getId()] = true;

            if (empty($relatedRecordIds[$id])) {
                $this->linkRecord($parent, $record, $linkField);
            }
        }

        if ($onSyncUnlinkUnsubmitted) {
            foreach ($relatedRecords as $record) {
                $id = $record->getId();
                if (empty($submittedRecordIds[$id])) {
                    $this->unlinkRecord($parent, $record, $linkField);
                }
            }
        }
    }

    public function unlinkRecord(Record $parent, Record $record, string $linkField): void
    {
        $this->init();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/Relationships/UnlinkService.php';
        $baseModule = $this->moduleNameMapper->toLegacy($parent->getModule());

        $service = new \UnlinkService();

        $service->run($baseModule, $parent->getId(), $linkField, $record->getId());
        $this->close();
    }

    public function linkRecord(Record $parent, Record $record, string $linkField): void
    {
        $this->init();
        $parentModule = $this->moduleNameMapper->toLegacy($parent->getModule());
        $bean = \BeanFactory::getBean($parentModule, $parent->getId());

        if (!$bean->load_relationship($linkField)) {
            return;
        }

        $bean->$linkField->add([$record->getId()]);

        $this->close();
    }

    public function linkRecordIds(Record $parent, array $recordsIds, string $linkField): void
    {
        $this->init();
        $parentModule = $this->moduleNameMapper->toLegacy($parent->getModule());
        $bean = \BeanFactory::getBean($parentModule, $parent->getId());

        if (!$bean->load_relationship($linkField)) {
            return;
        }

        if (empty($recordsIds)) {
            return;
        }

        $bean->$linkField->add([$recordsIds]);

        $this->close();
    }

    protected function saveRecord(Record $record): Record
    {
        return $this->recordProvider->saveRecord($record);
    }

    protected function deleteRecord(Record $record): void
    {
        $this->recordDeletionService->delete($record);
    }

    /**
     * @param Record $record
     * @param array $definition
     * @return array
     */
    protected function getItemBeans(Record $record, array $definition): array
    {
        $bean = \BeanFactory::getBean($this->moduleNameMapper->toLegacy($record->getModule()), $record->getId());
        $relationship = $definition['link'] ?? $definition['relationship'] ?? false;
        $linkName = $definition['link'] ?? $definition['name'] ?? false;

        if (!$bean->load_relationship($relationship)) {
            return [];
        }

        /** @var \Link2 $link */
        $link = $bean->$linkName;

        if (empty($link)) {
            return [];
        }

        return $link->getBeans();
    }

    /**
     * @param \SugarBean $itemBean
     * @return array
     */
    protected function mapItem(\SugarBean $itemBean): array
    {
        return (new \ApiBeanMapper())->toApi($itemBean);
    }

    /**
     * @param Record $record
     * @param string $field
     * @return array|null
     */
    protected function getDefinition(Record $record, string $field): ?array
    {
        $definition = $this->fieldDefinitionsProvider->getVardef($record->getModule());
        $vardefs = $definition->getVardef() ?? [];
        return $vardefs[$field] ?? null;
    }


}
