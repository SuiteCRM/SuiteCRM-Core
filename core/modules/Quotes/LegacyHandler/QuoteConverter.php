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

namespace App\Module\Quotes\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Records created from a quote, as the legacy quote actions create them
 * (modules/AOS_Quotes: converToInvoice.php, createContract.php,
 * createOpportunity.php), which end with a redirect to the legacy edit view:
 *  - invoice: the quote values, number and line items; the quote is set as invoiced;
 *  - contract: the quote account, contact, opportunity, totals and line items;
 *  - opportunity: the quote name, account, amount and expiry date, linked to the quote.
 */
class QuoteConverter extends LegacyHandler
{
    public const HANDLER_KEY = 'quote-converter';

    public const INVOICE = 'AOS_Invoices';
    public const CONTRACT = 'AOS_Contracts';
    public const OPPORTUNITY = 'Opportunities';

    protected const NUMBER_TYPES = ['currency', 'decimal', 'float', 'double'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Id of the record created from the quote, or null (quote not found, no
     * access to it or to the target module).
     */
    public function convert(string $quoteId, string $targetModule): ?string
    {
        $this->init();
        $this->startLegacyApp();

        $id = null;
        $quote = \BeanFactory::getBean('AOS_Quotes', $quoteId);

        if ($quote && !empty($quote->id) && $quote->ACLAccess('view')
            && \ACLController::checkAccess($targetModule, 'edit', true)) {
            switch ($targetModule) {
                case self::INVOICE:
                    $id = $this->toInvoice($quote);
                    break;
                case self::CONTRACT:
                    $id = $this->toContract($quote);
                    break;
                case self::OPPORTUNITY:
                    $id = $this->toOpportunity($quote);
                    break;
            }
        }

        $this->close();

        return $id;
    }

    protected function toInvoice(\SugarBean $quote): string
    {
        $quote->invoice_status = 'Invoiced';
        $quote->save();

        $row = $quote->fetched_row;
        $row['id'] = '';
        $row['template_ddown_c'] = ' ';
        $row['quote_number'] = $row['number'];
        $row['number'] = '';
        $row['quote_date'] = explode(' ', (string)$row['date_entered'])[0];
        $row['invoice_date'] = date('Y-m-d');
        unset($row['date_entered'], $row['date_modified'], $row['created_by'], $row['modified_user_id']);

        $invoice = \BeanFactory::newBean(self::INVOICE);
        $invoice->populateFromRow($this->toBeanRow($invoice, $row));
        $invoice->populateDefaultValues();
        $invoice->save();

        require_once 'modules/Relationships/Relationship.php';
        $link = \Relationship::retrieve_by_modules('AOS_Quotes', self::INVOICE, $quote->db);
        if (!empty($link) && $quote->load_relationship($link)) {
            $quote->$link->add($invoice->id);
        }

        $this->copyLineItems($quote, $invoice);

        return $invoice->id;
    }

    protected function toContract(\SugarBean $quote): string
    {
        $contract = \BeanFactory::newBean(self::CONTRACT);
        $contract->name = $quote->name;
        $contract->assigned_user_id = $quote->assigned_user_id;
        $contract->total_contract_value = (float)$quote->total_amount;
        $contract->contract_account_id = $quote->billing_account_id;
        $contract->contact_id = $quote->billing_contact_id;
        $contract->opportunity_id = $quote->opportunity_id;

        foreach (['total_amt', 'subtotal_amount', 'discount_amount', 'tax_amount', 'shipping_amount', 'shipping_tax_amt', 'total_amount'] as $field) {
            $contract->$field = $quote->$field === null || $quote->$field === '' ? null : (float)$quote->$field;
        }

        $contract->shipping_tax = $quote->shipping_tax;
        $contract->currency_id = $quote->currency_id;
        $contract->save();

        $this->copyLineItems($quote, $contract);

        require_once 'modules/Relationships/Relationship.php';
        $link = \Relationship::retrieve_by_modules('AOS_Quotes', self::CONTRACT, $quote->db);
        if (!empty($link) && $quote->load_relationship($link)) {
            $quote->$link->add($contract->id);
        }

        return $contract->id;
    }

    protected function toOpportunity(\SugarBean $quote): string
    {
        global $app_list_strings;

        $opportunity = \BeanFactory::newBean(self::OPPORTUNITY);
        $opportunity->name = $quote->name;
        $opportunity->assigned_user_id = $quote->assigned_user_id;
        $opportunity->amount = (float)$quote->total_amount;
        $opportunity->account_id = $quote->billing_account_id;
        $opportunity->currency_id = $quote->currency_id;
        $opportunity->sales_stage = 'Proposal/Price Quote';
        $opportunity->probability = $app_list_strings['sales_probability_dom']['Proposal/Price Quote'] ?? '';
        $opportunity->lead_source = 'Self Generated';
        $opportunity->date_closed = $quote->expiration;
        $opportunity->save();

        if ($quote->load_relationship('opportunities')) {
            $quote->opportunities->add($opportunity->id);
        }

        return $opportunity->id;
    }

    /**
     * Copies the groups and lines of $from to $to (new ids, the lines keep their group).
     */
    protected function copyLineItems(\SugarBean $from, \SugarBean $to): void
    {
        $db = $from->db;
        $parent = 'parent_type = ' . $db->quoted($from->object_name)
            . ' AND parent_id = ' . $db->quoted($from->id) . ' AND deleted = 0';

        $groupIds = [];
        $result = $db->query('SELECT * FROM aos_line_item_groups WHERE ' . $parent . ' ORDER BY number');
        while ($row = $db->fetchByAssoc($result, false)) {
            $oldId = $row['id'];
            $group = \BeanFactory::newBean('AOS_Line_Item_Groups');
            $group->populateFromRow($this->toBeanRow($group, $this->toChildRow($row, $to)));
            $group->save();
            $groupIds[$oldId] = $group->id;
        }

        $result = $db->query('SELECT * FROM aos_products_quotes WHERE ' . $parent . ' ORDER BY number');
        while ($row = $db->fetchByAssoc($result, false)) {
            $row = $this->toChildRow($row, $to);
            $row['group_id'] = $groupIds[$row['group_id'] ?? ''] ?? null;
            $line = \BeanFactory::newBean('AOS_Products_Quotes');
            $line->populateFromRow($this->toBeanRow($line, $row));
            $line->save();
        }
    }

    protected function toChildRow(array $row, \SugarBean $parent): array
    {
        $row['id'] = '';
        $row['parent_id'] = $parent->id;
        $row['parent_type'] = $parent->object_name;
        unset($row['date_entered'], $row['date_modified'], $row['created_by'], $row['modified_user_id']);

        return $row;
    }

    /**
     * Database row with its amounts as numbers, for populateFromRow() and save():
     * perform_aos_save() unformats strings with the user number format.
     */
    protected function toBeanRow(\SugarBean $bean, array $row): array
    {
        foreach ($row as $field => $value) {
            $type = $bean->field_defs[$field]['type'] ?? '';
            if (in_array($type, self::NUMBER_TYPES, true) && is_numeric($value)) {
                $row[$field] = (float)$value;
            }
        }

        return $row;
    }
}
