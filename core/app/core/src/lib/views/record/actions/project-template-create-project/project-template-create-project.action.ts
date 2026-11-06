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

import {Component, Injectable, Input} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Router} from '@angular/router';
import {NgbActiveModal, NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {ViewMode} from '../../../../common/views/view.model';

interface CreateProjectForm {
    name: string;
    start: string;
    copyAll: boolean;
    tasks: string[];
}

/**
 * The form of Create Project of a project template, as the dialog of the legacy
 * template page: the project name, its start date, and the tasks whose user is copied (all, or
 * the chosen ones). Closes with the form.
 */
@Component({
    selector: 'scrm-project-template-create-project-modal',
    standalone: true,
    imports: [CommonModule, FormsModule],
    template: `
        <form (ngSubmit)="form.name.trim() && modal.close(form)">
            <div class="modal-header">
                <h4 class="modal-title">{{ label('LBL_CREATE_PROJECT_TITLE') }}</h4>
                <button type="button" class="btn-close close" [attr.aria-label]="label('LBL_CANCEL_BUTTON_LABEL')"
                        (click)="modal.dismiss()"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <label for="create-project-name">{{ label('LBL_PROJECT_NAME') }} *</label>
                <input id="create-project-name" name="name" class="form-control form-control-sm mb-3" required
                       [(ngModel)]="form.name">
                <label for="create-project-start">{{ label('LBL_START_DATE') }}</label>
                <input id="create-project-start" name="start" type="date" class="form-control form-control-sm mb-3"
                       [(ngModel)]="form.start">
                <div class="form-check mb-2">
                    <input id="create-project-all" name="copyAll" type="checkbox" class="form-check-input"
                           [(ngModel)]="form.copyAll">
                    <label for="create-project-all" class="form-check-label" [title]="label('LBL_TOOLTIP_TEXT')">
                        {{ label('LBL_COPY_ALL_TASKS') }}</label>
                </div>
                <ng-container *ngIf="!form.copyAll && tasks.length">
                    <label for="create-project-tasks">{{ label('LBL_COPY_SEL_TASKS') }}</label>
                    <select id="create-project-tasks" name="tasks" multiple class="form-control form-control-sm"
                            [size]="tasks.length < 8 ? tasks.length : 8" [(ngModel)]="form.tasks">
                        <option *ngFor="let task of tasks" [value]="task.id">{{ task.name }}</option>
                    </select>
                </ng-container>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" (click)="modal.dismiss()">{{ label('LBL_CANCEL_BUTTON_LABEL') }}</button>
                <button type="submit" class="btn btn-sm btn-main" [disabled]="!form.name.trim()">{{ label('LBL_CREATE_PROJECT') }}</button>
            </div>
        </form>
    `
})
export class ProjectTemplateCreateProjectModalComponent {

    @Input() tasks: { id: string; name: string }[] = [];
    @Input() labels: { [key: string]: string } = {};

    form: CreateProjectForm = {name: '', start: new Date().toISOString().slice(0, 10), copyAll: false, tasks: []};

    constructor(public modal: NgbActiveModal) {
    }

    label(key: string): string {
        return this.labels[key] || key;
    }
}

/**
 * "Create Project" of the project template record view (recordActions of
 * modules/AM_ProjectTemplates/metadata/detailviewdefs.php), as the button of the legacy
 * template page: the form, then the project made from the template (process
 * `project-template-gantt`, action createProject), which opens.
 */
@Injectable({providedIn: 'root'})
export class ProjectTemplateCreateProjectAction extends RecordActionHandler {

    static readonly PROCESS = 'project-template-gantt';

    key = 'project-template-create-project';
    modes = ['detail'] as ViewMode[];

    constructor(
        protected processService: ProcessService,
        protected modals: NgbModal,
        protected message: MessageService,
        protected language: LanguageStore,
        protected router: Router
    ) {
        super();
    }

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }

    run(data: RecordActionData): void {
        const templateId = data?.store?.recordStore?.getBaseRecord()?.id ?? '';
        if (!templateId) {
            return;
        }

        const process = ProjectTemplateCreateProjectAction.PROCESS;
        this.processService.submit(process, {action: 'load', project: templateId}).pipe(take(1)).subscribe({
            next: (loaded: Process) => {
                if (loaded?.status !== 'success' || !loaded.data) {
                    this.message.addDangerMessageByKey(loaded?.messages?.[0] || 'LBL_ACTION_ERROR');
                    return;
                }
                const labels = {...(loaded.data.labels ?? {})};
                for (const key of ['LBL_CREATE_PROJECT', 'LBL_CANCEL_BUTTON_LABEL']) {
                    labels[key] = this.language.getAppString(key) || labels[key] || key;
                }
                const modal = this.modals.open(ProjectTemplateCreateProjectModalComponent);
                modal.componentInstance.tasks = (loaded.data.tasks ?? []).map(task => ({id: task.id, name: task.name}));
                modal.componentInstance.labels = labels;
                modal.closed.pipe(take(1)).subscribe((form: CreateProjectForm) => this.create(templateId, form, labels));
            },
            error: () => this.message.addDangerMessageByKey('LBL_ACTION_ERROR')
        });
    }

    protected create(templateId: string, form: CreateProjectForm, labels: { [key: string]: string }): void {
        this.processService.submit(ProjectTemplateCreateProjectAction.PROCESS, {
            action: 'createProject',
            project: templateId,
            name: form.name.trim(),
            start: form.start || '',
            copyAll: form.copyAll,
            tasks: form.copyAll ? [] : form.tasks
        }).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const id = process?.data?.id;
                if (process?.status !== 'success' || !id) {
                    this.message.addDangerMessageByKey(process?.messages?.[0] || 'LBL_ACTION_ERROR');
                    return;
                }
                this.message.addSuccessMessage(labels.LBL_NEW_PROJECT_CREATED || 'New Project Created');
                this.router.navigate(['/project', 'record', id]).then();
            },
            error: () => this.message.addDangerMessageByKey('LBL_ACTION_ERROR')
        });
    }
}
