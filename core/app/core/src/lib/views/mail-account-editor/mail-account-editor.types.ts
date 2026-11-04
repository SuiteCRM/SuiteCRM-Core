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
 * The data of the account editor (backend MailAccountEditor and its subclasses: inbound and outbound
 * email accounts, external OAuth providers and connections, calendar accounts).
 */
export interface MailAccountOption {
    value: string;
    label: string;
}

/** Shown when each of these fields has one of these values. */
export type MailAccountRule = { [field: string]: string[] };

/** An entry of a key-value list (legacy stringmap field). */
export interface MailAccountEntry {
    key: string;
    value: string;
}

export type MailAccountValue = string | boolean | string[] | MailAccountEntry[];

export interface MailAccountField {
    name: string;
    label: string;
    // text, textarea, password, int, bool, enum, readonly, relate, stringmap, folders, folder, primary, assign
    type: string;
    required?: boolean;
    options?: MailAccountOption[];
    // relate: the frontend module, the field of the record id, and whether the record fills the form
    module?: string;
    idField?: string;
    fill?: boolean;
    // folders, folder: the title of the folder choice; primary: the field of the folders
    title?: string;
    of?: string;
    // assign: the security groups and roles
    groups?: MailAccountOption[];
    roles?: MailAccountOption[];
    // bool, enum: the values it sets in other fields ({field: {value: value of the other field}})
    sets?: { [field: string]: { [value: string]: string } };
    // stringmap: whether the entries have keys (else a list of values)
    showKeys?: boolean;
    show?: MailAccountRule;
    clear?: boolean;
    // A write-only value is saved (kept when the field is left empty).
    isSet?: boolean;
    // readonly: the value shown as a date and time (in the format of the user)
    format?: string;
}

/** The test of the settings of a panel (connection test, or a test email to an address). */
export interface MailAccountTest {
    label: string;
    // The message while the test runs.
    wait: string;
    // The label of the address of the test email, and of its send button.
    address?: string;
    send?: string;
}

/**
 * The authorization of an OAuth connection by its provider: the legacy entry point opened in a
 * window (with the provider of the field), which sends the token back (BroadcastChannel "auth").
 */
export interface MailAccountAuthorize {
    label: string;
    url: string;
    provider: string;
    // The message when no provider is chosen.
    missing: string;
}

export interface MailAccountPanel {
    key: string;
    label: string;
    show?: MailAccountRule;
    test?: MailAccountTest;
    authorize?: MailAccountAuthorize;
    fields: MailAccountField[];
}

export interface MailAccount {
    id: string;
    module: string;
    title: string;
    values: { [field: string]: MailAccountValue };
    panels: MailAccountPanel[];
    labels: { [key: string]: string };
}

/** The folder choice of a folder field (the folders of the mail server). */
export interface MailAccountFolderChoice {
    field: MailAccountField;
    folders: string[];
    selected: Set<string>;
    message: string;
}

/** The result of the connection test. */
export interface MailAccountTestResult {
    success: boolean;
    message: string;
    // The SMTP log of a failed test email.
    log?: string;
    // Values of the form set by the test (saved with the account).
    values?: { [field: string]: MailAccountValue };
}
