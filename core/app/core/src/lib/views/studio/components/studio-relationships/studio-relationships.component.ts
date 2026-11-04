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
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {STUDIO_PROCESS, StudioLabels, StudioOption, StudioRelationship, StudioRelationshipEditor, StudioRelationshipRow} from '../../studio.model';
import {postStudioAction} from '../../studio-legacy';

type RelationshipColumn = 'name' | 'lhsModule' | 'type' | 'rhsModule';

/**
 * The relationships of a module in Studio (legacy relationships list and editor): the
 * relationships, sorted by a column (those made in Studio marked *); a relationship, or a new
 * one, in the editor below the list. The editor is the legacy one: it is loaded again from the
 * backend when the type, the related module or the language changes, as the legacy editor
 * reloads itself. Save & Deploy saves and builds the relationship, Save its labels in the
 * language; Delete, once confirmed, deletes it with its tables unless they are kept. Save & Deploy
 * and Delete post the legacy form to its action: they repair the application.
 */
@Component({
    selector: 'scrm-studio-relationships',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './studio-relationships.component.html',
    styleUrls: []
})
export class StudioRelationshipsComponent implements OnChanges, OnDestroy {

    @Input() module = '';
    @Input() labels: StudioLabels = {};
    /** The help of the editor while it is open, the help of the list otherwise. */
    @Output() editing = new EventEmitter<boolean>();

    readonly columns: { key: RelationshipColumn; label: string }[] = [
        {key: 'name', label: 'LBL_REL_NAME'},
        {key: 'lhsModule', label: 'LBL_LHS_MODULE'},
        {key: 'type', label: 'LBL_REL_TYPE'},
        {key: 'rhsModule', label: 'LBL_RHS_MODULE'},
    ];

    relationships: StudioRelationshipRow[] = null;
    canAdd = false;
    sort: RelationshipColumn = 'name';
    descending = false;
    editor: StudioRelationshipEditor = null;
    /** The relationship of the editor, as changed in the page. */
    rel: StudioRelationship = null;
    language = '';
    confirmDelete = false;
    keepTables = false;
    loading = false;

