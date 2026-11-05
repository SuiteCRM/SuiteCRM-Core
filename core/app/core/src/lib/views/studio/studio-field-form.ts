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
 * The legacy field editor of Studio (view.modulefield and the DynamicFields form of
 * each data type) read as data for the SuiteCRM 8 page: its rows (label, then the inputs, lists,
 * check boxes and texts of the row), its hidden values, its buttons and its checks. Its form is
 * posted as the browser posts it, to the legacy action of the form, so a field is saved as the
 * legacy editor saves it.
 */

export type StudioFieldControlKind = 'select' | 'text' | 'checkbox' | 'radio' | 'textarea' | 'hidden' | 'button';

export interface StudioFieldControl {
    /** Its id in the page. */
    uid: string;
    kind: StudioFieldControlKind;
    name: string;
    id: string;
    /** The value of an input or a single list; the label of a button. */
    value: string;
    checked: boolean;
    disabled: boolean;
    readonly: boolean;
    multiple: boolean;
    options: { value: string; label: string }[];
    /** The items chosen in a list of several items. */
    selected: string[];
    maxlength: number | null;
    onclick: string;
}

export type StudioFieldPart = { control: StudioFieldControl } | { text: string } | { br: true };

export interface StudioFieldRow {
    id: string;
    label: string;
    hidden: boolean;
    parts: StudioFieldPart[];
}

/**
 * A check of the legacy form (addToValidate, addToValidateIsInArray): the field, its kind
 * (DBName, int, float…, in_array), whether a value is required, its message and, for in_array,
 * the values not allowed.
 */
export interface StudioFieldRule {
    name: string;
    type: string;
    required: boolean;
    message: string;
    values?: string[];
    upper?: boolean;
}

export interface StudioFieldForm {
    title: string;
    /** The legacy action of the form (saveField, saveSugarField). */
    action: string;
    controls: StudioFieldControl[];
    rows: StudioFieldRow[];
    canSave: boolean;
    canDelete: boolean;
    canClone: boolean;
    /** The confirmation of Delete, from the legacy button. */
    deleteConfirm: string;
    rules: StudioFieldRule[];
}

/**
 * The legacy field editor of a field (a new one without name, of a type), as an AjaxCompose
 * answer of the legacy action (modulefield, CloneField); null when it has no editor.
 */
export function parseStudioFieldForm(output: string): StudioFieldForm | null {
    const start = output.indexOf('{"');
    if (start < 0) {
        return null;
    }
    let sections: any;
    try {
        sections = JSON.parse(output.slice(start));
    } catch (e) {
        return null;
    }
    const section = sections?.east?.content ? sections.east : null;
    if (!section) {
        return null;
    }

    const doc = new DOMParser().parseFromString(String(section.content), 'text/html');
    const form = doc.querySelector('form[name="popup_form"]') as HTMLFormElement;
    if (!form) {
        return null;
    }

    const controls: StudioFieldControl[] = [];
    const rows: StudioFieldRow[] = [];
    const inRow = new Set<Element>();
    form.querySelectorAll('tr').forEach(tr => {
        // The rows of the form, not the rows holding a table of rows.
        if (tr.querySelector('tr')) {
            return;
        }
        const cells = Array.from(tr.children).filter(cell => cell.tagName === 'TD' || cell.tagName === 'TH');
        if (!cells.length) {
            return;
        }
        // The label: the cells before the first one with a control (a group and its label in
        // some forms, as the image field), else all the cells but the last one (a value shown).
        const first = cells.findIndex(cell => !!cell.querySelector('input,select,textarea'));
        const labelCells = first > 0 ? cells.slice(0, first) : first < 0 && cells.length > 1 ? cells.slice(0, -1) : [];
        const parts: StudioFieldPart[] = [];
        cells.slice(labelCells.length).forEach(cell => readParts(cell, parts, controls, inRow));
        if (!parts.length) {
            return;
        }
        const label = labelCells.map(cell => (cell.textContent ?? '').replace(/\s+/g, ' ').trim().replace(/:$/, ''))
            .filter(text => text !== '')
            .join(' › ');
        rows.push({
            id: tr.id,
            label,
            hidden: /display\s*:\s*none/i.test(tr.getAttribute('style') ?? ''),
            parts
        });
    });

    // The values of the form outside its rows (module, action, view_module…), its buttons left out.
    Array.from(form.querySelectorAll('input,select,textarea')).filter(element => !inRow.has(element)).forEach(element => {
        const control = readControl(element);
        if (control && control.kind !== 'button') {
            controls.push(control);
        }
    });

    const buttons = Array.from(form.querySelectorAll('input[type="button"]')).filter(element => !inRow.has(element));
    const button = (name: string): Element | undefined => buttons.find(element => element.getAttribute('name') === name);
    const deleteButton = button('fdeletebtn');
    const scripts = Array.from(doc.querySelectorAll('script')).map(script => script.textContent ?? '').join('\n');

    return {
        title: String(section.title ?? ''),
        action: controls.find(control => control.kind === 'hidden' && control.name === 'action')?.value ?? 'saveField',
        controls,
        rows,
        canSave: !!button('fsavebtn') || !!button('lsavebtn'),
        canDelete: !!deleteButton,
        canClone: !!button('fclonebtn'),
        // Written for a JS string in the legacy button (\n for its line breaks).
        deleteConfirm: ((deleteButton?.getAttribute('onclick') ?? '').match(/confirm\("((?:[^"\\]|\\.)*)"\)/)?.[1] ?? '')
            .replace(/\\n/g, '\n').replace(/\\(.)/g, '$1'),
        rules: readRules(scripts)
    };
}

