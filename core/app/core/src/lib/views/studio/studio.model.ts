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
 * Studio page of the administration, data of the `studio` process (Studio).
 */
export const STUDIO_PROCESS = 'studio';

/**
 * The legacy Studio actions the page opens in the legacy Studio (the components not in the page
 * yet), as the legacy Studio tree opens them.
 */
export const STUDIO_LEGACY_ACTIONS = ['wizard', 'editLayout'];

/**
 * The legacy Studio wizard screens of a module the page shows (its folders of components), with
 * their question and help (legacy view.wizard).
 */
export const STUDIO_FOLDERS: { [view: string]: { question: string; help: string } } = {
    layouts: {question: 'LBL_QUESTION_LAYOUT', help: 'layoutsHelp'},
    subpanels: {question: 'LBL_QUESTION_SUBPANEL', help: 'subpanelHelp'},
    search: {question: 'LBL_QUESTION_SEARCH', help: 'searchHelp'},
    dashlet: {question: 'LBL_QUESTION_DASHLET', help: 'dashletHelp'},
    popup: {question: 'LBL_QUESTION_POPUP', help: 'popupHelp'},
};

/**
 * The list layouts the page edits (legacy view of their editLayout action, lower case), with
 * the help group of their legacy editor.
 */
export const STUDIO_LIST_LAYOUTS: { [view: string]: string } = {
    listview: 'listViewEditor',
    dashlet: 'listViewEditor',
    dashletsearch: 'searchViewEditor',
    popuplist: 'popupListViewEditor',
    popupsearch: 'searchViewEditor',
    basic_search: 'searchViewEditor',
    advanced_search: 'searchViewEditor',
};

/**
 * The grid layouts the page edits (legacy view of their editLayout action, lower case).
 */
export const STUDIO_GRID_LAYOUTS = ['editview', 'detailview', 'quickcreate'];

/**
 * The slots of a grid layout without a field: a blank cell, the cell taken by the field before
 * it (legacy MBConstants).
 */
export const STUDIO_FILLER = '(filler)';
export const STUDIO_EMPTY = '(empty)';

/**
 * The theme icons of the components (by legacy action, and view for the wizard and the
 * layouts), in place of the legacy Studio icon font.
 */
export const STUDIO_ICONS: { [key: string]: string } = {
    'editLabels': 'no-fill-pencil',
    // The labels of a module of a Module Builder package.
    'modulelabels': 'no-fill-pencil',
    'modulefields': 'list',
    'relationships': 'apps-line',
    'wizard:layouts': 'kanban',
    'wizard:subpanels': 'clipboard',
    'wizard:dashlet': 'piechart',
    'wizard:popup': 'arrows-angle-expand',
    'wizard:search': 'filter',
    'editLayout:editview': 'edit',
    'editLayout:detailview': 'view',
    'editLayout:listview': 'list',
    'editLayout:quickcreate': 'plus',
    'editLayout:dashlet': 'list',
    'editLayout:dashletsearch': 'search',
    'editLayout:popuplist': 'list',
    'editLayout:popupsearch': 'search',
    'editLayout:basic_search': 'search',
    'editLayout:advanced_search': 'filter',
};

/**
 * The modules without a theme icon of their own, the icon used for them (as the legacy Studio
 * icons); the others without one get the basic module icon.
 */
export const STUDIO_MODULE_ICONS: { [module: string]: string } = {
    EmailTemplates: 'Emails',
    Employees: 'Users',
    Prospects: 'Targets',
};

export type StudioLabels = { [key: string]: string };

/**
 * The help of the legacy Studio screens, by group (studioWizard, labelsHelp, fieldsEditor).
 */
export type StudioHelp = { [group: string]: { [key: string]: string } };

export interface StudioModule {
    module: string;
    name: string;
}

/**
 * A component of a module (legacy Studio tree node): its legacy action, its help, its children
 * (layouts, subpanels) and for a subpanel its module.
 */
export interface StudioNode {
    name: string;
    action: string;
    help?: string;
    module?: string;
    children?: StudioNode[];
}

/**
 * A screen of the page (legacy Studio center panel): its title, path, question, help and the
 * icons to open.
 */
export interface StudioScreen {
    title: string;
    crumbs: { name: string; open?: () => void }[];
    question?: string;
    help: string;
    tiles?: StudioTile[];
}

