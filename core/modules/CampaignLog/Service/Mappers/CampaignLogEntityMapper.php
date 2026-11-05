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

namespace App\Module\CampaignLog\Service\Mappers;

use App\Data\Entity\Record;
use App\Data\Service\Record\EntityRecordMappers\EntityRecordMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The fields of a campaign log entry that the legacy subpanels compute, for the list and record
 * views: the name and email of the target (CampaignLog::get_list_view_data) and
 * the name of the related record (CampaignLog::get_related_name: the email sent, the tracker URL
 * clicked, the lead or contact created...).
 */
class CampaignLogEntityMapper implements EntityRecordMapperInterface
{
    /** Tables of the target and related types whose name is a person name. */
    protected const PERSONS = ['Contacts' => 'contacts', 'Leads' => 'leads', 'Prospects' => 'prospects', 'Users' => 'users'];

    /** Tables (and name column) of the other related types. */
    protected const NAMED = [
        'Accounts' => ['accounts', 'name'],
        'Emails' => ['emails', 'name'],
        'CampaignTrackers' => ['campaign_trkrs', 'tracker_url'],
    ];

    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function getKey(): string
    {
        return 'campaign-log-names';
    }

    public function getModule(): string
    {
        return 'campaign-log';
    }

    public function getModes(): array
    {
        return ['list', 'retrieve'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $attributes = $record->getAttributes() ?? [];

        $targetType = (string)($attributes['target_type'] ?? '');
        $targetId = (string)($attributes['target_id'] ?? '');
        if ($targetType !== '' && $targetId !== '') {
            $attributes['recipient_name'] = $this->name($targetType, $targetId);
            $attributes['recipient_email'] = $this->email($targetId);
        }

        $relatedType = (string)($attributes['related_type'] ?? '');
        $relatedId = (string)($attributes['related_id'] ?? '');
        if ($relatedType !== '' && $relatedId !== '') {
            $attributes['related_record_name'] = $this->name($relatedType, $relatedId);
        }

        $record->setAttributes($attributes);
    }

    /**
     * The name of a record of a type of the log (empty when unknown).
     */
    protected function name(string $type, string $id): string
    {
        $connection = $this->entityManager->getConnection();

        if (isset(self::PERSONS[$type])) {
            $row = $connection->fetchAssociative(
                'SELECT first_name, last_name FROM ' . self::PERSONS[$type] . ' WHERE id = ?',
                [$id]
            );

            return $row ? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) : '';
        }

        if (isset(self::NAMED[$type])) {
            [$table, $column] = self::NAMED[$type];
            $value = $connection->fetchOne('SELECT ' . $column . ' FROM ' . $table . ' WHERE id = ?', [$id]);

            return $value !== false ? (string)$value : '';
        }

        return '';
    }

    /**
     * The email address of the target, the primary one first (CampaignLog::retrieve_email_address).
     */
    protected function email(string $id): string
    {
        $email = $this->entityManager->getConnection()->fetchOne(
            'SELECT ea.email_address FROM email_addresses ea'
            . ' LEFT JOIN email_addr_bean_rel eabr ON eabr.email_address_id = ea.id'
            . ' WHERE eabr.bean_id = ? AND ea.deleted = 0 AND eabr.deleted = 0'
            . ' ORDER BY eabr.primary_address DESC',
            [$id]
        );

        return $email !== false ? (string)$email : '';
    }
}