    protected sub: Subscription;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes.module) {
            this.close();
            this.relationships = null;
            this.submit({action: 'relationships', module: this.module}, data => this.setList(data));
        }
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    get shown(): StudioRelationshipRow[] {
        const direction = this.descending ? -1 : 1;

        return [...(this.relationships ?? [])].sort((a, b) => direction * a[this.sort].localeCompare(b[this.sort], undefined, {sensitivity: 'base'}));
    }

    sortBy(column: RelationshipColumn): void {
        this.descending = this.sort === column ? !this.descending : false;
        this.sort = column;
    }

    sortState(column: RelationshipColumn): string {
        return this.sort !== column ? 'none' : this.descending ? 'descending' : 'ascending';
    }

    /**
     * Opens a relationship in the editor (its name without the Studio mark), a new one without name.
     */
    edit(name: string): void {
        this.confirmDelete = false;
        this.load({relationship_name: name.replace(/\*$/, '')});
    }

    close(): void {
        this.editor = null;
        this.rel = null;
        this.confirmDelete = false;
        this.editing.emit(false);
    }

    /** The type changed: the editor again, as the legacy one. */
    changeType(): void {
        this.load(this.editorValues());
    }

    /** The related module changed: the editor again, its label from the module. */
    changeModule(): void {
        this.load({...this.editorValues(), rhs_label: ''});
    }

    /** The language changed: the editor again, with the labels in that language. */
    changeLanguage(): void {
        this.load({...this.editorValues(), relationship_lang: this.language, ajaxLoad: '1'});
    }

    get isOneToOne(): boolean {
        return this.rel?.type === 'one-to-one';
    }

    /** The labels and subpanels of the relationship are shown (legacy template). */
    get hasPanels(): boolean {
        return !!this.rel?.rhsModule && !this.isOneToOne && !this.rel.relationshipOnly;
    }

    get hasLhsPanel(): boolean {
        return this.rel?.type === 'many-to-many' || this.rel?.type === 'many-to-one';
    }

    get hasRhsPanel(): boolean {
        return this.rel?.type !== 'many-to-one';
    }

    get canSaveLabels(): boolean {
        return !!this.rel && (this.rel.fromStudio || this.rel.readonly && !this.editor.isNew) && !this.isOneToOne;
    }

    get canDelete(): boolean {
        return !!this.rel && (this.rel.fromStudio || !this.rel.readonly && !this.editor.isNew);
    }

    saveAndDeploy(): void {
        if (!this.rel || this.rel.readonly || this.loading) {
            return;
        }
        // The fields of the legacy form, as it posts them.
        const values: { [key: string]: string } = {
            action: 'SaveRelationship',
            view_module: this.module,
            remove_tables: 'true',
            relationship_name: this.rel.name,
            lhs_module: this.rel.lhsModule,
            relationship_type: this.rel.type,
            rhs_module: this.rel.rhsModule
        };
        if (!this.isOneToOne) {
            values.relationship_lang = this.language;
        }
        if (this.rel.relationshipOnly) {
            values.relationship_only = '1';
        } else if (this.hasPanels) {
            values.lhs_label = this.rel.lhsLabel;
            values.rhs_label = this.rel.rhsLabel;
            if (this.hasLhsPanel) {
                values.lhs_subpanel = this.rel.lhsSubpanel;
            }
            if (this.hasRhsPanel) {
                values.rhs_subpanel = this.rel.rhsSubpanel;
            }
        }
        this.post(values);
    }

    saveLabels(): void {
        if (!this.canSaveLabels || this.loading) {
            return;
        }
        this.submit({
            action: 'saveRelationshipLabels',
            module: this.module,
            name: this.rel.name,
            language: this.language,
            lhsLabel: this.rel.lhsLabel,
            rhsLabel: this.rel.rhsLabel
        }, data => this.saved(data));
    }

    delete(): void {
        if (!this.canDelete || this.loading) {
            return;
        }
        this.post({action: 'DeleteRelationship', view_module: this.module, relationship_name: this.rel.name, remove_tables: this.keepTables ? '' : 'true'});
    }

    trackName(index: number, item: StudioRelationshipRow): string {
        return item.name;
    }

    /** The options kept from an editor to the next one: their select keeps its value. */
    trackOption(index: number, option: StudioOption): string {
        return option.value;
    }

    trackValue(index: number, value: string): string {
        return value;
    }

    /**
     * The values of the editor sent back to the backend when it is loaded again.
     */
    protected editorValues(): { [key: string]: string } {
        return {
            relationship_name: this.rel?.name ?? '',
            relationship_type: this.rel?.type ?? '',
            rhs_module: this.rel?.rhsModule ?? '',
            lhs_label: this.rel?.lhsLabel ?? '',
            rhs_label: this.rel?.rhsLabel ?? '',
            relationship_lang: this.language
        };
    }

    protected load(values: { [key: string]: string }): void {
        this.submit({action: 'relationship', module: this.module, values}, data => {
            this.editor = data as StudioRelationshipEditor;
            this.rel = {...this.editor.relationship};
            // The first subpanel when none is set, as the legacy select shows it.
            this.rel.lhsSubpanel = this.rel.lhsSubpanel || this.editor.lhsSubpanels[0] || '';
            this.rel.rhsSubpanel = this.rel.rhsSubpanel || this.editor.rhsSubpanels[0] || '';
            this.language = this.editor.language;
            this.editing.emit(true);
        });
    }

    /**
     * Posts the legacy form to its action, then the relationships again.
     */
    protected async post(params: { [key: string]: string }): Promise<void> {
        this.loading = true;
        this.cdr.detectChanges();
        try {
            await postStudioAction(params);
        } catch (e) {
            this.loading = false;
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
            this.cdr.detectChanges();
            return;
        }
        this.submit({action: 'relationships', module: this.module}, data => this.saved(data));
    }

    protected saved(data: any): void {
        this.setList(data);
        this.close();
        this.message.addSuccessMessageByKey('LBL_REQUEST_PROCESSED');
    }

    protected setList(data: any): void {
        this.relationships = data.relationships ?? [];
        this.canAdd = !!data.canAdd;
    }

    protected submit(options: { [key: string]: any }, next: (data: any) => void): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();
        this.sub = this.processService.submit(STUDIO_PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.status === 'success') {
                    next(process.data ?? {});
                } else {
                    this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }
}
