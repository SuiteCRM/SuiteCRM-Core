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

import {ChangeDetectorRef, Component, OnDestroy, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {combineLatest, Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {ModuleNameMapper} from '../../../../services/navigation/module-name-mapper/module-name-mapper.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {
    getImport,
    IMPORT_PROCESS,
    ImportChoice,
    ImportDuplicatesPage,
    ImportError,
    ImportMappingPage,
    ImportMappingRow,
    ImportModulePage,
    ImportPage,
    ImportProperty,
    ImportPropertiesPage,
    ImportResultsPage,
    ImportResultsTab,
    ImportSavedMap,
    ImportUndoPage,
    ImportUploadPage,
    isImportError,
    parseDefaultControls,
    parseDuplicatesPage,
    parseImportDocument,
    parseImportSample,
    parseImportTable,
    parseMappingPage,
    parseModulePage,
    parsePropertiesPage,
    parseResultsPage,
    parseUndoPage,
    parseUploadPage,
    postImport
} from '../../import-pages';
import {htmlText, LegacyControl, legacyControl, serializeLegacyControls} from '../../legacy-form';

type ImportStep = 'loading' | 'module' | 'upload' | 'properties' | 'mapping' | 'duplicates' | 'importing' | 'results' | 'undo' | 'error';

interface ImportedGroup {
    module: string;
    moduleName: string;
    total: number;
    offset: number;
    pageSize: number;
    records: { id: string; name: string }[];
}

/**
 * The import wizard of the modules (legacy module Import), in the page instead of the
 * legacy wizard in an iframe: the module menus (Import) and the import wizard of the
 * administration. Each step is the legacy one: its form is posted to its legacy action as the
 * browser posts it (the file uploaded too) and the page it answers is shown here, with what its
 * scripts do (the first rows of the file refreshed, the third-party sources, the default value of
 * a field and its quick search, the checks of the mapping, the duplicate check lists, the import
 * of the file by parts with its progress, the tables of the results). The records imported, the
 * target lists and the vCards come from the import-wizard process.
 */
@Component({
    selector: 'scrm-import-view',
    templateUrl: './import.component.html',
    styleUrls: []
})
export class ImportViewComponent implements OnInit, OnDestroy {

    step: ImportStep = 'loading';
    /** The legacy module imported into (Administration: the wizard of the administration). */
    module = '';
    moduleName = '';
    labels: { [key: string]: string } = {};
    loading = false;
    errors: string[] = [];
    error: ImportError | null = null;
    /** A question before going on (the confirm() of the legacy scripts). */
    question: { text: string; proceed: () => void; cancel: () => void } | null = null;

    modulePage: ImportModulePage | null = null;
    uploadPage: ImportUploadPage | null = null;
    file: File | null = null;
    propertiesPage: ImportPropertiesPage | null = null;
    showProperties = false;
    mappingPage: ImportMappingPage | null = null;
    showDefaults = false;
    showNotes = false;
    duplicatesPage: ImportDuplicatesPage | null = null;
    progress = {percent: 0, text: '', started: ''};
    resultsPage: ImportResultsPage | null = null;
    activeTab = 0;
    imported: ImportedGroup[] = [];
    listTerm = '';
    lists: { id: string; name: string }[] | null = null;
    undoPage: ImportUndoPage | null = null;
    /** The results of the quick search of a default value. */
    search: { control: LegacyControl; row: ImportMappingRow; results: { [key: string]: string }[] } | null = null;

    protected subs = new Subscription();
    protected destroyed = false;
    protected request = 0;
    /** The wizard of the administration (its first step: the module to import into). */
    fromAdmin = '';
    protected returnModule = '';
    protected hasHeader = '';
    protected searchTimer: any = null;

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected moduleNameMapper: ModuleNameMapper,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.subs.add(combineLatest([this.route.paramMap, this.route.queryParamMap]).subscribe(([params, query]) => {
            this.module = query.get('import_module') ?? '';
            this.returnModule = query.get('return_module') ?? '';
            this.fromAdmin = query.get('from_admin_wizard') ?? '';
            this.hasHeader = query.get('has_header') ?? '';
            const action = (params.get('action') ?? 'step1').toLowerCase();
            this.load({action: 'labels', module: this.module}, data => {
                this.labels = data.labels ?? {};
                this.moduleName = data.moduleName ?? '';
                if (action === 'last') {
                    this.openResults('');
                } else {
                    this.start();
                }
            });
        }));
    }

    ngOnDestroy(): void {
        this.destroyed = true;
        this.subs.unsubscribe();
        clearTimeout(this.searchTimer);
    }

    label(key: string): string {
        return this.labels[key] || this.language.getAppString(key) || key;
    }

    get page(): ImportPage | null {
        switch (this.step) {
            case 'module':
                return this.modulePage;
            case 'upload':
                return this.uploadPage;
            case 'properties':
                return this.propertiesPage;
            case 'mapping':
                return this.mappingPage;
            case 'duplicates':
            case 'importing':
                return this.duplicatesPage;
            case 'results':
                return this.resultsPage;
            case 'undo':
                return this.undoPage;
        }

        return null;
    }

    answer(proceed: boolean): void {
        const question = this.question;
        this.question = null;
        if (question) {
            proceed ? question.proceed() : question.cancel();
        }
    }

    /** A radio checked: the others of its name unchecked. */
    check(controls: LegacyControl[], control: LegacyControl): void {
        controls.filter(item => item.kind === 'radio' && item.name === control.name).forEach(item => item.checked = item === control);
    }

    exit(): void {
        const module = this.returnModule || (this.module !== 'Administration' ? this.module : '');
        this.router.navigate(module ? ['/', this.moduleNameMapper.toFrontend(module)] : ['/', 'administration']).then();
    }

    /**
     * The wizard from its start: the module to import into (administration), else the file to
     * upload (legacy Step1).
     */
    start(): void {
        this.file = null;
        this.error = null;
        if (this.module === 'Administration') {
            this.run(
                () => getImport({module: 'Import', action: 'Step1', import_module: 'Administration', to_pdf: '1'}),
                parseModulePage,
                page => this.show('module', () => this.modulePage = page)
            );
            return;
        }
        this.openUpload();
    }

    // ----- Module (import wizard of the administration) -----

    get moduleSelect(): LegacyControl | undefined {
        return legacyControl(this.modulePage?.controls ?? [], 'admin_import_module');
    }

    moduleNext(): void {
        const page = this.modulePage;
        const select = this.moduleSelect;
        if (!page || !select) {
            return;
        }
        this.module = select.value;
        this.moduleName = select.options.find(option => option.value === select.value)?.label ?? select.value;
        this.fromAdmin = '1';
        this.setValue(page.controls, 'import_module', select.value);
        this.run(
            () => postImport(this.values(page.controls, 'Step2', page)),
            parseUploadPage,
            page => this.show('upload', () => this.uploadPage = page)
        );
    }

    // ----- Upload (legacy step2) -----

    fileChosen(event: Event): void {
        const input = event.target as HTMLInputElement;
        this.file = input.files?.[0] ?? null;
        this.errors = [];
    }

    publish(map: ImportSavedMap): void {
        this.mapping({publish: map.publish, import_map_id: map.control.value.slice(7)});
    }

    deleteMap(map: ImportSavedMap): void {
        this.ask(this.label('LBL_DELETE_MAP_CONFIRMATION'), () => this.mapping({delete_map_id: map.control.value.slice(7)}));
    }

    uploadNext(): void {
        const page = this.uploadPage;
        if (!page || this.loading) {
            return;
        }
        if (!this.file) {
            this.errors = [this.label('ERR_MISSING_REQUIRED_FIELDS') + ' ' + this.label('ERR_SELECT_FILE')];
            return;
        }
        const next = (): void => this.run(
            () => postImport(this.values(page.controls, 'Confirm', page), this.file),
            parsePropertiesPage,
            properties => this.showPropertiesPage(properties)
        );
        // The updates cannot be undone (legacy step2 script).
        const update = page.types.find(choice => choice.control.value === 'update');
        update?.control.checked ? this.ask(this.label('LBL_CONFIRM_IMPORT'), next) : next();
    }

    /**
     * Back to the module to import into (the wizard of the administration), else to the upload
     * (legacy Step1).
     */
    uploadBack(): void {
        const page = this.uploadPage;
        if (!page) {
            return;
        }
        if (this.fromAdmin) {
            this.module = 'Administration';
            this.start();
            return;
        }
        this.run(() => postImport(this.values(page.controls, 'Step1', page, true)), parseUploadPage,
            upload => this.show('upload', () => this.uploadPage = upload));
    }

    // ----- File properties (legacy confirm) -----

    propertyControl(id: string): LegacyControl | undefined {
        return this.propertiesPage?.properties.find(property => property.control.id === id)?.control;
    }

    /**
     * The example of an amount with the currency properties (legacy setSigDigits()).
     */
    get currencyExample(): string {
        const currency = this.propertyControl('currency_select');
        const label = currency?.options.find(option => option.value === currency.value)?.label ?? '';
        const symbol = label.includes(' : ') ? label.slice(label.lastIndexOf(' : ') + 3) : '';
        const precision = Number(this.propertyControl('sigDigits')?.value ?? 2);
        const thousands = this.propertyControl('default_number_grouping_seperator')?.value ?? '';
        const decimals = this.propertyControl('default_decimal_seperator')?.value ?? '';
        const major = '123456789'.replace(/\B(?=(\d{3})+(?!\d))/g, thousands);

        return symbol + major + (precision > 0 ? decimals + '0'.repeat(precision) : '');
    }

    /**
     * The example of a name with the name format (legacy setPreview()).
     */
    get nameExample(): string {
        const parts: { [key: string]: string } = {s: 'Dr.', f: 'David', l: 'Livingstone', t: 'Code Monkey Extraordinaire'};

        return (this.propertyControl('default_locale_name_format')?.value ?? '').split('').map(char => parts[char] ?? char).join('');
    }

    propertyChanged(property: ImportProperty, other = false): void {
        const id = property.control.id;
        if (!['custom_delimiter', 'custom_enclosure', 'has_header', 'importlocale_charset'].includes(id)) {
            return;
        }
        // An enclosure "Other": the table is refreshed once it is typed (legacy refreshDataTable()).
        if (id === 'custom_enclosure' && !other && property.control.value === 'other') {
            return;
        }
        this.refreshSample();
    }

    /**
     * A third-party source: the properties of its files (legacy setMappingProperties()).
     */
    chooseSource(choice: ImportChoice): void {
        const page = this.propertiesPage;
        if (!page) {
            return;
        }
        const source = legacyControl(page.controls, 'source');
        const apply = (): void => {
            this.check(page.controls, choice.control);
            const properties = page.sourceProperties[choice.control.value];
            if (!properties || !source) {
                return;
            }
            source.value = choice.control.value;
            this.setValue(page.controls, 'custom_delimiter', properties.delim);
            this.setValue(page.controls, 'custom_enclosure', properties.enclos);
            const header = legacyControl(page.controls, 'has_header');
            if (header) {
                header.checked = !!properties.has_header;
            }
            this.refreshSample();
        };
        if (choice.control.value && source && !['', 'csv', 'salesforce', 'outlook'].includes(source.value)) {
            this.ask(this.label('LBL_CONFIRM_MAP_OVERRIDE'), apply, () => {
                page.sources.forEach(item => item.control.checked = false);
                this.refresh();
            });
            return;
        }
        apply();
    }

    propertiesBack(): void {
        const page = this.propertiesPage;
        if (page) {
            this.run(() => postImport(this.values(page.controls, 'Step2', page, true)), parseUploadPage,
                upload => this.show('upload', () => this.uploadPage = upload));
        }
    }

    propertiesNext(): void {
        const page = this.propertiesPage;
        if (page) {
            this.run(() => postImport(this.values(page.controls, 'Step3', page)), parseMappingPage,
                mapping => this.showMappingPage(mapping));
        }
    }

    // ----- Field mapping (legacy step3) -----

    defaultsShown(row: ImportMappingRow): LegacyControl[] {
        return row.defaults.filter(control => control.shown && control.kind !== 'hidden' && control.kind !== 'button');
    }

    fieldChanged(row: ImportMappingRow): void {
        this.errors = [];
        if (row.field.value === '-1') {
            row.defaults = [];
            return;
        }
        // The default value of the field (legacy GetControl).
        row.loading = true;
        const field = row.field.value;
        getImport({module: 'Import', action: 'GetControl', import_module: this.module, field_name: field}).then(output => {
            if (!this.destroyed && row.field.value === field) {
                row.defaults = parseDefaultControls(output);
            }
        }).catch(() => this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'))).finally(() => {
            row.loading = false;
            this.refresh();
        });
    }

    /**
     * What the scripts of a default value do when it changes: the date and time of a date and time
     * field, the type of a flex relate (legacy Datetimecombo, parent type).
     */
    defaultChanged(row: ImportMappingRow, control: LegacyControl): void {
        const match = control.id.match(/^(.+)_(date|import_time)$/);
        const combo = match ? row.defaults.find(item => item.kind === 'hidden' && item.id === match[1]) : undefined;
        if (match && combo) {
            const date = row.defaults.find(item => item.id === match[1] + '_date')?.value.trim() ?? '';
            const time = row.defaults.find(item => item.id === match[1] + '_import_time')?.value.trim() ?? '';
            combo.value = date ? (date + ' ' + time).trim() : '';
        }
        if (control.name === 'parent_type') {
            row.defaults.filter(item => ['parent_name', 'parent_id'].includes(item.name)).forEach(item => item.value = '');
        }
    }

    hasQuickSearch(control: LegacyControl): boolean {
        return /\bsqsEnabled\b/.test(control.className) && !!this.quickSearchOf(control);
    }

    /**
     * The quick search of a relate default value, as the legacy one (Home quicksearchQuery).
     */
    quickSearch(row: ImportMappingRow, control: LegacyControl): void {
        clearTimeout(this.searchTimer);
        const definition = this.quickSearchOf(control);
        if (!definition) {
            return;
        }
        // The legacy fields filled by the search are emptied with the name typed.
        this.populate(row, definition, null, control);
        const term = control.value;
        if (term.trim() === '') {
            this.search = null;
            return;
        }
        this.searchTimer = setTimeout(() => {
            const data = JSON.parse(JSON.stringify(definition));
            (data.conditions ?? []).forEach((condition: { value: string }) => condition.value = term);
            const parentType = control.name === 'parent_name' ? row.defaults.find(item => item.name === 'parent_type')?.value : '';
            if (parentType) {
                data.modules = [parentType];
            }
            getImport({to_pdf: 'true', module: 'Home', action: 'quicksearchQuery', data: JSON.stringify(data)}).then(output => {
                if (this.destroyed || control.value !== term) {
                    return;
                }
                const results = JSON.parse(output.slice(Math.max(0, output.indexOf('{'))) || '{}');
                this.search = {control, row, results: Array.isArray(results?.fields) ? results.fields : []};
                this.refresh();
            }).catch(() => {
                this.search = null;
                this.refresh();
            });
        }, 300);
    }

    chooseResult(result: { [key: string]: string }): void {
        const search = this.search;
        const definition = search ? this.quickSearchOf(search.control) : undefined;
        if (search && definition) {
            this.populate(search.row, definition, result);
        }
        this.search = null;
    }

    clearRelate(row: ImportMappingRow, control: LegacyControl): void {
        const definition = this.quickSearchOf(control);
        if (definition) {
            this.populate(row, definition, null);
        }
        control.value = '';
        this.search = null;
    }

    resultLabel(result: { [key: string]: string }, control: LegacyControl): string {
        const field = this.quickSearchOf(control)?.field_list?.[0] ?? 'name';

        return htmlText(result[field] ?? '');
    }

    addRow(): void {
        const page = this.mappingPage;
        const first = page?.rows[0];
        const count = page ? legacyControl(page.controls, 'columncount') : undefined;
        if (!page || !first || !count) {
            return;
        }
        const index = Number(count.value) || page.rows.length;
        count.value = String(index + 1);
        page.rows.push({
            index,
            header: '',
            field: {...first.field, uid: first.field.uid + '-' + index, name: 'colnum_' + index, value: '-1'},
            samples: [],
            removable: true,
            defaults: []
        });
        // A field added is given its default value (legacy addrow: the column shown).
        this.showDefaults = true;
    }

    removeRow(row: ImportMappingRow): void {
        if (this.mappingPage) {
            this.mappingPage.rows = this.mappingPage.rows.filter(item => item !== row);
        }
    }

    mappingBack(): void {
        const page = this.mappingPage;
        if (!page) {
            return;
        }
        // The legacy Confirm refuses the file of the mapping step (upload://… is not a valid file
        // name): the Back button of the legacy wizard fails there. Its name is posted, Confirm
        // reads the file from it (upload:// and its base name).
        const values = this.values(this.mappingControls(page), 'Confirm', page, true)
            .map(([name, value]): [string, string] => [name, name === 'tmp_file' ? value.replace(/^upload:\/\//, '') : value]);
        this.run(() => postImport(values), parsePropertiesPage, properties => this.showPropertiesPage(properties));
    }

    mappingNext(): void {
        const page = this.mappingPage;
        if (!page) {
            return;
        }
        this.errors = this.mappingErrors(page);
        if (this.errors.length) {
            return;
        }
        this.run(() => postImport(this.values(this.mappingControls(page), 'dupcheck', page)), parseDuplicatesPage,
            duplicates => this.show('duplicates', () => this.duplicatesPage = duplicates));
    }

    trackRow(index: number, row: ImportMappingRow): number {
        return row.index;
    }

    // ----- Duplicate check (legacy dupcheck) -----

    moveDuplicate(item: { dupeVal: string; label: string }, check: boolean): void {
        const page = this.duplicatesPage;
        if (!page) {
            return;
        }
        page.enabled = page.enabled.filter(entry => entry !== item);
        page.disabled = page.disabled.filter(entry => entry !== item);
        (check ? page.enabled : page.disabled).push(item);
    }

    get saveMapControl(): LegacyControl | undefined {
        return legacyControl(this.duplicatesPage?.controls ?? [], 'save_map_as');
    }

    duplicatesBack(): void {
        const page = this.duplicatesPage;
        if (page) {
            this.run(() => postImport(this.values(page.controls, 'Step3', page, true)), parseMappingPage,
                mapping => this.showMappingPage(mapping));
        }
    }

    /**
     * Imports the file by parts of the legacy maximum of records, as the legacy wizard
     * (ProcessImport: Step4 for each part), then shows the results.
     */
    async importNow(): Promise<void> {
        const page = this.duplicatesPage;
        if (!page || this.loading) {
            return;
        }
        this.setValue(page.controls, 'enabled_dupes', JSON.stringify(page.enabled.map(item => item.dupeVal)));
        this.setValue(page.controls, 'disabled_dupes', JSON.stringify(page.disabled.map(item => item.dupeVal)));
        this.setValue(page.controls, 'action', 'Step4');
        const base = legacyControl(page.controls, 'tmp_file_base')?.value ?? '';
        const request = ++this.request;
        this.loading = true;
        this.errors = [];
        this.step = 'importing';
        this.progress = {percent: 0, text: '', started: this.label('LBL_IMPORT_STARTED') + ' ' + new Date().toLocaleString()};
        this.refresh();

        try {
            for (let part = 0; part <= page.fileTotal; part++) {
                this.setValue(page.controls, 'tmp_file', base + '-' + part);
                this.progress.percent = page.fileTotal > 0 ? Math.round(part / page.fileTotal * 100) : 0;
                this.progress.text = [
                    this.label('LBL_IMPORT_RECORDS'), part * page.recordThreshold + 1, this.label('LBL_IMPORT_RECORDS_TO'),
                    Math.min((part + 1) * page.recordThreshold, page.recordCount), this.label('LBL_IMPORT_RECORDS_OF'), page.recordCount
                ].join(' ');
                this.refresh();
                const output = await postImport(serializeLegacyControls(page.controls));
                if (request !== this.request || this.destroyed) {
                    return;
                }
                if (output.trim() !== '') {
                    this.step = 'duplicates';
                    this.errors = [this.label('LBL_IMPORT_ERROR') + ' ' + htmlText(output)];
                    return;
                }
                // The settings are saved once (legacy ProcessImport).
                this.setValue(page.controls, 'save_map_as', '');
            }
            this.progress = {...this.progress, percent: 100, text: this.label('LBL_IMPORT_COMPLETED')};
        } catch (e) {
            this.step = 'duplicates';
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
            return;
        } finally {
            if (request === this.request) {
                this.loading = false;
                this.refresh();
            }
        }
        this.openResults(legacyControl(page.controls, 'current_step')?.value ?? '');
    }

    // ----- Results (legacy last) -----

    tableOffset(tab: ImportResultsTab, offset: number | null): void {
        const table = tab.table;
        if (!table || offset === null || this.loading) {
            return;
        }
        getImport({module: 'Import', action: 'RefreshTable', offset: String(offset), tableID: table.tableID, has_header: this.hasHeader})
            .then(output => {
                tab.table = parseImportTable(parseImportDocument(output), table.tableID) ?? tab.table;
                this.refresh();
            })
            .catch(() => this.message.addDangerMessage(this.label('LBL_ACTION_ERROR')));
    }

    importedPage(group: ImportedGroup, offset: number): void {
        this.load({action: 'imported', module: this.module, offset}, data => {
            const updated = (data.groups ?? []).find((item: ImportedGroup) => item.module === group.module);
            if (updated) {
                Object.assign(group, updated);
            }
        });
    }

    recordRoute(group: ImportedGroup, id: string): string[] {
        return ['/', this.moduleNameMapper.toFrontend(group.module), 'record', id];
    }

    searchLists(): void {
        this.load({action: 'prospectLists', term: this.listTerm}, data => this.lists = data.lists ?? []);
    }

    addToList(list: { id: string; name: string }): void {
        this.load({action: 'addToProspectList', listId: list.id}, data => {
            this.message.addSuccessMessage(this.label('LBL_IMPORT_VIEW_ADDED_TO_LIST')
                .replace('{0}', String(data.count ?? 0)).replace('{1}', data.name ?? list.name));
            this.lists = null;
            this.listTerm = '';
        });
    }

    undo(): void {
        const page = this.resultsPage;
        if (page) {
            const values = this.values(page.controls, 'Undo', null);
            values.push(['undo', this.label('LBL_UNDO_LAST_IMPORT')]);
            this.run(() => postImport(values), parseUndoPage, undo => this.show('undo', () => this.undoPage = undo));
        }
    }

    // ----- Internals -----

    protected openUpload(): void {
        // Step1 of the wizard of the administration is the module to import into: its upload is Step2.
        const params: { [key: string]: string } = {module: 'Import', action: this.fromAdmin ? 'Step2' : 'Step1', import_module: this.module, to_pdf: '1'};
        if (this.fromAdmin) {
            params.from_admin_wizard = this.fromAdmin;
        }
        if (this.returnModule) {
            params.return_module = this.returnModule;
            params.return_action = 'index';
        }
        this.run(() => getImport(params), parseUploadPage, page => this.show('upload', () => this.uploadPage = page));
    }

    protected openResults(currentStep: string): void {
        this.hasHeader = (this.duplicatesPage ? legacyControl(this.duplicatesPage.controls, 'has_header')?.value : '') || this.hasHeader;
        this.run(
            () => getImport({
                module: 'Import', action: 'Last', current_step: currentStep, type: '', import_module: this.module,
                has_header: this.hasHeader, to_pdf: '1'
            }),
            parseResultsPage,
            page => this.show('results', () => {
                this.resultsPage = page;
                this.activeTab = page.activeTab;
                this.lists = null;
                this.imported = [];
                this.load({action: 'imported', module: this.module, offset: 0}, data => this.imported = data.groups ?? []);
            })
        );
    }

    protected showPropertiesPage(page: ImportPropertiesPage): void {
        this.show('properties', () => {
            this.propertiesPage = page;
            this.showProperties = false;
        });
        // A file of too many records (legacy confirm page): going on is confirmed.
        if (page.maxRecordsWarning) {
            this.ask(page.maxRecordsWarning, () => {
            }, () => this.openUpload());
        }
    }

    protected showMappingPage(page: ImportMappingPage): void {
        this.show('mapping', () => {
            this.mappingPage = page;
            this.showDefaults = false;
            this.showNotes = false;
            this.search = null;
        });
    }

    protected show(step: ImportStep, set: () => void): void {
        set();
        this.step = step;
        this.error = null;
        this.errors = [];
        window.scrollTo({top: 0});
    }

    /**
     * The checks of the mapping (legacy ImportView.validateMappings()): a field mapped once, the
     * required fields mapped (the full name for the last name of a person).
     */
    protected mappingErrors(page: ImportMappingPage): string[] {
        const errors: string[] = [];
        const mapped = new Set<string>();
        page.rows.forEach(row => {
            if (row.field.value === '-1') {
                return;
            }
            if (mapped.has(row.field.value)) {
                errors.push(this.label('ERR_MULTIPLE') + ' ' + (row.field.options.find(option => option.value === row.field.value)?.label ?? ''));
            }
            mapped.add(row.field.value);
        });
        Object.keys(page.required).forEach(field => {
            if (!mapped.has(field) && !(field === 'last_name' && mapped.has('full_name'))) {
                errors.push(this.label('ERR_MISSING_REQUIRED_FIELDS') + ' ' + page.required[field]);
            }
        });

        return errors;
    }

    protected mappingControls(page: ImportMappingPage): LegacyControl[] {
        return [...page.controls, ...page.rows.reduce((controls, row) => [...controls, row.field, ...row.defaults], [] as LegacyControl[])];
    }

    /**
     * The values of a form posted to an action, with its button as the browser posts it.
     */
    protected values(controls: LegacyControl[], action: string, page: { controls: LegacyControl[] } | null, back = false): [string, string][] {
        this.setValue(controls, 'action', action);
        const values = serializeLegacyControls(controls);
        if (page) {
            values.push(['button', this.label(back ? 'LBL_BACK' : 'LBL_NEXT')]);
        }

        return values;
    }

    protected setValue(controls: LegacyControl[], name: string, value: string): void {
        controls.filter(control => control.name === name).forEach(control => control.value = value);
    }

    protected quickSearchOf(control: LegacyControl): any {
        return this.mappingPage?.quickSearches['importstep3_' + control.id];
    }

    /**
     * The fields a quick search fills (its populate_list) set from a result, emptied without one
     * (the control typed in kept).
     */
    protected populate(row: ImportMappingRow, definition: any, result: { [key: string]: string } | null, keep: LegacyControl | null = null): void {
        (definition.populate_list ?? []).forEach((target: string, index: number) => {
            const id = target.replace(/^importstep3_/, '');
            const control = row.defaults.find(item => item.id === id || item.name === id);
            if (control && control !== keep) {
                control.value = result ? htmlText(result[definition.field_list[index]] ?? '') : '';
            }
        });
    }

    protected mapping(params: { [key: string]: string }): void {
        getImport({module: 'Import', action: 'mapping', import_module: this.module, ...params}).then(output => {
            let message = '';
            try {
                message = JSON.parse(output.slice(Math.max(0, output.indexOf('{'))))?.message ?? '';
            } catch (e) {
                message = '';
            }
            if (message) {
                this.message.addDangerMessage(message);
            }
            this.openUpload();
        }).catch(() => this.message.addDangerMessage(this.label('LBL_ACTION_ERROR')));
    }

    protected ask(text: string, proceed: () => void, cancel: () => void = () => {
    }): void {
        this.question = {text, proceed, cancel};
        this.refresh();
        document.getElementById('import-wizard-question')?.scrollIntoView({block: 'center', behavior: 'smooth'});
    }

    protected run<T>(load: () => Promise<string>, parse: (output: string) => T | ImportError, show: (page: T) => void): void {
        const request = ++this.request;
        this.loading = true;
        this.errors = [];
        this.question = null;
        this.search = null;
        this.refresh();
        load().then(output => {
            if (request !== this.request || this.destroyed) {
                return;
            }
            const page = parse(output);
            if (isImportError(page)) {
                this.error = page;
                this.step = 'error';
            } else {
                show(page);
            }
        }).catch(() => {
            if (request === this.request) {
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
            }
        }).finally(() => {
            if (request === this.request) {
                this.loading = false;
                this.refresh();
            }
        });
    }

    protected load(options: { [key: string]: any }, next: (data: any) => void): void {
        this.subs.add(this.processService.submit(IMPORT_PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                if (process?.status === 'success') {
                    next(process.data ?? {});
                } else {
                    this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                }
                this.refresh();
            },
            error: () => {
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.refresh();
            }
        }));
    }

    protected refresh(): void {
        if (!this.destroyed) {
            this.cdr.detectChanges();
        }
    }

    protected refreshSample(): void {
        const page = this.propertiesPage;
        if (!page) {
            return;
        }
        const value = (id: string): string => legacyControl(page.controls, id)?.value ?? '';
        const delimiter = value('custom_delimiter') === 'other' ? value('custom_delimiter_other') : value('custom_delimiter');
        const enclosure = value('custom_enclosure') === 'other' ? value('custom_enclosure_other') : value('custom_enclosure');
        getImport({
            import_module: this.module, action: 'RefreshMapping', module: 'Import', importFile: value('file_name'),
            delim: delimiter, qualif: enclosure, header: legacyControl(page.controls, 'has_header')?.checked ? 'true' : ''
        }).then(output => {
            page.sample = parseImportSample(parseImportDocument(output));
            this.refresh();
        }).catch(() => this.message.addDangerMessage(this.label('LBL_ACTION_ERROR')));
    }
}
