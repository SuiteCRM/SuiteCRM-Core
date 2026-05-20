{*
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
*}

{include file="modules/DynamicFields/templates/Fields/Forms/coreTop.tpl"}

<tr>
    <td class='mbLBL'>{sugar_translate module="DynamicFields" label="LBL_METADATA"}</td>
    <td class='mbLBL'>{sugar_translate module="DynamicFields" label="LBL_STORAGE_TYPE"}:</td>
    <td>
        {html_options name="storage_type" id="storage_type" options=$storage_type_options selected=$vardef.metadata.storage_type}
    </td>
</tr>
<tr>
    <td class="mbLBL"></td>
    <td class='mbLBL'>{sugar_translate module="DynamicFields" label="LBL_UPLOAD_MAXSIZE"}:</td>
    <td>
        <input id="upload_maxsize" type="text" name="upload_maxsize"
                {if !$vardef.metadata.upload_maxsize}
                    value=""
                {else}
                    value="{$vardef.metadata.upload_maxsize}"
                {/if}
        >
    </td>
</tr>

{include file="modules/DynamicFields/templates/Fields/Forms/coreBottom.tpl"}
