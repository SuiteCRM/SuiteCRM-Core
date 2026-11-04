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

import {htmlText, LegacyControl, legacyControl, legacyHelp, legacyScripts, legacyText, readLegacyControl, readLegacyControls} from './legacy-form';

/**
 * The pages of the legacy import wizard (module Import) read as data for the SuiteCRM 8
 * import page: each step is the legacy one, its form posted to its legacy action as the browser
 * posts it, and the page it answers read here (its values, texts and the data of its scripts).
 */

export const IMPORT_PROCESS = 'import-wizard';

export interface ImportPage {
    title: string;
    instruction: string;
    /** The controls of the form of the step, its hidden values too. */
    controls: LegacyControl[];
}

/**
 * An error page of the wizard (legacy error.tpl): its messages and the step to go back to.
 */
export interface ImportError {
    messages: string[];
    action: string;
    canCancel: boolean;
}

/**
 * The module to import into, from the import wizard of the administration (legacy step1).
 */
export interface ImportModulePage extends ImportPage {
    question: string;
}

export interface ImportChoice {
    control: LegacyControl;
    label: string;
    help: string;
}

export interface ImportSavedMap {
    control: LegacyControl;
    name: string;
    published: boolean;
    /** Publish (yes) or Un-Publish (no), none: not allowed. */
    publish: string;
    canDelete: boolean;
}

/**
 * The file to upload, the type of import and the saved import settings (legacy step2).
 */
export interface ImportUploadPage extends ImportPage {
    sampleLabel: string;
    sampleUrl: string;
    sampleHelp: string;
    fileLabel: string;
    fileHelp: string;
    fileHint: string;
    typeTitle: string;
    types: ImportChoice[];
    mapsTitle: string;
    mapsHelp: string;
    noMap: LegacyControl | null;
    maps: ImportSavedMap[];
    canGoBack: boolean;
}

export interface ImportProperty {
    label: string;
    control: LegacyControl;
    /** The control of its "Other" value. */
    other?: LegacyControl;
    help: string;
}

/**
 * The properties of the file uploaded and its first rows (legacy confirm).
 */
export interface ImportPropertiesPage extends ImportPage {
    sample: ImportSample;
    autoDetectError: string;
    /** The warning of a file of too many records, confirmed before going on. */
    maxRecordsWarning: string;
    /** The warnings of the file (its type), the import going on. */
    warnings: string[];
    propertiesHelp: string;
    properties: ImportProperty[];
    nameFormatHelp: string;
    sourcesTitle: string;
    sourcesHelp: string;
    sources: ImportChoice[];
    /** The properties of the files exported from the sources. */
    sourceProperties: { [source: string]: { delim: string; enclos: string; has_header: boolean } };
}

export interface ImportSample {
    hasHeader: boolean;
    missingHeader: string;
    rows: string[][];
}

export interface ImportMappingRow {
    index: number;
    header: string;
    field: LegacyControl;
    samples: string[];
    removable: boolean;
    defaults: LegacyControl[];
    loading?: boolean;
}

/**
 * A quick search of the legacy page (sqs_objects): the module and fields searched, and the
 * controls its results fill.
 */
export interface ImportQuickSearch {
    [key: string]: any;
    field_list: string[];
    populate_list: string[];
    conditions: { name: string; op: string; end: string; value: string }[];
}

/**
 * The fields of the module for the columns of the file (legacy step3).
 */
export interface ImportMappingPage extends ImportPage {
    hasHeader: boolean;
    headers: { label: string; help: string }[];
    rows: ImportMappingRow[];
    notes: string[];
    required: { [field: string]: string };
    quickSearches: { [key: string]: ImportQuickSearch };
}

/**
 * The fields to check for duplicates and the name to save the settings as (legacy dupcheck).
 */
export interface ImportDuplicatesPage extends ImportPage {
    enabled: { dupeVal: string; label: string }[];
    disabled: { dupeVal: string; label: string }[];
    enabledTitle: string;
    disabledTitle: string;
    saveLabel: string;
    saveHelp: string;
    fileTotal: number;
    recordCount: number;
    recordThreshold: number;
}

export interface ImportTable {
    tableID: string;
    columns: string[];
    rows: string[][];
    range: string;
    start: number | null;
    previous: number | null;
    next: number | null;
    end: number | null;
}

