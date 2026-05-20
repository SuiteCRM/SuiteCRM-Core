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

namespace App\Module\Campaigns\Service\Email\Parser;

use Traversable;

class CampaignEmailParserRegistry
{
    use CampaignEmailParserMapperTrait;

    /**
     * @var CampaignEmailParserInterface[][]
     */
    protected array $registry = [];

    /**
     * CampaignEmailParserRegistry constructor.
     * @param Traversable $parsers
     */
    public function __construct(Traversable $parsers)
    {
        /**
         * @var $parsers CampaignEmailParserInterface[]
         */

        $this->addParsers($parsers);
    }

    /**
     * Get the parsers for the module
     * @return CampaignEmailParserInterface[]
     */
    public function getParsers(): array
    {
        return $this->getOrderedParsers();
    }

    /**
     * @param CampaignEmailParserInterface[] $parsers
     * @return void
     */
    protected function addParsers(iterable $parsers): void
    {
        foreach ($parsers as $handler) {
            $this->addParsersByOrder($handler);
        }
    }


    protected function getOrderedParsers(): array
    {
        $parsers = $this->registry ?? [];

        $flatList = [];
        foreach ($parsers as $orderedParsers) {

            if (empty($orderedParsers)) {
                continue;
            }

            if (!is_array($orderedParsers)) {
                $flatList[] = $orderedParsers;
                continue;
            }

            foreach ($orderedParsers as $orderedParser) {
                if (empty($orderedParser)) {
                    continue;
                }
                $flatList[] = $orderedParser;
            }
        }
        return $flatList;
    }

    /**
     * @param CampaignEmailParserInterface $handler
     * @return void
     */
    protected function addParsersByOrder(CampaignEmailParserInterface $handler): void
    {
        $order = $handler->getOrder() ?? 0;
        $mappersByOrder = $this->registry[$order] ?? [];
        $mappersByOrder[] = $handler;
        $this->registry[$order] = $mappersByOrder;
    }
}