/**
 * The values of the form as the browser posts them: the enabled controls with a name, the
 * check boxes and radios checked, the items chosen in the lists.
 */
export function serializeStudioFieldForm(form: StudioFieldForm): [string, string][] {
    const values: [string, string][] = [];
    form.controls.forEach(control => {
        if (control.disabled || !control.name || control.kind === 'button') {
            return;
        }
        switch (control.kind) {
            case 'checkbox':
            case 'radio':
                if (control.checked) {
                    values.push([control.name, control.value || 'on']);
                }
                break;
            case 'select':
                if (control.multiple) {
                    control.selected.forEach(value => values.push([control.name, value]));
                } else if (control.options.length) {
                    const value = control.options.some(option => option.value === control.value) ? control.value : control.options[0].value;
                    values.push([control.name, value]);
                }
                break;
            default:
                values.push([control.name, control.value]);
        }
    });

    return values;
}

/**
 * The messages of the checks of the legacy form the values do not pass (sugar_3.js validators).
 */
export function validateStudioFieldForm(form: StudioFieldForm): string[] {
    const errors: string[] = [];
    form.rules.forEach(rule => {
        const control = form.controls.find(item => item.name === rule.name && item.kind !== 'button');
        if (!control || control.disabled) {
            return;
        }
        const value = (control.kind === 'select' && control.multiple ? control.selected.join(',') : control.value ?? '').trim();
        if (value === '') {
            if (rule.required && rule.type !== 'in_array') {
                errors.push(rule.message);
            }
            return;
        }
        const valid = rule.type === 'in_array'
            ? !(rule.values ?? []).some(item => rule.upper ? item.toUpperCase() === value.toUpperCase() : item === value)
            : rule.type === 'DBName' ? /^[a-zA-Z][a-zA-Z_0-9]*$/.test(value)
                : rule.type === 'int' ? /^[+-]?[0-9]*$/.test(value)
                    : ['float', 'decimal', 'currency'].includes(rule.type) ? /^[+-]?[0-9]*\.?[0-9]*$/.test(value)
                        : true;
        if (!valid) {
            errors.push(rule.message);
        }
    });

    return Array.from(new Set(errors));
}

export function studioFieldControl(form: StudioFieldForm, idOrName: string): StudioFieldControl | undefined {
    return form.controls.find(control => control.id === idOrName) ?? form.controls.find(control => control.name === idOrName);
}

/**
 * The texts and controls of a cell, in their order. The controls of a part hidden by the legacy
 * form are kept for its values (the browser posts them), not shown.
 */