export interface ImportResultsTab {
    title: string;
    help: string;
    links: { label: string; url: string }[];
    table: ImportTable | null;
}

/**
 * The results of the import (legacy last).
 */
export interface ImportResultsPage extends ImportPage {
    summaryTitle: string;
    summary: string[];
    canUndo: boolean;
    canAddToProspectList: boolean;
    tabs: ImportResultsTab[];
    activeTab: number;
}

export interface ImportUndoPage extends ImportPage {
    message: string;
    againLabel: string;
}

/**
 * Posts values to the legacy import wizard, as its forms (the file uploaded too); the page of
 * the step only (to_pdf).
 */
export async function postImport(values: [string, string][], file: File | null = null): Promise<string> {
    const body = new FormData();
    values.forEach(([name, value]) => body.append(name, value));
    if (file) {
        body.append('userfile', file, file.name);
    }
    if (!body.has('to_pdf')) {
        body.append('to_pdf', '1');
    }
    const response = await fetch('legacy/index.php', {method: 'POST', body, credentials: 'same-origin'});
    if (!response.ok) {
        throw new Error(`${response.status}`);
    }

    return response.text();
}

/**
 * A page or an answer of the legacy import wizard.
 */
export async function getImport(params: { [key: string]: string }): Promise<string> {
    const response = await fetch('legacy/index.php?' + new URLSearchParams(params), {credentials: 'same-origin'});
    if (!response.ok) {
        throw new Error(`${response.status}`);
    }

    return response.text();
}

/**
 * A link of a legacy page (relative to the legacy app) for the SuiteCRM 8 page.
 */
export function legacyUrl(href: string): string {
    return new URL(href, new URL('legacy/index.php', document.baseURI)).href;
}

export function parseImportDocument(output: string): Document {
    return new DOMParser().parseFromString(output, 'text/html');
}

/**
 * The error page of the wizard (legacy error.tpl, a message box), or the message of a page
 * without its form (a file name not allowed); none for a page of a step.
 */
export function parseImportError(doc: Document, formId: string): ImportError | null {
    const scripts = legacyScripts(doc);
    const message = scripts.match(/var modalBod = "((?:[^"\\]|\\.)*)";/);
    if (message) {
        const html = message[1].replace(/\\(.)/g, '$1');
        const parts = parseImportDocument(html);
        const messages = Array.from(parts.querySelectorAll('p')).map(p => legacyText(p)).filter(text => text !== '');
        return {
            messages: messages.length ? messages : [htmlText(html)],
            action: scripts.match(/var actionVAR = '([^']*)'/)?.[1] ?? 'Step1',
            canCancel: !!scripts.match(/var showCancelVAR = '1'/)
        };
    }
    if (!doc.getElementById(formId)) {
        const text = legacyText(doc.body);
        return {messages: [text], action: 'Step1', canCancel: false};
    }

    return null;
}

/**
 * The warnings shown by a page of a step that goes on (legacy error.tpl with Cancel: the
 * warnings of the type of the file uploaded).
 */
function pageWarnings(doc: Document): string[] {
    const error = parseImportError(doc, '');

    return error && error.canCancel ? error.messages : [];
}

function pageBase(doc: Document, form: Element | null): ImportPage {
    return {
        // The path of the legacy title (Import » Step 1…) without the spaces of its separators.
        title: legacyText(doc.querySelector('.moduleTitle h2, h2.module-title-text')).replace(/\s*»\s*/g, ' » '),
        instruction: legacyText(doc.querySelector('.import_instruction')),
        controls: readLegacyControls(form)
    };
}

function choices(form: Element, name: string): ImportChoice[] {
    return Array.from(form.querySelectorAll(`input[type="radio"][name="${name}"]`)).map(radio => {
        const cell = radio.closest('td');
        return {
            control: controlOf(form, radio),
            label: legacyText(cell),
            help: legacyHelp(cell)
        };
    });
}

/**
 * The control read for an element of a form (the same in the controls of the page).
 */
const controlElements = new WeakMap<Element, LegacyControl>();

function controlOf(form: Element, element: Element): LegacyControl {
    return controlElements.get(element) ?? readLegacyControl(element);
}

