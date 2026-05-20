<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Statistics;

use App\Statistics\Entity\Statistic;
use App\Statistics\Model\ChartOptions;
use App\Statistics\Model\Series;
use App\Statistics\Model\SeriesItem;
use App\Statistics\Model\SeriesResult;

trait StatisticsHandlingTrait
{

    /**
     * Build empty blank statistic
     * @param string $key
     * @return Statistic
     */
    protected function getBlankResponse(string $key): Statistic
    {
        $statistic = new Statistic();
        $statistic->setId($key);
        $statistic->setData(['value' => '']);
        $statistic->setMetadata([
            'type' => 'single-value-statistic',
            'dataType' => 'varchar',
        ]);

        return $statistic;
    }

    /**
     * Build empty response statistic
     * @param string $key
     * @return Statistic
     */
    protected function getEmptyResponse(string $key): Statistic
    {
        $emptyValueString = '-';
        $statistic = new Statistic();
        $statistic->setId($key);
        $statistic->setData(['value' => $emptyValueString]);
        $statistic->setMetadata([
            'type' => 'single-value-statistic',
            'emptyValueString' => $emptyValueString,
            'dataType' => 'varchar',
        ]);

        return $statistic;
    }

    /**
     * Build error response statistic
     * @param string $key
     * @return Statistic
     */
    protected function getErrorResponse(string $key): Statistic
    {
        $statistic = $this->getEmptyResponse($key);

        $this->addMetadata($statistic, ['labelKey' => 'LBL_STATISTIC_ERROR']);
        $this->addMetadata($statistic, ['tooltip_title_key' => 'LBL_STATISTIC_ERROR_DESC_TOOLTIP']);
        $this->addMetadata($statistic, ['descriptionKey' => 'LBL_STATISTIC_ERROR_DESC']);

        return $statistic;
    }

    /**
     * Build empty response statistic
     * @param string $key
     * @return Statistic
     */
    protected function getEmptySeriesResponse(string $key): Statistic
    {
        $statistic = new Statistic();
        $statistic->setId($key);
        $statistic->setData([
            'multiSeries' => [],
            'singleSeries' => []
        ]);
        $metadata = [
            'type' => 'series-statistic',
            'dataType' => 'int',
        ];

        $statistic->setMetadata($metadata);

        return $statistic;
    }

    /**
     * @param string $key
     * @param string $dataType
     * @param array $result
     * @return Statistic
     */
    protected function buildSingleValueResponse(string $key, string $dataType, array $result): Statistic
    {
        $statistic = new Statistic();
        $statistic->setId($key);
        $statistic->setData($result);
        $statistic->setMetadata([
            'type' => 'single-value-statistic',
            'dataType' => $dataType,
        ]);

        return $statistic;
    }

    /**
     * @param string $key
     * @param string $dataType
     * @param SeriesResult $result
     * @param ChartOptions $options
     * @return Statistic
     */
    protected function buildSeriesResponse(
        string $key,
        string $dataType,
        SeriesResult $result,
        ChartOptions $options = null
    ): Statistic {
        $statistic = new Statistic();
        $statistic->setId($key);
        $statistic->setData($this->toSeriesArray($result));
        $metadata = [
            'type' => 'series-statistic',
            'dataType' => $dataType,
        ];

        if ($options !== null) {
            $metadata['chartOptions'] = $options->toArray();
        }

        $statistic->setMetadata($metadata);

        return $statistic;
    }

    /**
     * @param array $result
     * @param string $groupingField
     * @param string $nameField
     * @param string $valueField
     * @param array $defaultValues
     * @param bool $addEmpty
     * @return Series|SeriesResult
     */
    protected function buildMultiSeries(
        array $result,
        string $groupingField,
        string $nameField,
        string $valueField,
        array $defaultValues,
        bool $addEmpty = false
    ): Series|SeriesResult {
        $seriesMap = [];

        foreach ($result as $row) {
            $groupingFieldValue = $row[$groupingField] ?? '';
            $nameFieldValue = $row[$nameField] ?? '';
            $valueFieldValue = $row[$valueField] ?? '';

            if (empty($seriesMap[$groupingFieldValue])) {
                $series = new Series();
                $series->name = $groupingFieldValue;
                $series->series = [];
                $seriesMap[$groupingFieldValue] = [
                    'series' => $series,
                    'items' => []
                ];

                foreach ($defaultValues as $default) {
                    $item = new SeriesItem();
                    $item->name = $default;
                    $item->value = '0';

                    $seriesMap[$groupingFieldValue]['items'][$default] = $item;
                }
            }

            $seriesMap[$groupingFieldValue]['items'][$nameFieldValue]->value = $valueFieldValue;
        }

        if (empty($result) && $addEmpty) {
            $series = new Series();
            $series->name = '';
            $series->series = [];
            $seriesMap[''] = [
                'series' => $series,
                'items' => []
            ];

            foreach ($defaultValues as $default) {
                $item = new SeriesItem();
                $item->name = $default;
                $item->value = '0';

                $seriesMap['']['items'][$default] = $item;
            }
        }

        $series = new SeriesResult();
        $series->multiSeries = [];

        foreach ($seriesMap as $item) {
            $item['series']->series = array_values($item['items']);
            $series->multiSeries[] = $item['series'];
        }

        return $series;
    }