function readParts(node: Node, parts: StudioFieldPart[], controls: StudioFieldControl[], inRow: Set<Element>, shown = true): void {
    node.childNodes.forEach(child => {
        if (child.nodeType === Node.TEXT_NODE) {
            const text = (child.textContent ?? '').replace(/\s+/g, ' ').trim();
            if (text && shown) {
                parts.push({text});
            }
            return;
        }
        if (child.nodeType !== Node.ELEMENT_NODE) {
            return;
        }
        const element = child as Element;
        if (['SCRIPT', 'STYLE', 'IMG'].includes(element.tagName)) {
            return;
        }
        if (element.tagName === 'BR') {
            if (shown) {
                parts.push({br: true});
            }
            return;
        }
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(element.tagName)) {
            const control = readControl(element);
            if (control) {
                controls.push(control);
                inRow.add(element);
                if (control.kind !== 'hidden' && shown) {
                    parts.push({control});
                }
            }
            return;
        }
        readParts(element, parts, controls, inRow, shown && !/display\s*:\s*none/i.test(element.getAttribute('style') ?? ''));
    });
}

let controlCount = 0;

function readControl(element: Element): StudioFieldControl | null {
    const tag = element.tagName;
    const type = (element.getAttribute('type') ?? 'text').toLowerCase();
    const control: StudioFieldControl = {
        uid: 'studio-field-' + (++controlCount),
        kind: 'text',
        name: element.getAttribute('name') ?? '',
        id: element.getAttribute('id') ?? '',
        value: '',
        checked: element.hasAttribute('checked'),
        disabled: element.hasAttribute('disabled'),
        readonly: element.hasAttribute('readonly'),
        multiple: element.hasAttribute('multiple'),
        options: [],
        selected: [],
        maxlength: element.hasAttribute('maxlength') ? Number(element.getAttribute('maxlength')) || null : null,
        onclick: element.getAttribute('onclick') ?? ''
    };

    if (tag === 'SELECT') {
        control.kind = 'select';
        const options = Array.from(element.querySelectorAll('option'));
        control.options = options.map(option => ({value: option.getAttribute('value') ?? option.textContent ?? '', label: (option.textContent ?? '').trim()}));
        const chosen = options.filter(option => option.hasAttribute('selected')).map(option => option.getAttribute('value') ?? option.textContent ?? '');
        control.selected = control.multiple ? chosen : [];
        control.value = control.multiple ? '' : chosen[chosen.length - 1] ?? control.options[0]?.value ?? '';
        return control;
    }
    if (tag === 'TEXTAREA') {
        control.kind = 'textarea';
        control.value = element.textContent ?? '';
        return control;
    }
    if (['submit', 'reset', 'image', 'file'].includes(type)) {
        return null;
    }
    control.kind = type === 'button' ? 'button' : type === 'checkbox' ? 'checkbox' : type === 'radio' ? 'radio' : type === 'hidden' ? 'hidden' : 'text';
    control.value = element.getAttribute('value') ?? (control.kind === 'checkbox' || control.kind === 'radio' ? 'on' : '');

    return control;
}

function readRules(scripts: string): StudioFieldRule[] {
    const rules: StudioFieldRule[] = [];
    const unquote = (text: string): string => text.replace(/\\(.)/g, '$1');
    const all = (pattern: RegExp): RegExpExecArray[] => {
        const matches: RegExpExecArray[] = [];
        let match: RegExpExecArray | null;
        while ((match = pattern.exec(scripts)) !== null) {
            matches.push(match);
        }

        return matches;
    };
    for (const match of all(/addToValidate\(\s*'popup_form'\s*,\s*'([^']+)'\s*,\s*'([^']+)'\s*,\s*(true|false)\s*,\s*'((?:[^'\\]|\\.)*)'/g)) {
        rules.push({name: match[1], type: match[2], required: match[3] === 'true', message: unquote(match[4])});
    }
    for (const match of all(/addToValidateIsInArray\(\s*'popup_form'\s*,\s*'([^']+)'\s*,\s*'in_array'\s*,\s*(true|false)\s*,\s*'((?:[^'\\]|\\.)*)'\s*,\s*'((?:[^'\\]|\\.)*)'\s*,\s*'([^']*)'\s*\)/g)) {
        let values: string[] = [];
        try {
            values = JSON.parse(unquote(match[4]));
        } catch (e) {
            values = [];
        }
        rules.push({name: match[1], type: 'in_array', required: match[2] === 'true', message: unquote(match[3]), values, upper: match[5].startsWith('u')});
    }

    return rules;
}
