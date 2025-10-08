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
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\DependecyInjection\Metadata;

use Symfony\Component\DependencyInjection\ContainerBuilder;

trait ParameterMergingLoaderTrait
{
    /**
     * @param string $key
     * @param ContainerBuilder $container
     * @param callable $loaderCallback
     * @param array $structure
     * @return void
     */
    protected function loadAndMerge(string $key, ContainerBuilder $container, callable $loaderCallback, array $structure): void
    {
        $current = $container->getParameterBag()->get($key);
        if (empty($current)) {
            $current = [];
        }

        $loaderCallback();

        $newValue = $container->getParameter($key);
        if (empty($newValue)) {
            $newValue = [];
        }

        $merged = $this->merge($structure, $current, $newValue);

        $container->setParameter($key, $merged);
    }

    protected function merge(array|bool $structureValue, array $left, array $right): array
    {

        if (empty($structureValue)) {
            return [];
        }

        if (empty($left) && empty($right)) {
            return [];
        }

        if (!empty($left) && empty($right)) {
            return $left;
        }

        if (empty($left) && !empty($right)) {
            return $right;
        }

        if (is_bool($structureValue)) {
            return $this->runMergeAll($structureValue, $left, $right);
        }

        $subMergeAll = $structureValue['*'] ?? '';
        if ($subMergeAll !== '') {

            if (is_array($subMergeAll)) {
                return $this->subArrayMerge($structureValue['*'], $left, $right);
            }

            return $this->runSubMergeAll($subMergeAll, $left, $right);
        }

        $result = [];
        foreach ($structureValue as $index => $value) {
            $result[$index] = $this->merge($value, $left[$index] ?? [], $right[$index] ?? []);
        }

        return $result;
    }

    /**
     * @param bool $structureValue
     * @param array $left
     * @param array $right
     * @return array
     */
    protected function runMergeAll(bool $structureValue, array $left, array $right): array
    {
        if ($structureValue === true) {
            return array_merge($left, $right);
        }

        return [];
    }

    /**
     * @param bool $mergeAll
     * @param array $left
     * @param array $right
     * @return array
     */
    protected function runSubMergeAll(bool $mergeAll, array $left, array $right): array
    {
        if ($mergeAll === true) {
            $result = [];
            foreach ($left as $leftIndex => $leftValue) {
                $result[$leftIndex] = array_merge($leftValue ?? [], $right[$leftIndex] ?? []);
            }
            foreach ($right as $rightIndex => $rightValue) {
                if (isset($result[$rightIndex])) {
                    continue;
                }
                $result[$rightIndex] = $rightValue;
            }
            return $result;
        }

        return [];
    }

    /**
     * @param array $left
     * @param $structureValue
     * @param array $right
     * @return array
     */
    protected function subArrayMerge($structureValue, array $left, array $right): array
    {
        $result = [];
        foreach ($left as $leftIndex => $leftValue) {
            $result[$leftIndex] = $this->merge($structureValue, $leftValue ?? [], $right[$leftIndex] ?? []);
        }
        foreach ($right as $rightIndex => $rightValue) {
            if (isset($result[$rightIndex])) {
                continue;
            }
            $result[$rightIndex] = $this->merge($structureValue, [], $rightValue ?? []);
        }
        return $result;
    }


}
