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

import {ChangeDetectorRef, Component, EventEmitter, Input, OnChanges, OnDestroy, Output, SimpleChanges} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {StudioLabels} from '../../studio.model';
import {
    parseStudioFieldForm,
    serializeStudioFieldForm,
    StudioFieldControl,
    StudioFieldForm,
    StudioFieldPart,
    StudioFieldRow,
    studioFieldControl,
    validateStudioFieldForm
} from '../../studio-field-form';

/**
 * The field editor of Studio (legacy view.modulefield): the legacy editor of a field,
 * or of a new one of a data type, shown in the page with its rows, hidden values, buttons and
 * checks. What its scripts do is done here: another data type loads the editor of that type, the
 * name of a new field gives its labels, a dropdown list gives the default values to choose, the
 * URL generation, the default time, the HTML default. Save, Clone and Delete post the legacy form
 * to its action, as the legacy editor: the field is saved, cloned or deleted as it does it.
 */
@Component({
    selector: 'scrm-studio-field-editor',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './studio-field-editor.component.html',
    styleUrls: []
})
export class StudioFieldEditorComponent implements OnChanges, OnDestroy {

    @Input() module = '';
    /** The field edited, none for a new field. */
    @Input() field = '';
    @Input() labels: StudioLabels = {};
    /** The editor closed: true when the fields changed. */
    @Output() closed = new EventEmitter<boolean>();

    form: StudioFieldForm = null;
    errors: string[] = [];
    confirmDelete = false;
    loading = false;

    protected destroyed = false;
    protected request = 0;