function readFormControls(form: Element | null): LegacyControl[] {
    if (!form) {
        return [];
    }
    const controls = readLegacyControls(form);
    const elements = Array.from(form.querySelectorAll('input,select,textarea')).filter(element => {
        const type = (element.getAttribute('type') ?? '').toLowerCase();
        return !['submit', 'reset', 'image', 'file'].includes(type);
    });
    elements.forEach((element, index) => {
        if (controls[index]) {
            controlElements.set(element, controls[index]);
        }
    });

    return controls;
}

export function parseModulePage(output: string): ImportModulePage | ImportError {
    const doc = parseImportDocument(output);
    const error = parseImportError(doc, 'importstep1');
    if (error) {
        return error;
    }
    const form = doc.getElementById('importstep1');
    const page = pageBase(doc, form);
    page.controls = readFormControls(form);

    return {
        ...page,
        question: legacyText(form?.querySelector('h3, label, b')) || legacyText(form?.querySelector('td'))
    };
}

export function parseUploadPage(output: string): ImportUploadPage | ImportError {
    const doc = parseImportDocument(output);
    const error = parseImportError(doc, 'importstep2');
    if (error) {
        return error;
    }
    const form = doc.getElementById('importstep2') as Element;
    const page = pageBase(doc, form);
    page.controls = readFormControls(form);

    const sample = form.querySelector('a[onclick*="entryPoint=export"]');
    const sampleHref = (sample?.getAttribute('onclick') ?? '').match(/href='([^']+)'/)?.[1] ?? '';
    const fileCell = form.querySelector('#userfile')?.closest('td');
    const headings = Array.from(form.querySelectorAll('h3'));
    const maps: ImportSavedMap[] = [];
    let noMap: LegacyControl | null = null;
    form.querySelectorAll('input[type="radio"][name="source"]').forEach(radio => {
        const control = controlOf(form, radio);
        const row = radio.closest('tr');
        if (!control.value.startsWith('custom:')) {
            noMap = control;
            return;
        }
        const publish = row?.querySelector('input[name="publish"]');
        maps.push({
            control,
            name: legacyText(radio.closest('td')),
            published: (row?.id ?? '').startsWith('published_import'),
            publish: publish?.getAttribute('publish') ?? '',
            canDelete: !!row?.querySelector('input[name="delete"]')
        });
    });

    return {
        ...page,
        sampleLabel: legacyText(sample),
        sampleUrl: sampleHref ? legacyUrl(sampleHref) : '',
        sampleHelp: legacyHelp(sample?.closest('td')),
        fileLabel: legacyText(form.querySelector('label[for="userfile"]')),
        fileHelp: legacyHelp(fileCell),
        fileHint: legacyText(fileCell?.querySelector('.small')),
        typeTitle: legacyText(headings[0]),
        types: choices(form, 'type'),
        mapsTitle: headings.length > 1 ? legacyText(headings[1]) : '',
        mapsHelp: headings.length > 1 ? legacyHelp(headings[1]) : '',
        noMap,
        maps,
        canGoBack: !!form.querySelector('#goback')
    };
}

/**
 * The first rows of the file (legacy confirm_table.tpl).
 */
export function parseImportSample(root: ParentNode | null): ImportSample {
    const rows: string[][] = [];
    let hasHeader = true;
    let missingHeader = '';
    root?.querySelectorAll('#importTable tr, table tr').forEach((tr, index) => {
        const cells = Array.from(tr.querySelectorAll('td'));
        if (index === 0 && cells.length === 1 && cells[0].hasAttribute('colspan')) {
            hasHeader = false;
            missingHeader = legacyText(cells[0]);
            rows.push([]);
            return;
        }
        rows.push(cells.map(cell => legacyText(cell)));
    });

    return {hasHeader, missingHeader, rows};
}

