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


namespace App\Statistics\Model;

class ChartOptions
{
    /**@var int|null height */
    public $height;

    /**@var string|null $scheme */
    public $scheme;

    /**@var bool|null $gradient */
    public $gradient;

    /**@var bool|null $xAxis */
    public $xAxis;

    /**@var bool|null $yAxis */
    public $yAxis;

    /**@var bool|null $legend */
    public $legend;

    /**@var bool|null $showXAxisLabel */
    public $showXAxisLabel;

    /**@var bool|null $gradient */
    public $showYAxisLabel;

    /**@var string|null $xAxisLabel */
    public $xAxisLabel;

    /**@var string|null $yAxisLabel */
    public $yAxisLabel;

    /**@var string|null $xScaleMin */
    public $xScaleMin;

    /**@var string|null $xScaleMax */
    public $xScaleMax;

    /**@var string[]|null $xAxisTicks */
    public $xAxisTicks;

    /**@var bool|null $yAxisTickFormatting */
    public $yAxisTickFormatting;

    /**@var bool|null $xAxisTickFormatting */
    public $xAxisTickFormatting;

    /**@var bool|null $tooltipDisabled */
    public $tooltipDisabled;

    public function toArray(): array
    {
        $keys = array_keys(get_object_vars($this));
        $values = [];
        foreach ($keys as $key) {
            if (!is_null($this->{$key})) {
                $values[$key] = $this->{$key};
            }
        }
        return $values;
    }
}