    /**
     * Build single series from query results with raw data (no translation)
     * @param array $result
     * @param string $nameField
     * @param string $valueField
     * @param array $defaultValues
     * @return SeriesResult
     */
    protected function buildSingleSeries(
        array $result,
        string $nameField,
        string $valueField,
        array $fields = [],
        array $defaultValues = [],
        bool $showEmpty = false
    ): Series|SeriesResult
    {
        $seriesMap = [];

        if (!empty($defaultValues)) {
            foreach ($defaultValues as $default) {
                $item = new SeriesItem();
                $item->name = $default;
                $item->value = '0';

                $seriesMap[$default] = $item;
            }
        }

        foreach ($result as $row) {
            $nameFieldValue = $row[$nameField] ?? '';
            $valueFieldValue = $row[$valueField] ?? '0';
            $label = $fields[$nameFieldValue] ?? '';

            if (empty($seriesMap[$nameFieldValue])) {
                $seriesMap[$nameFieldValue] = new SeriesItem();
                $seriesMap[$nameFieldValue]->name = $nameFieldValue;
                if (!empty($label)) {
                    $seriesMap[$nameFieldValue]->label = $label;
                }
            }

            $seriesMap[$nameFieldValue]->value = $valueFieldValue;
        }

        if (empty($result) && !$showEmpty){
            $series = new SeriesResult();
            $series->singleSeries = array_values($seriesMap);

            return $series;
        }


        foreach ($fields as $i => $field) {
            if (empty($seriesMap[$i])){
                $seriesMap[$i] = new SeriesItem();
                $seriesMap[$i]->name = $i;
                $seriesMap[$i]->label = $field;
                $seriesMap[$i]->value = '0';
            }
        }


        $series = new SeriesResult();
        $series->singleSeries = array_values($seriesMap);

        return $series;

    }


    /**
     * @param SeriesResult $result
     * @return array
     */
    protected function toSeriesArray(SeriesResult $result): array
    {
        $seriesArray = [];
        if (isset($result->singleSeries)) {
            $seriesArray['singleSeries'] = [];
            foreach ($result->singleSeries as $singleSeriesEntry) {
                $seriesArray['singleSeries'][] = $this->buildSeriesEntry($singleSeriesEntry);
            }
        }

        if (isset($result->multiSeries)) {
            $seriesArray['multiSeries'] = [];
            foreach ($result->multiSeries as $multiSeriesEntry) {
                $multiSeriesArray = [];
                $multiSeriesArray['name'] = $multiSeriesEntry->name;
                $multiSeriesArray['series'] = [];

                foreach ($multiSeriesEntry->series as $singleSeriesEntry) {

                    $multiSeriesArray['series'][] = $this->buildSeriesEntry($singleSeriesEntry);


                }
                $seriesArray['multiSeries'][] = $multiSeriesArray;
            }
        }

        return $seriesArray;
    }

    /**
     * @param Statistic $statistic
     * @param array $newMeta
     */
    protected function addMetadata(Statistic $statistic, array $newMeta): void
    {
        $metadata = $statistic->getMetadata() ?? [];
        $metadata = array_merge($metadata, $newMeta);
        $statistic->setMetadata($metadata);
    }

    /**
     * Build currency statistic result
     * @param array $result
     * @return Statistic
     */
    protected function buildCurrencyResult(array $result): Statistic
    {
        return $this->buildNumberResult($result, 'currency');
    }

    /**
     * Build number statistic result
     * @param array $result
     * @param string $type
     * @return Statistic
     */
    protected function buildNumberResult(array $result, string $type): Statistic
    {
        $value = $result['value'] ?? 0;

        if (empty($value)) {
            $result = ['value' => 0];
        }

        $statistic = new Statistic();
        $statistic->setId(self::KEY);
        $statistic->setData($result);
        $statistic->setMetadata([
            'type' => 'single-value-statistic',
            'dataType' => $type,
        ]);

        return $statistic;
    }

    /**
     * @param array $parts
     * @return string
     */
    protected function joinQueryParts(array $parts): string
    {
        $queryParts = [];
        $queryParts[] = $parts['select'] ?? '';
        $queryParts[] = $parts['from'] ?? '';
        $queryParts[] = $parts['where'] ?? '';
        $queryParts[] = $parts['group_by'] ?? '';
        $queryParts[] = $parts['order_by'] ?? '';

        return implode(' ', $queryParts);
    }

    /**
     * @param array $query
     * @return array
     */
    protected function extractContext(array $query): array
    {
        $module = $query['context']['module'] ?? '';
        $id = $query['context']['id'] ?? '';
        $criteria = $query['context']['criteria'] ?? [];
        $sort = $query['context']['sort'] ?? [];

        return [$module, $id, $criteria, $sort];
    }

    /**
     * @param SeriesItem $singleSeriesEntry
     * @return array
     */
    protected function buildSeriesEntry(SeriesItem $singleSeriesEntry): array
    {
        $keys = ['name', 'value', 'extra', 'min', 'max', 'label'];

        $entry = [];

        foreach ($keys as $key) {
            if ($singleSeriesEntry->$key !== null) {
                $entry[$key] = $singleSeriesEntry->$key;
            }
        }

        return $entry;
    }


}
