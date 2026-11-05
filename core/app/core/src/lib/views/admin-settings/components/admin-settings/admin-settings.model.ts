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

/**
 * A settings page of the administration, as sent by the `admin-settings` process
 * (AdminSettingsPage): labels translated, values by field name.
 */
export type AdminSettingValue = string | boolean | string[] | string[][];

export interface AdminSettingOption {
    value: string;
    label: string;
}

export interface AdminSettingItem extends AdminSettingOption {
    locked?: boolean;
}

/**
 * A field (or section) is shown when the other field has one of the values (all conditions).
 */
export interface AdminSettingCondition {
    field: string;
    values: (string | boolean)[];
}

export interface AdminSettingLink {
    label: string;
    // With {value}: the value of the field (shown when the field has one).
    href: string;
    withValue?: boolean;
}

export interface AdminSettingField {
    name: string;
    label: string;
    type: 'bool' | 'number' | 'text' | 'password' | 'textarea' | 'select' | 'html' | 'lists' | 'pairs';
    help?: string;
    options?: AdminSettingOption[];
    required?: boolean;
    showIf?: AdminSettingCondition[];
    wide?: boolean;
    links?: AdminSettingLink[];
    maxlength?: number;
    max?: number;
    suffix?: string;
    // password: whether one is saved (kept when left empty).
    set?: boolean;
    // lists: the items, the labels of the two lists, the field whose value is locked enabled.
    items?: AdminSettingItem[];
    enabledLabel?: string;
    disabledLabel?: string;
    lockedBy?: string;
    ordered?: boolean;
    // pairs: the labels of the two selects and of the buttons.
    leftLabel?: string;
    rightLabel?: string;
    addLabel?: string;
    removeLabel?: string;
}

export interface AdminSettingAction {
    name: string;
    label: string;
}

export interface AdminSettingSection {
    title: string;
    intro?: string;
    fields: AdminSettingField[];
    actions?: AdminSettingAction[];
    showIf?: AdminSettingCondition[];
}

export interface AdminSettingsPageData {
    title: string;
    leave: boolean;
    sections: AdminSettingSection[];
    values: { [name: string]: AdminSettingValue };
}

export interface AdminSettingActionResult {
    status: string;
    message?: string;
    html?: string;
}