export function parsePropertiesPage(output: string): ImportPropertiesPage | ImportError {
    const doc = parseImportDocument(output);
    const error = parseImportError(doc, 'importconfirm');
    if (error && !error.canCancel) {
        return error;
    }
    const form = doc.getElementById('importconfirm') as Element;
    if (!form) {
        return error as ImportError;
    }
    const page = pageBase(doc, form);
    page.controls = readFormControls(form);
    const scripts = legacyScripts(doc);

    const property = (id: string, otherId = ''): ImportProperty | null => {
        const element = form.querySelector('#' + id);
        if (!element) {
            return null;
        }
        const other = otherId ? form.querySelector('#' + otherId) : null;
        return {
            label: legacyText(form.querySelector(`label[for="${id}"]`)) || legacyText(element.closest('td')?.previousElementSibling),
            control: controlOf(form, element),
            other: other ? controlOf(form, other) : undefined,
            help: legacyHelp(element.closest('td'))
        };
    };
    const properties = [
        property('importlocale_charset'),
        property('custom_delimiter', 'custom_delimiter_other'),
        property('custom_enclosure', 'custom_enclosure_other'),
        property('has_header'),
        property('importlocale_dateformat'),
        property('importlocale_time_format'),
        property('importlocale_timezone'),
        property('currency_select'),
        property('sigDigits'),
        property('default_number_grouping_seperator'),
        property('default_decimal_seperator'),
        property('default_locale_name_format')
    ].filter((item): item is ImportProperty => !!item);

    let sourceProperties = {};
    try {
        sourceProperties = JSON.parse(scripts.match(/var import_mapping_js = (\{.*\});/)?.[1] ?? '{}');
    } catch (e) {
        sourceProperties = {};
    }
    const maxRecords = scripts.match(/if\s*\(\s*true\s*\)\s*\{\s*var contImport = confirm\('((?:[^'\\]|\\.)*)'\)/);
    const sourcesHeading = form.querySelector('label[for="external_source"]')?.closest('h3');

    return {
        ...page,
        sample: parseImportSample(doc.getElementById('confirm_table')),
        autoDetectError: legacyText(form.querySelector('.errorMessage')),
        maxRecordsWarning: maxRecords ? maxRecords[1].replace(/\\(.)/g, '$1') : '',
        warnings: pageWarnings(doc),
        propertiesHelp: legacyHelp(form.querySelector('#toggleImportOptions')?.closest('td')),
        properties,
        nameFormatHelp: legacyText(form.querySelector('#default_locale_name_format')?.closest('td')?.querySelector('i')),
        sourcesTitle: legacyText(sourcesHeading),
        sourcesHelp: legacyHelp(sourcesHeading),
        sources: choices(form, 'external_source'),
        sourceProperties
    };
}

/**
 * The quick searches of the relate fields of a page (sqs_objects['form_field'] = {…}).
 */
export function parseQuickSearches(scripts: string): { [key: string]: ImportQuickSearch } {
    const searches: { [key: string]: ImportQuickSearch } = {};
    const pattern = /sqs_objects\['([^']+)'\]\s*=\s*\{/g;
    let match: RegExpExecArray | null;
    while ((match = pattern.exec(scripts)) !== null) {
        const start = match.index + match[0].length - 1;
        const end = jsonEnd(scripts, start);
        if (end < 0) {
            continue;
        }
        try {
            searches[match[1]] = JSON.parse(scripts.slice(start, end + 1));
        } catch (e) {
        }
    }

    return searches;
}

/**
 * The end of the JSON object starting at a position (its closing brace).
 */
function jsonEnd(text: string, start: number): number {
    let depth = 0;
    let inString = false;
    for (let i = start; i < text.length; i++) {
        const char = text[i];
        if (inString) {
            if (char === '\\') {
                i++;
            } else if (char === '"') {
                inString = false;
            }
            continue;
        }
        if (char === '"') {
            inString = true;
        } else if (char === '{') {
            depth++;
        } else if (char === '}') {
            depth--;
            if (depth === 0) {
                return i;
            }
        }
    }

    return -1;
}

