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
 * Data of the workflow editor (backend WorkflowEditor).
 */

export interface WfChoice {
    value: string;
    label: string;
    type?: string;
    module?: string;
}

/** The value of a line: a text, or the parts of a date, the users to assign, the values of a list. */
export type WfValue = string | string[];

/** What a line on a field offers (WorkflowEditor::describe*()). */
export interface WfFieldInfo {
    type: string;
    input: string;
    module: string;
    legacyModule: string;
    options: WfChoice[];
    operators?: WfChoice[];
    valueTypes: WfChoice[];
    fields?: WfChoice[];
    dateBases?: WfChoice[];
}

export interface WfValueLine {
    key: number;
    valueType: string;
    value: WfValue;
    valueLabel: string;
    info: WfFieldInfo | null;
}

export interface WfConditionLine extends WfValueLine {
    id: string;
    path: string;
    pathLabel?: string;
    field: string;
    fieldLabel?: string;
    operator: string;
}

export interface WfFieldLine extends WfValueLine {
    field: string;
}

export interface WfRelationLine extends WfValueLine {
    rel: string;
}

export interface WfRecipient {
    key: number;
    to: string;
    type: string;
    value: WfValue;
    valueLabel: string;
}

/** The module a Create Record or Modify Record action changes. */
export interface WfTarget {
    name: string;
    label: string;
    fields: WfChoice[];
    relationships: WfChoice[];
}

export interface WfComputeRelation extends WfChoice {
    fields: WfChoice[];
}

export interface WfComputeLine {
    key: number;
    field: string;
    type?: string;
    relation?: string;
    formula?: string;
}

export interface WfActionParams {
    // Send Email.
    individualEmail?: boolean;
    emailTemplate?: string;
    recipients?: WfRecipient[];
    // Create Record, Modify Record.
    recordType?: string;
    relateToWorkflow?: boolean;
    copyEmailAddresses?: boolean;
    relType?: string;
    fields?: WfFieldLine[];
    relationships?: WfRelationLine[];
    target?: WfTarget | null;
    // Calculate Fields.
    parameters?: WfComputeLine[];
    relationParameters?: WfComputeLine[];
    formulas?: WfComputeLine[];
    // An action the editor does not offer.
    raw?: string;
}

export interface WfActionLine {
    key: number;
    id: string;
    name: string;
    action: string;
    supported: boolean;
    params: WfActionParams;
}

export interface WfModule {
    name: string;
    label: string;
    relationships: WfChoice[];
    emailTypes: WfChoice[];
    emailFields: WfChoice[];
    fields: WfChoice[];
    computeRelations: WfComputeRelation[];
}

export interface WfOptions {
    modules: WfChoice[];
    statuses: WfChoice[];
    runWhen: WfChoice[];
    runOn: WfChoice[];
    actions: WfChoice[];
    dateOperators: WfChoice[];
    dateUnits: WfChoice[];
    assignOptions: WfChoice[];
    emailTo: WfChoice[];
    emailTemplates: WfChoice[];
    roles: WfChoice[];
    securityGroups: WfChoice[];
    computeTypes: WfChoice[];
}

export interface WfWorkflow {
    id: string;
    name: string;
    assignedUserId: string;
    assignedUserName: string;
    flowModule: string;
    status: string;
    runWhen: string;
    flowRunOn: string;
    multipleRuns: boolean;
    runOnImport: boolean;
    description: string;
    conditions: WfConditionLine[];
    actions: WfActionLine[];
    options: WfOptions;
    module: WfModule;
    labels: { [key: string]: string };
}