export interface StudioTile {
    name: string;
    icon: string;
    help?: string;
    open: () => void;
}

export interface StudioModuleData {
    module: string;
    name: string;
    nodes: StudioNode[];
    labels: StudioLabels;
}

export interface StudioOption {
    value: string;
    label: string;
}

export interface StudioLabel {
    key: string;
    value: string;
}

export interface StudioLabelsData {
    language: string;
    languages: StudioOption[];
    types: StudioOption[];
    labels: StudioLabel[];
}

export interface StudioField {
    name: string;
    label: string;
    type: string;
    custom: boolean;
}

/**
 * A field of a list layout (legacy list layout editor): its label, the label the property
 * editor saves, its width.
 */
export interface StudioLayoutField {
    name: string;
    label: string;
    labelKey: string;
    labelEditable: boolean;
    width: string;
}

export interface StudioLayoutColumn {
    key: string;
    label: string;
    fields: StudioLayoutField[];
}

export interface StudioHistoryEntry {
    sid: string;
    date: string;
}

/**
 * A list layout (list view, subpanel, dashlet, popup, filter): its columns of fields, its
 * subpanel title and history.
 */
export interface StudioListLayout {
    module: string;
    view: string;
    subpanel: string;
    sid: string;
    columns: StudioLayoutColumn[];
    widths: boolean;
    minimum: boolean;
    subpanelTitle: { key: string; value: string } | null;
    history: StudioHistoryEntry[];
    restoreDefault: string;
}

/**
 * A slot of a grid layout: a field (its label, the label key saved, its tab order), a filler or
 * an empty slot.
 */
export interface StudioGridSlot {
    name: string;
    label: string;
    labelKey: string;
    tabindex: string;
}

export interface StudioGridPanel {
    id: string;
    label: string;
    labelEditable: boolean;
    newTab: boolean;
    panelDefault: string;
    rows: StudioGridSlot[][];
}

/**
 * A grid layout (edit view, detail view, quick create): its panels, the fields to add, its
 * history, Sync to Detail View.
 */
export interface StudioGridLayout {
    module: string;
    view: string;
    type: string;
    sid: string;
    copied: boolean;
    maxColumns: number;
    nextPanelId: number;
    sync: boolean | null;
    disabled: boolean;
    copyFromEditView: boolean;
    required: string[];
    history: StudioHistoryEntry[];
    restoreDefault: string;
    panels: StudioGridPanel[];
    available: StudioGridSlot[];
}

/**
 * A customization Reset Module removes (legacy view.resetmodule).
 */
export interface StudioResetAction {
    name: string;
    label: string;
}

/**
 * A customized module of Export Customizations: what is customized in it, and whether it can be
 * exported (legacy MBPackage::getCustomModules()).
 */
export interface StudioExportModule {
    module: string;
    name: string;
    items: string[];
    exportable: boolean;
}

/**
 * A relationship of a module (legacy relationships list): its name (marked * when made in
 * Studio), its modules and type, translated.
 */
export interface StudioRelationshipRow {
    name: string;
    lhsModule: string;
    type: string;
    rhsModule: string;
}

/**
 * A relationship in the legacy editor (legacy view.relationship).
 */
export interface StudioRelationship {
    name: string;
    lhsModule: string;
    rhsModule: string;
    type: string;
    lhsLabel: string;
    rhsLabel: string;
    lhsSubpanel: string;
    rhsSubpanel: string;
    readonly: boolean;
    fromStudio: boolean;
    relationshipOnly: boolean;
}

/**
 * The legacy editor of a relationship: the relationship, the types and related modules to
 * choose, the subpanels of each module, the languages of its labels.
 */
export interface StudioRelationshipEditor {
    relationship: StudioRelationship;
    isNew: boolean;
    lhsModuleLabel: string;
    rhsModuleLabel: string;
    types: StudioOption[];
    modules: StudioOption[];
    lhsSubpanels: string[];
    rhsSubpanels: string[];
    language: string;
    languages: StudioOption[];
}

/**
 * A view of the page to open: a component of the module (labels, fields) or a legacy Studio
 * action (mb).
 */
export interface StudioView {
    view: string;
    mb?: string;
    layout?: string;
    subpanel?: string;
}