export function parseMappingPage(output: string): ImportMappingPage | ImportError {
    const doc = parseImportDocument(output);
    const error = parseImportError(doc, 'importstep3');
    if (error) {
        return error;
    }
    const form = doc.getElementById('importstep3') as Element;
    const table = form.querySelector('#importTable');
    const page = pageBase(doc, null);
    // The values of the form outside the table of the fields (the step, the file, its properties).
    page.controls = Array.from(form.querySelectorAll('input[type="hidden"]'))
        .filter(element => !table?.contains(element))
        .map(element => readLegacyControl(element) as LegacyControl);

    const rows: ImportMappingRow[] = [];
    table?.querySelectorAll('select[name^="colnum_"]').forEach(select => {
        const index = Number((select.getAttribute('name') ?? '').slice(7));
        const samples = [doc.getElementById(`row_${index}_col_1`), doc.getElementById(`row_${index}_col_2`)]
            .filter((cell): cell is HTMLElement => !!cell)
            .map(cell => legacyText(cell));
        rows.push({
            index,
            header: legacyText(doc.getElementById(`row_${index}_header`)),
            field: readLegacyControl(select) as LegacyControl,
            samples,
            removable: !!doc.getElementById(`deleterow_${index}`),
            defaults: readDefaultControls(doc.getElementById(`defaultvaluepicker_${index}`))
        });
    });

    const scripts = legacyScripts(doc);
    const required: { [field: string]: string } = {};
    const pattern = /required\['([^']+)'\] = '((?:[^'\\]|\\.)*)';/g;
    let match: RegExpExecArray | null;
    while ((match = pattern.exec(scripts)) !== null) {
        required[match[1]] = match[2].replace(/\\(.)/g, '$1');
    }

    return {
        ...page,
        instruction: legacyText(doc.querySelector('.import_instruction')),
        hasHeader: legacyControl(page.controls, 'has_header')?.value === 'on',
        headers: Array.from(table?.querySelectorAll('tr:first-child th') ?? []).map(th => ({
            label: legacyText(th.querySelector('b') ?? th),
            help: legacyHelp(th)
        })),
        rows,
        notes: Array.from(doc.querySelectorAll('#importNotes li')).map(li => legacyText(li)),
        required,
        quickSearches: parseQuickSearches(scripts)
    };
}

export function parseDefaultControls(output: string): LegacyControl[] {
    return readDefaultControls(parseImportDocument(output).body);
}

/**
 * The controls of the default value of a field (legacy getControl(): the SugarField of the field
 * in the import view). The date of a date and time field has no name: its hidden value is posted,
 * made of the date and of a time added here (legacy Datetimecombo).
 */
export function readDefaultControls(root: Element | null): LegacyControl[] {
    const controls = readLegacyControls(root);
    const formats = Array.from(root?.querySelectorAll('.dateFormat') ?? []).map(format => legacyText(format));
    controls.filter(control => /\b(date_input|datetimecombo_date)\b/.test(control.className))
        .forEach(control => control.placeholder = formats[0] ?? '');
    controls.filter(control => control.kind === 'hidden' && /\bDateTimeCombo\b/.test(control.className)).forEach(combo => {
        const date = controls.find(control => control.id === combo.id + '_date');
        if (!date) {
            return;
        }
        const [day, ...time] = combo.value.split(' ');
        date.value = day ?? '';
        const timeControl = {
            ...date,
            uid: date.uid + '-time',
            name: '',
            id: combo.id + '_import_time',
            value: time.join(' '),
            className: 'import-wizard-time',
            placeholder: formats[1] ?? ''
        };
        controls.splice(controls.indexOf(date) + 1, 0, timeControl);
    });

    return controls;
}

export function parseDuplicatesPage(output: string): ImportDuplicatesPage | ImportError {
    const doc = parseImportDocument(output);
    const error = parseImportError(doc, 'importstepdup');
    if (error) {
        return error;
    }
    const form = doc.getElementById('importstepdup') as Element;
    const page = pageBase(doc, form);
    const scripts = legacyScripts(doc);
    const list = (name: string): { dupeVal: string; label: string }[] => {
        try {
            const items = JSON.parse(scripts.match(new RegExp(`var ${name} = (.*);`))?.[1] ?? '[]');
            return Array.isArray(items) ? items.filter(item => item?.dupeVal) : [];
        } catch (e) {
            return [];
        }
    };
    const number = (name: string): number => Number(scripts.match(new RegExp(`this\\.${name}\\s*=\\s*(\\d+);`))?.[1] ?? 0);
    const text = (name: string): string => (scripts.match(new RegExp(`var ${name} = '((?:[^'\\\\]|\\\\.)*)';`))?.[1] ?? '').replace(/\\(.)/g, '$1');
    const saveLabel = form.querySelector('label[for="save_map_as"]');

    return {
        ...page,
        enabled: list('enabled_dupes'),
        disabled: list('disabled_dupes'),
        enabledTitle: text('lblEnabled'),
        disabledTitle: text('lblDisabled'),
        saveLabel: legacyText(saveLabel),
        saveHelp: legacyHelp(saveLabel?.closest('span')),
        fileTotal: number('fileTotal'),
        recordCount: number('recordCount'),
        recordThreshold: number('recordThreshold') || 1
    };
}

/**
 * A table of the rows of the file not imported or duplicates (legacy ImportListView).
 */
export function parseImportTable(root: ParentNode | null, tableID: string): ImportTable | null {
    const table = root?.querySelector('table.list');
    if (!table) {
        return null;
    }
    const offset = (id: string): number | null => {
        const button = table.querySelector('#' + id);
        if (!button || button.hasAttribute('disabled')) {
            return null;
        }
        const match = (button.getAttribute('onclick') ?? '').match(/getTable\(\s*"[^"]*"\s*,\s*(-?\d+)\s*\)/);
        return match ? Number(match[1]) : null;
    };
    const rows = Array.from(table.querySelectorAll(':scope > tbody > tr, :scope > tr'))
        .filter(tr => !tr.classList.contains('pagination') && tr.querySelector('td') && !tr.querySelector('th'));

    return {
        tableID,
        columns: Array.from(table.querySelectorAll('th')).map(th => legacyText(th)),
        rows: rows.filter(tr => !tr.querySelector('td[colspan]') || tr.querySelectorAll('td').length > 1)
            .map(tr => Array.from(tr.querySelectorAll(':scope > td')).map(td => legacyText(td))),
        range: legacyText(table.querySelector('.pageNumbers')),
        start: offset('listViewStartButton'),
        previous: offset('listViewPrevButton'),
        next: offset('listViewNextButton'),
        end: offset('listViewEndButton')
    };
}

export function parseResultsPage(output: string): ImportResultsPage | ImportError {
    const doc = parseImportDocument(output);
    const error = parseImportError(doc, 'importlast');
    if (error) {
        return error;
    }
    const form = doc.getElementById('importlast') as Element;
    const page = pageBase(doc, form);
    const summary = doc.querySelector('span[style*="font-size"]');
    const tab = (index: number, tableID: string): ImportResultsTab => {
        const div = doc.getElementById(`pageNumIW_${index}_div`);
        const tableRoot = tableID ? doc.getElementById(`${tableID}_table`) : null;
        const help = div?.cloneNode(true) as Element | undefined;
        help?.querySelectorAll('a, div, style, script, table').forEach(element => element.remove());
        return {
            title: legacyText(doc.getElementById(`pageNum_${index}_title_text`)),
            help: tableID ? legacyText(help) : '',
            links: tableID ? Array.from(div?.querySelectorAll('a[href]') ?? [])
                .filter(link => !tableRoot?.contains(link))
                .map(link => ({label: legacyText(link), url: legacyUrl(link.getAttribute('href') ?? '')})) : [],
            table: tableID ? parseImportTable(tableRoot, tableID === 'errors' ? 'errors' : 'dup') : null
        };
    };
    // The tab the legacy page opens on: the errors or duplicates when nothing was imported.
    const toggles = legacyScripts(doc).split('SUGAR.IV.togglePages(\'');

    return {
        ...page,
        summaryTitle: legacyText(doc.querySelector('h2 > p')),
        summary: (summary?.innerHTML ?? '').split(/<br\s*\/?>/i).map(html => htmlText(html)).filter(text => text !== ''),
        canUndo: !!form.querySelector('#undo'),
        canAddToProspectList: !!form.querySelector('#select_button'),
        tabs: [tab(0, ''), tab(1, 'dup'), tab(2, 'errors')],
        activeTab: Number(toggles[toggles.length - 1].match(/^(\d)'\)/)?.[1] ?? 0)
    };
}

export function parseUndoPage(output: string): ImportUndoPage | ImportError {
    const doc = parseImportDocument(output);
    const error = parseImportError(doc, 'importundo');
    if (error) {
        return error;
    }
    const form = doc.getElementById('importundo') as Element;
    const page = pageBase(doc, form);

    return {
        ...page,
        message: legacyText(doc.querySelector('.screen h3')),
        againLabel: htmlText(form.querySelector('input[name="button"]')?.getAttribute('value') ?? '')
    };
}

export function isImportError(page: any): page is ImportError {
    return !!page && Array.isArray(page.messages);
}
