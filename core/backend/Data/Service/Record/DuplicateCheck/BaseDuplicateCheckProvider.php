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

namespace App\Data\Service\Record\DuplicateCheck;

use App\Data\LegacyHandler\PreparedStatementHandler;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

/**
 * Query helpers shared by the duplicate check providers
 */
abstract class BaseDuplicateCheckProvider implements DuplicateCheckProviderInterface
{
    protected const MAX_DUPLICATES = 50;
    protected const LIKE_ESCAPE_CHAR = '!';

    /**
     * @param PreparedStatementHandler $preparedStatementHandler
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Get the module table
     * @return string
     */
    abstract protected function getTable(): string;

    /**
     * Get the legacy module name, as stored in email_addr_bean_rel.bean_module
     * @return string
     */
    abstract protected function getLegacyModule(): string;

    /**
     * Records of the module linked to one of the email addresses being saved.
     * When editing, only the addresses added by the edit are checked.
     * @param array $attributes
     * @param string $recordId
     * @return string[]
     */
    protected function findByEmails(array $attributes, string $recordId): array
    {
        $emails = $this->getEmails($attributes);

        if ($recordId !== '' && !empty($emails)) {
            $emails = array_values(array_diff($emails, $this->getRecordEmails($recordId)));
        }

        if (empty($emails)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->distinct()
            ->innerJoin(
                't',
                'email_addr_bean_rel',
                'eabr',
                'eabr.bean_id = t.id AND eabr.deleted = 0 AND eabr.bean_module = '
                . $queryBuilder->createNamedParameter($this->getLegacyModule())
            )
            ->innerJoin('eabr', 'email_addresses', 'ea', 'ea.id = eabr.email_address_id AND ea.deleted = 0')
            ->andWhere($queryBuilder->expr()->in(
                'ea.email_address_caps',
                $queryBuilder->createNamedParameter($emails, ArrayParameterType::STRING)
            ));

        return $this->fetchIds($queryBuilder, $recordId);
    }

    /**
     * Upper-cased email addresses being saved: the email line items not flagged as deleted, plus email1
     * @param array $attributes
     * @return string[]
     */
    protected function getEmails(array $attributes): array
    {
        $emails = [];

        foreach ($attributes['email_addresses'] ?? [] as $item) {
            $itemAttributes = $item['attributes'] ?? [];
            if (!empty($itemAttributes['deleted'])) {
                continue;
            }

            $emails[] = $itemAttributes['email_address'] ?? '';
        }

        $emails[] = $attributes['email1'] ?? '';

        return $this->normalizeEmails($emails);
    }

    /**
     * Upper-cased email addresses already linked to the record
     * @param string $recordId
     * @return string[]
     */
    protected function getRecordEmails(string $recordId): array
    {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
        $queryBuilder
            ->select('ea.email_address_caps')
            ->from('email_addr_bean_rel', 'eabr')
            ->innerJoin('eabr', 'email_addresses', 'ea', 'ea.id = eabr.email_address_id AND ea.deleted = 0')
            ->where('eabr.deleted = 0')
            ->andWhere('eabr.bean_module = ' . $queryBuilder->createNamedParameter($this->getLegacyModule()))
            ->andWhere('eabr.bean_id = ' . $queryBuilder->createNamedParameter($recordId));

        try {
            $rows = $queryBuilder->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error('Duplicate check: unable to get the record email addresses. ' . $e->getMessage());

            return [];
        }

        return $this->normalizeEmails(array_column($rows, 'email_address_caps'));
    }

    /**
     * Query on the non-deleted records of the module, most recent first
     * @return QueryBuilder
     */
    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->preparedStatementHandler->createQueryBuilder()
            ->select('t.id', 't.date_entered')
            ->from($this->getTable(), 't')
            ->where('t.deleted = 0')
            ->orderBy('t.date_entered', 'DESC')
            ->setMaxResults(self::MAX_DUPLICATES);
    }

    /**
     * Run the query, excluding the record being saved.
     * A failing query is logged and returns no duplicates, so it never blocks a save.
     * @param QueryBuilder $queryBuilder
     * @param string $recordId
     * @return string[]
     */
    protected function fetchIds(QueryBuilder $queryBuilder, string $recordId): array
    {
        if ($recordId !== '') {
            $queryBuilder->andWhere('t.id <> ' . $queryBuilder->createNamedParameter($recordId));
        }

        try {
            $rows = $queryBuilder->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error('Duplicate check: unable to get the ' . $this->getModule() . ' duplicates. ' . $e->getMessage());

            return [];
        }

        return array_values(array_unique(array_map('strval', array_column($rows, 'id'))));
    }

    /**
     * Add a "starts with" condition, the user value wildcards being escaped
     * @param QueryBuilder $queryBuilder
     * @param string $column
     * @param string $value
     */
    protected function andWhereStartsWith(QueryBuilder $queryBuilder, string $column, string $value): void
    {
        $queryBuilder->andWhere($this->startsWith($queryBuilder, $column, $value));
    }

    /**
     * Build a "starts with" condition, the user value wildcards being escaped
     * @param QueryBuilder $queryBuilder
     * @param string $column
     * @param string $value
     * @return string
     */
    protected function startsWith(QueryBuilder $queryBuilder, string $column, string $value): string
    {
        $escape = self::LIKE_ESCAPE_CHAR;
        $pattern = str_replace([$escape, '%', '_'], [$escape . $escape, $escape . '%', $escape . '_'], $value) . '%';

        return $queryBuilder->expr()->like(
            $column,
            $queryBuilder->createNamedParameter($pattern),
            "'" . $escape . "'"
        );
    }

    /**
     * True on create, or when the edit changed one of the fields
     * @param string[] $fields
     * @param array $attributes
     * @param array $originalAttributes
     * @param string $recordId
     * @return bool
     */
    protected function hasChanged(array $fields, array $attributes, array $originalAttributes, string $recordId): bool
    {
        if ($recordId === '') {
            return true;
        }

        foreach ($fields as $field) {
            if ($this->getValue($attributes, $field) !== $this->getValue($originalAttributes, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a trimmed string attribute
     * @param array $attributes
     * @param string $field
     * @return string
     */
    protected function getValue(array $attributes, string $field): string
    {
        $value = $attributes[$field] ?? '';

        return is_scalar($value) ? trim((string)$value) : '';
    }

    /**
     * @param array $emails
     * @return string[]
     */
    protected function normalizeEmails(array $emails): array
    {
        $normalized = array_map(
            static fn($email) => is_scalar($email) ? strtoupper(trim((string)$email)) : '',
            $emails
        );

        return array_values(array_unique(array_filter($normalized, static fn(string $email) => $email !== '')));
    }
}
