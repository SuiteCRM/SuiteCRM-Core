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
 * The controls of a legacy form (inputs, lists, check boxes, text areas) read as data
 * for a SuiteCRM 8 page, and their values posted as the browser posts the legacy form.
 */

export type LegacyControlKind = 'select' | 'text' | 'checkbox' | 'radio' | 'textarea' | 'hidden' | 'button';

export interface LegacyOption {
    value: string;
    label: string;
    title?: string;
    required?: boolean;
}

export interface LegacyControl {
    /** Its id in the page. */
    uid: string;
    kind: LegacyControlKind;
    name: string;
    id: string;
    /** The value of an input or a single list; the label of a button. */
    value: string;
    checked: boolean;
    disabled: boolean;
    readonly: boolean;
    multiple: boolean;
    options: LegacyOption[];
    /** The items chosen in a list of several items. */
    selected: string[];
    maxlength: number | null;
    className: string;
    title: string;
    onclick: string;
    /** Shown in the legacy page (not in a part it hides). */
    shown: boolean;
    /** The format of its value (a date), shown in the empty control. */
    placeholder?: string;
}

let controlCount = 0;

/**
 * The controls of an element of a legacy page, in their order (its buttons too, not posted).
 * The controls of a part hidden by the page are kept for their values (the browser posts them).
 */
export function readLegacyControls(root: Element | null): LegacyControl[] {
    const controls: LegacyControl[] = [];
    if (!root) {
        return controls;
    }
    root.querySelectorAll('input,select,textarea').forEach(element => {
        const control = readLegacyControl(element);
        if (control) {
            control.shown = control.kind !== 'hidden' && !isHidden(element, root);
            controls.push(control);
        }
    });

    return controls;
}

export function readLegacyControl(element: Element): LegacyControl | null {
    const tag = element.tagName;
    const type = (element.getAttribute('type') ?? 'text').toLowerCase();
    const control: LegacyControl = {
        uid: 'legacy-control-' + (++controlCount),
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
        className: element.getAttribute('class') ?? '',
        title: element.getAttribute('title') ?? '',
        onclick: element.getAttribute('onclick') ?? '',
        shown: true
    };

    if (tag === 'SELECT') {
        control.kind = 'select';
        const options = Array.from(element.querySelectorAll('option'));
        control.options = options.map(option => ({
            value: optionValue(option),
            label: (option.textContent ?? '').trim(),
            title: option.getAttribute('title') ?? '',
            required: /\brequired\b/.test(option.getAttribute('class') ?? '')
        }));
        const chosen = options.filter(option => option.hasAttribute('selected')).map(optionValue);
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

/**
 * The values of controls as the browser posts them: the enabled controls with a name, the
 * check boxes and radios checked, the items chosen in the lists.
 */
export function serializeLegacyControls(controls: LegacyControl[]): [string, string][] {
    const values: [string, string][] = [];
    controls.forEach(control => {
        if (control.disabled || !control.name || control.kind === 'button') {
            return;
        }
        switch (control.kind) {
            case 'checkbox':
            case 'radio':
                if (control.checked) {
                    values.push([control.name, control.value]);
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

export function legacyControl(controls: LegacyControl[], idOrName: string): LegacyControl | undefined {
    return controls.find(control => control.id === idOrName) ?? controls.find(control => control.name === idOrName);
}

/**
 * The text of an element, its spaces as shown.
 */
export function legacyText(element: Element | null | undefined): string {
    return (element?.textContent ?? '').replace(/\s+/g, ' ').trim();
}

/**
 * The help of a legacy help icon (sugar_help: SUGAR.util.showHelpTips(this, '…')).
 */
export function legacyHelp(element: Element | null | undefined): string {
    const icon = element?.querySelector('img.inlineHelpTip,img[onclick*="showHelpTips"]');
    const match = (icon?.getAttribute('onclick') ?? '').match(/showHelpTips\(this,\s*'((?:[^'\\]|\\.)*)'/);

    return match ? htmlText(match[1].replace(/\\(.)/g, '$1')) : '';
}

/**
 * The text of a piece of HTML.
 */
export function htmlText(html: string): string {
    const doc = new DOMParser().parseFromString('<body>' + html + '</body>', 'text/html');

    return legacyText(doc.body);
}

/**
 * The scripts of a legacy page, as one text.
 */
export function legacyScripts(doc: Document): string {
    return Array.from(doc.querySelectorAll('script')).map(script => script.textContent ?? '').join('\n');
}

function optionValue(option: Element): string {
    return option.hasAttribute('value') ? option.getAttribute('value') ?? '' : (option.textContent ?? '');
}

function isHidden(element: Element, root: Element): boolean {
    for (let node: Element | null = element; node && node !== root.parentElement; node = node.parentElement) {
        if (/display\s*:\s*none/i.test(node.getAttribute('style') ?? '')) {
            return true;
        }
    }

    return false;
}
