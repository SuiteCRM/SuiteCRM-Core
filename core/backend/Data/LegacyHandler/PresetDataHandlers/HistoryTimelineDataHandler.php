<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2021 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
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


namespace App\Data\LegacyHandler\PresetDataHandlers;

use App\Data\LegacyHandler\ListData;
use App\Data\LegacyHandler\PresetListDataHandlerInterface;
use App\Data\LegacyHandler\RecordMapper;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Statistics\StatisticsHandlingTrait;
use BadMethodCallException;
use BeanFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use SubpanelDataPort;
use SugarBean;
use Symfony\Component\HttpFoundation\RequestStack;
use User;

class HistoryTimelineDataHandler extends SubpanelDataQueryHandler implements PresetListDataHandlerInterface, LoggerAwareInterface
{
    use StatisticsHandlingTrait;

    public const HANDLER_KEY = 'history-timeline-data-handlers';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RecordMapper
     */
    private $recordMapper;

    /**
     * @var FieldDefinitionsProviderInterface
     */
    private $fieldDefinitionProvider;

    /**
     * HistoryTimelineDataHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session,
        RecordMapper $recordMapper,
        FieldDefinitionsProviderInterface $fieldDefinitionProvider
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState,
            $moduleNameMapper, $session);
        $this->recordMapper = $recordMapper;
        $this->fieldDefinitionProvider = $fieldDefinitionProvider;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'history-timeline';
    }

    /**
     * @inheritDoc
     */
    public function fetch(
        string $module,
        array $criteria = [],
        int $offset = -1,
        int $limit = -1,
        array $sort = []
    ): ListData {
        $this->init();
        $this->startLegacyApp();
        global $current_language, $app_strings;

        $parentModule = $criteria['preset']['params']['parentModule'] ?? '';
        $parentId = $criteria['preset']['params']['parentId'] ?? '';

        $selectModule = 'history';

        $selectOffset = $criteria['preset']['params']['offset'] ?? '';
        $selectLimit = $criteria['preset']['params']['limit'] ?? '';

        $sort['orderBy'] = 'date_end';
        $sort['sortOrder'] = 'DESC';

        if ($parentModule) {
            $parentModule = $this->moduleNameMapper->toLegacy($parentModule);
        }
        if (empty($parentModule)) {
            throw new BadMethodCallException('Parent Module not defined!');
        }

        $legacyParentModule = $this->moduleNameMapper->toLegacy($parentModule);

        $this->initController($parentModule, $parentId);

        $parentBean = BeanFactory::getBean($parentModule, $parentId);

        $unionQueryColumns =
            [
                'tasks' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [],
                    "date_entered" => [],
                    "date_due" => [
                        "alias" => 'date_end',
                        "sort_by" => 'date_end'
                    ]
                ],
                'tasks_parent' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [],
                    "date_entered" => [],
                    "date_due" => [
                        "alias" => 'date_end',
                        "sort_by" => 'date_end'
                    ]
                ],
                'meetings' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [],
                    "date_entered" => [],
                    "date_end" => [],
                ],
                'oldmeetings' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [],
                    "date_entered" => [],
                    "date_end" => [],
                ],
                'calls' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [],
                    "date_entered" => [],
                    "date_end" => [],
                ],
                'oldcalls' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [],
                    "date_entered" => [],
                    "date_end" => [],
                ],
                'notes' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [],
                    "date_entered" => [

                        "alias" => 'date_end',
                        "sort_by" => 'date_end'
                    ],
                    "date_due" => [],
                ],
                'emails' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [
                        "alias" => 'date_end',
                        "sort_by" => 'date_end'
                    ],
                    "date_entered" => [],
                    "date_due" => [],
                ],
                'linkedemails' => [
                    "name" => [],
                    "status" => [],
                    "date_modified" => [
                        "alias" => 'date_end',
                        "sort_by" => 'date_end'
                    ],
                    "date_entered" => [],
                    "date_due" => [],
                ],
            ];

        $panelToModuleName = [
            'tasks' => 'Tasks',
            'tasks_parent' => 'Tasks',
            'meetings' => 'Meetings',
            'oldmeetings' => 'Meetings',
            'calls' => 'Calls',
            'oldcalls' => 'Calls',
            'notes' => 'Notes',
            'emails' => 'Emails',
            'linkedemails' => 'Emails',
            'audit' => 'audit'
        ];

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Subpanels/SubpanelDataPort.php';

        $historyUnionQueryParams = (new SubpanelDataPort())->fetchFinalQuery(
            $parentBean,
            $selectModule,
            -1,
            -1,
            '',
            '',
            $unionQueryColumns
        );

        $activitiesUnionQueryParams = (new SubpanelDataPort())->fetchFinalQuery(
            $parentBean,
            'activities',
            -1,
            -1,
            '',
            '',
            $unionQueryColumns
        );

        if ($historyUnionQueryParams === null || $activitiesUnionQueryParams === null) {
            $timelineEntryData = new ListData();
            $timelineEntryData->setOffsets([]);
            $timelineEntryData->setOrdering([]);
            $timelineEntryData->setRecords($this->recordMapper->mapRecords([]));
            return $timelineEntryData;
        }

        $historyUnionQuery = $historyUnionQueryParams[1];
        $activitiesUnionQuery = $activitiesUnionQueryParams[1];
        $auditUnionQuery = $this->queryAuditInfo($parentBean);
        $combinedQuery = $historyUnionQuery .
            ' UNION ALL ' . $activitiesUnionQuery .
            ' UNION ALL (' . $auditUnionQuery . ')' .
            ' ORDER BY ' . $sort['orderBy'] . ' ' . $sort['sortOrder'] . ' Limit ' . $selectOffset . ', ' . $selectLimit;

        $listData = $this->fetchAll($combinedQuery);

        /** Get Audit Data */
        // Required to get Language Label for the Audited Column Name
        $fieldDefinition = $this->fieldDefinitionProvider->getVardef($parentModule);
        $vardefs = $fieldDefinition->getVardef();

        $mod_strings = return_module_language($current_language, $legacyParentModule);

        foreach ($listData as $key => $record) {
            if ($listData[$key]['panel_name'] === 'audit') {
                $auditFields = explode(',', $record['name'] ?? '');
                $auditFieldValues = explode(',', $record['status'] ?? '');
                $auditDescription = '';

                foreach ($auditFields as $index => $field) {
                    //translated field name
                    $auditFieldDefinition = $vardefs[$field] ?? [];
                    $auditedFieldLabelKey = $mod_strings[$auditFieldDefinition['vname'] ?? ''] ?? '';

                    //present field value
                    $auditFieldValue = $auditFieldValues[$index] ?? '';

                    if ($field === 'assigned_user_id') {
                        // transform userid to username
                        /** @var User $user */
                        $user = BeanFactory::getBean('Users', $auditFieldValue);
                        $auditFieldValue = $user->user_name ?? '';
                    }

                    $auditDescription .= implode(" ", [$auditedFieldLabelKey, $auditFieldValue, '<br/>']);

                    $listData[$key]['description'] = $auditDescription;
                    $listData[$key]['name'] = $app_strings['LBL_RECORD_CHANGED'];
                }
            }

            /** @var User $user */
            $user = BeanFactory::getBean('Users', $record['assigned_user_id']);
            $listData[$key]['assigned_user_name']['user_name'] = showFullName() ? $user->full_name : ($user->user_name ?? '');
            $listData[$key]['assigned_user_name']['user_id'] = $record['assigned_user_id'];
            $listData[$key]['module_name'] = $panelToModuleName[$listData[$key]['panel_name']];
            $listData[$key]['description'] = html_entity_decode($listData[$key]['description']  ?? '', ENT_QUOTES);
            $listData[$key]['name'] =  html_entity_decode($listData[$key]['name']  ?? '', ENT_QUOTES);
            $listData[$key]['status'] =  html_entity_decode($listData[$key]['status']  ?? '', ENT_QUOTES);

        }

        $timelineEntryData = new ListData();
        $timelineEntryData->setOffsets($listData['offsets'] ?? []);
        $timelineEntryData->setOrdering($listData['ordering'] ?? []);
        $timelineEntryData->setRecords($this->recordMapper->mapRecords($listData ?? []));
        return $timelineEntryData;
    }

    /**
     * get audit data grouped by date created and created by
     * @param SugarBean $bean
     * @return string
     */
    protected function queryAuditInfo(
        SugarBean $bean
    ): string {
        $parts = [];

        $parts['select'] = "SELECT max(id),
        GROUP_CONCAT(field_name) as name,
        GROUP_CONCAT(after_value_string) as status,
        max(audit.date_created) AS date_modified,
        max(audit.date_created) AS date_entered,
        max(audit.date_created) AS date_end,
        audit.created_by AS assigned_user_id,
        'audit' panel_name ";

        $parts['from'] = ' FROM ' . $bean->get_audit_table_name() . ' as audit';
        $parts['where'] = " WHERE parent_id = '" . $bean->id . "'";
        $parts['group_by'] = ' GROUP BY audit.date_created, audit.created_by ';

        foreach ($parts as $key => $item) {
            if (isset($queryParts[$key])) {
                $parts[$key] = $queryParts[$key];
            }
        }

        return $this->joinQueryParts($parts);
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