    constructor(
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes.module || changes.field) {
            this.load(this.field, '');
        }
    }

    ngOnDestroy(): void {
        this.destroyed = true;
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    isControl(part: StudioFieldPart): part is { control: StudioFieldControl } {
        return 'control' in part;
    }

    isText(part: StudioFieldPart): part is { text: string } {
        return 'text' in part;
    }

    get shownRows(): StudioFieldRow[] {
        return (this.form?.rows ?? []).filter(row => !row.hidden);
    }

    /** The first control of a row, for its label. */
    rowControlId(row: StudioFieldRow): string | null {
        const part = row.parts.find(item => this.isControl(item) && item.control.kind !== 'button') as { control: StudioFieldControl } | undefined;

        return part?.control.uid ?? null;
    }

    /** A radio checked: the others of its name unchecked. */
    checkRadio(control: StudioFieldControl): void {
        this.form.controls.filter(item => item.kind === 'radio' && item.name === control.name).forEach(item => item.checked = item === control);
        this.changed(control);
    }

    /**
     * What the scripts of the legacy editor do when a control changes.
     */
    changed(control: StudioFieldControl): void {
        if (!this.form) {
            return;
        }
        if (control.kind === 'select' && control.name === 'type' && control.id === 'type') {
            this.load(this.field, control.value);
            return;
        }
        if (control.id === 'field_name_id') {
            // The labels of a new field from its name (legacy coreTop.tpl).
            const name = control.value;
            const key = studioFieldControl(this.form, 'label_key_id');
            const text = studioFieldControl(this.form, 'label_value_id');
            if (key && !key.disabled) {
                key.value = 'LBL_' + name.toUpperCase();
            }
            if (text) {
                text.value = name.replace(/_+/g, ' ').trim();
            }
            control.value = name.toLowerCase();
            return;
        }
        if (control.id === 'options' && control.kind === 'select') {
            this.loadDefaults(control.value);
            return;
        }
        if (['defaultTime_hours', 'defaultTime_minutes', 'defaultTime_meridiem'].includes(control.id)) {
            // The default time of a date time (legacy datetimecombo.tpl).
            const part = (id: string): string => studioFieldControl(this.form, id)?.value ?? '';
            const time = studioFieldControl(this.form, 'defaultTime');
            if (time) {
                time.value = part('defaultTime_hours') + ':' + part('defaultTime_minutes') + part('defaultTime_meridiem');
            }
            return;
        }
        if (control.id === 'gencheck') {
            // Generate URL (legacy url.tpl, iframe.tpl).
            const gen = studioFieldControl(this.form, 'ext3');
            if (gen) {
                gen.value = control.checked ? '1' : '0';
            }
            const helper = this.form.rows.find(row => row.id === 'fieldListHelper');
            if (helper) {
                helper.hidden = !control.checked;
            }
        }
    }

    /**
     * The buttons of a row: the dropdown lists in the Dropdown Editor, a field inserted in the
     * generated URL.
     */
    clicked(control: StudioFieldControl): void {
        if (!this.form) {
            return;
        }
        if (control.onclick.includes('moduleDropDown')) {
            window.open('#/module-builder/index?type=dropdowns', '_blank');
            return;
        }
        if (control.onclick.includes('fieldListOptions')) {
            const target = studioFieldControl(this.form, 'default');
            const field = studioFieldControl(this.form, 'fieldListOptions');
            if (target && field) {
                target.value += '{' + field.value + '}';
            }
        }
    }

    cancel(): void {
        this.closed.emit(false);
    }

    save(): void {
        if (!this.form?.canSave || this.loading) {
            return;
        }
        this.beforePost();
        this.errors = validateStudioFieldForm(this.form);
        if (this.errors.length) {
            return;
        }
        this.post(this.form.action, true);
    }

    clone(): void {
        if (!this.form?.canClone || this.loading) {
            return;
        }
        this.beforePost();
        this.post('CloneField', false);
    }

    delete(): void {
        if (!this.form?.canDelete || this.loading) {
            return;
        }
        this.post('DeleteField', true);
    }

    trackRow(index: number, row: StudioFieldRow): string {
        return row.id || row.label + '|' + index;
    }

    /**
     * The values the scripts of the legacy editor set before a save: the HTML default, the
     * required check of an HTML field (legacy html.tpl, textblock.tpl).
     */
    protected beforePost(): void {
        const html = studioFieldControl(this.form, 'htmlarea');
        const ext4 = studioFieldControl(this.form, 'ext4');
        if (html && ext4) {
            ext4.value = html.value;
        }
    }

    /**
     * The legacy editor of a field (a new one of a type), as the legacy Studio loads it
     * (ModuleBuilder.moduleLoadField()).
     */
    protected async load(field: string, type: string): Promise<void> {
        const request = ++this.request;
        this.loading = true;
        this.errors = [];
        this.confirmDelete = false;
        this.cdr.detectChanges();
        try {
            const output = await this.legacy('legacy/index.php?' + new URLSearchParams({
                module: 'ModuleBuilder', action: 'modulefield', view_package: '', view_module: this.module, field, type: type || '0', to_pdf: '1'
            }));
            if (request !== this.request || this.destroyed) {
                return;
            }
            const form = parseStudioFieldForm(output);
            if (!form) {
                throw new Error('No field editor');
            }
            this.setForm(form);
        } catch (e) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
        } finally {
            if (request === this.request) {
                this.loading = false;
                this.cdr.detectChanges();
            }
        }
    }

    /**
     * The items of a dropdown list as default values (ModuleBuilder.dropdownChanged()).
     */
    protected async loadDefaults(list: string): Promise<void> {
        const defaults = studioFieldControl(this.form, 'default[]');
        if (!defaults) {
            return;
        }
        defaults.options = [];
        defaults.selected = [];
        defaults.value = '';
        try {
            const output = await this.legacy('legacy/index.php?' + new URLSearchParams({
                module: 'ModuleBuilder', action: 'get_app_list_string', key: list, view_package: '', view_module: this.module, to_pdf: '1'
            }));
            const items = JSON.parse(output.slice(Math.max(0, output.indexOf('{'))) || '{}');
            defaults.options = Object.entries(items ?? {}).map(([value, label]) => ({value, label: String(label)}));
            defaults.value = defaults.options[0]?.value ?? '';
        } catch (e) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
        }
        this.cdr.detectChanges();
    }

    protected setForm(form: StudioFieldForm): void {
        // The labels of a new flex relate (legacy parent.tpl).
        const type = form.controls.find(control => control.kind === 'hidden' && control.name === 'type')?.value;
        if (type === 'parent') {
            const key = studioFieldControl(form, 'label_key_id');
            const text = studioFieldControl(form, 'label_value_id');
            if (key && key.value === '') {
                key.value = 'LBL_FLEX_RELATE';
            }
            if (text && text.value === '') {
                text.value = 'Flex Relate';
            }
        }
        // No required check for an HTML field (legacy html.tpl, textblock.tpl).
        if (studioFieldControl(form, 'htmlarea')) {
            const required = form.controls.find(control => control.name === 'required' && control.kind === 'checkbox');
            if (required) {
                required.checked = false;
                form.rows.filter(row => row.parts.some(part => 'control' in part && part.control === required)).forEach(row => row.hidden = true);
            }
        }
        this.form = form;
    }

    /**
     * Posts the form to a legacy action: Save and Delete then close the editor, Clone shows the
     * editor of the clone it answers.
     */
    protected async post(action: string, close: boolean): Promise<void> {
        const values = serializeStudioFieldForm(this.form).filter(([name]) => name !== 'action');
        const body = new URLSearchParams();
        values.forEach(([name, value]) => body.append(name, value));
        body.set('action', action);
        this.loading = true;
        this.cdr.detectChanges();
        try {
            const output = await this.postForm(body);
            if (this.destroyed) {
                return;
            }
            if (close) {
                this.message.addSuccessMessageByKey('LBL_REQUEST_PROCESSED');
                this.closed.emit(true);
                return;
            }
            const form = parseStudioFieldForm(output);
            if (!form) {
                throw new Error('No field editor');
            }
            this.setForm(form);
        } catch (e) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
        } finally {
            this.loading = false;
            this.confirmDelete = false;
            this.cdr.detectChanges();
        }
    }

    /**
     * Posts the values as they are (several values of a name), as the legacy form.
     */
    protected async postForm(body: URLSearchParams): Promise<string> {
        if (!body.has('module')) {
            body.set('module', 'ModuleBuilder');
        }
        const response = await fetch('legacy/index.php', {method: 'POST', body, credentials: 'same-origin'});
        if (!response.ok) {
            throw new Error(`${response.status}`);
        }

        return response.text();
    }

    protected async legacy(url: string): Promise<string> {
        const response = await fetch(url, {credentials: 'same-origin'});
        if (!response.ok) {
            throw new Error(`${response.status}`);
        }

        return response.text();
    }
}
