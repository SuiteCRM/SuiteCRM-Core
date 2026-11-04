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
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface SurveyOption {
    id: string;
    name: string;
}

interface SurveyQuestion {
    id: string;
    name: string;
    type: string;
    typeLabel?: string;
    options: SurveyOption[];
}

interface SurveyQuestionsData {
    id: string;
    url: string;
    hasResponses: boolean;
    canEdit: boolean;
    questions: SurveyQuestion[];
    types: { value: string; label: string; options: boolean }[];
    labels: { [key: string]: string };
}

/**
 * The URL and the questions of a survey under its details (record view, bottom widget
 * `survey-questions`), as the legacy detail view; Edit Questions changes the questions (text,
 * type, order) and the options of the choice and matrix questions, as the legacy edit view, while
 * the survey has no response. Process `survey-questions` (SurveyQuestions).
 */
@Component({
    selector: 'scrm-survey-questions-widget',
    standalone: true,
    imports: [CommonModule, FormsModule],
    template: `
        <div class="survey-questions" *ngIf="data">
            <p class="survey-url">
                <strong>{{ label('LBL_SURVEY_URL_DISPLAY') }}:</strong>
                <a *ngIf="data.url" [href]="data.url" target="_blank" rel="noopener">{{ data.url }}</a>
                <span *ngIf="!data.url" class="survey-muted">{{ label('LBL_SURVEY_VIEW_NOT_PUBLIC') }}</span>
            </p>

            <h3>{{ label('LBL_SURVEY_QUESTIONS_DISPLAY') }}</h3>

            <ng-container *ngIf="!editing">
                <table *ngIf="data.questions.length; else none" class="survey-table">
                    <thead>
                    <tr>
                        <th></th>
                        <th>{{ label('LBL_SURVEY_TEXT') }}</th>
                        <th>{{ label('LBL_SURVEY_TYPE') }}</th>
                        <th>{{ label('LBL_OPTIONS') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr *ngFor="let question of data.questions; let index = index">
                        <td class="survey-number">{{ index + 1 }}</td>
                        <td>{{ question.name }}</td>
                        <td>{{ question.typeLabel }}</td>
                        <td>{{ optionNames(question) }}</td>
                    </tr>
                    </tbody>
                </table>
                <ng-template #none><p class="survey-muted">{{ label('LBL_SURVEY_VIEW_NO_QUESTION') }}</p></ng-template>
                <p *ngIf="data.hasResponses && data.canEdit" class="survey-muted">{{ label('LBL_CANT_EDIT_RESPONDED') }}</p>
                <button *ngIf="data.canEdit && !data.hasResponses" type="button" class="btn btn-sm btn-main"
                        (click)="edit()">{{ label('LBL_SURVEY_VIEW_EDIT_QUESTIONS') }}</button>
            </ng-container>

            <ng-container *ngIf="editing">
                <div *ngFor="let question of editing; let index = index; let last = last" class="survey-edit">
                    <div class="survey-edit-row">
                        <span class="survey-number">{{ index + 1 }}</span>
                        <input type="text" class="form-control form-control-sm" [(ngModel)]="question.name"
                               [attr.aria-label]="label('LBL_SURVEY_TEXT')" [placeholder]="label('LBL_SURVEY_TEXT')">
                        <select class="form-control form-control-sm survey-type" [(ngModel)]="question.type"
                                [attr.aria-label]="label('LBL_SURVEY_TYPE')">
                            <option *ngFor="let type of data.types" [value]="type.value">{{ type.label }}</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" [disabled]="index === 0"
                                [title]="label('LBL_SURVEY_VIEW_MOVE_UP')" [attr.aria-label]="label('LBL_SURVEY_VIEW_MOVE_UP')"
                                (click)="move(editing, index, -1)">↑</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" [disabled]="last"
                                [title]="label('LBL_SURVEY_VIEW_MOVE_DOWN')" [attr.aria-label]="label('LBL_SURVEY_VIEW_MOVE_DOWN')"
                                (click)="move(editing, index, 1)">↓</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" [attr.aria-label]="app('LBL_DELETE')"
                                (click)="editing.splice(index, 1)">−</button>
                    </div>
                    <div *ngIf="hasOptions(question)" class="survey-options">
                        <span class="survey-options-label">{{ label('LBL_OPTIONS') }}</span>
                        <div *ngFor="let option of question.options; let position = index; let lastOption = last" class="survey-edit-row">
                            <input type="text" class="form-control form-control-sm" [(ngModel)]="option.name"
                                   [attr.aria-label]="label('LBL_OPTIONS')">
                            <button type="button" class="btn btn-sm btn-outline-secondary" [disabled]="position === 0"
                                    [attr.aria-label]="label('LBL_SURVEY_VIEW_MOVE_UP')" (click)="move(question.options, position, -1)">↑</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" [disabled]="lastOption"
                                    [attr.aria-label]="label('LBL_SURVEY_VIEW_MOVE_DOWN')" (click)="move(question.options, position, 1)">↓</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" [attr.aria-label]="app('LBL_DELETE')"
                                    (click)="question.options.splice(position, 1)">−</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-main"
                                (click)="question.options.push({id: '', name: ''})">{{ label('LBL_ADD_OPTION') }}</button>
                    </div>
                </div>
                <div class="survey-buttons">
                    <button type="button" class="btn btn-sm btn-outline-main" (click)="addQuestion()">{{ label('LBL_SURVEY_NEW_QUESTION') }}</button>
                    <button type="button" class="btn btn-sm btn-main" [disabled]="saving" (click)="save()">{{ app('LBL_SAVE_BUTTON_LABEL') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" (click)="editing = null">{{ app('LBL_CANCEL_BUTTON_LABEL') }}</button>
                </div>
            </ng-container>
        </div>
    `,
    styles: [`
        .survey-questions { padding: 8px 16px 16px; }
        .survey-questions h3 { margin: 12px 0 8px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .survey-url { margin: 0; }
        .survey-url a { margin-left: 6px; word-break: break-all; }
        .survey-muted { margin-left: 6px; color: #6a6676; }
        .survey-table { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .survey-table th { padding: 4px 8px; color: #6a6676; font-size: 12px; font-weight: 600; text-align: left; border-bottom: 1px solid #e3e0e8; }
        .survey-table td { padding: 6px 8px; vertical-align: top; border-bottom: 1px solid #f1eff4; }
        .survey-number { width: 1%; color: #6a6676; font-weight: 600; }
        .survey-edit { margin-bottom: 8px; padding: 8px; border: 1px solid #e3e0e8; border-radius: 4px; background: #faf9fb; }
        .survey-edit-row { display: flex; gap: 6px; align-items: center; margin-bottom: 4px; }
        .survey-type { max-width: 180px; }
        .survey-options { margin: 6px 0 0 24px; }
        .survey-options-label { display: block; margin-bottom: 4px; color: #6a6676; font-size: 12px; font-weight: 600; }
        .survey-buttons { display: flex; gap: 8px; margin-top: 8px; }
    `]
})
export class SurveyQuestionsWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'survey-questions';

    data: SurveyQuestionsData | null = null;
    editing: SurveyQuestion[] | null = null;
    saving = false;

    protected surveyId = '';
    protected subscriptions: Subscription[] = [];

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
        super();
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.open(this.context?.id);
        if (this.context$) {
            this.subscriptions.push(this.context$.subscribe(context => this.open(context?.id)));
        }
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
    }

    label(key: string): string {
        return this.data?.labels?.[key] || key;
    }

    app(key: string): string {
        return this.language.getAppString(key) || key;
    }

    optionNames(question: SurveyQuestion): string {
        return question.options.map(option => option.name).join(', ');
    }

    hasOptions(question: SurveyQuestion): boolean {
        return !!this.data?.types.find(type => type.value === question.type)?.options;
    }

    edit(): void {
        this.editing = this.data.questions.map(question => ({
            id: question.id, name: question.name, type: question.type, options: question.options.map(option => ({...option}))
        }));
    }

    addQuestion(): void {
        this.editing.push({id: '', name: '', type: this.data.types[0]?.value ?? 'Text', options: []});
    }

    move<T>(lines: T[], index: number, step: number): void {
        const [line] = lines.splice(index, 1);
        lines.splice(index + step, 0, line);
    }

    save(): void {
        const questions = this.editing.map(question => ({
            id: question.id,
            name: question.name,
            type: question.type,
            options: this.hasOptions(question) ? question.options : []
        }));
        if (questions.some(question => !question.name.trim() || question.options.some(option => !option.name.trim()))) {
            this.message.addWarningMessage(this.label('LBL_SURVEY_VIEW_QUESTION_REQUIRED'));
            return;
        }
        this.saving = true;
        this.run({action: 'saveQuestions', questions}, () => this.editing = null);
    }

    protected open(id: string): void {
        if (id && id !== this.surveyId) {
            this.surveyId = id;
            this.editing = null;
            this.run({action: 'questions'});
        }
    }

    protected run(options: { [key: string]: any }, done: () => void = null): void {
        this.subscriptions.push(this.processService.submit(SurveyQuestionsWidgetComponent.PROCESS, {...options, id: this.surveyId})
            .pipe(take(1)).subscribe({
                next: (process: Process) => {
                    this.saving = false;
                    if (process?.status !== 'success' || !process.data) {
                        const key = process?.messages?.[0] || 'LBL_ACTION_ERROR';
                        this.message.addDangerMessage(this.app(key));
                    } else {
                        this.data = process.data as SurveyQuestionsData;
                        done?.();
                    }
                    this.cdr.detectChanges();
                },
                error: () => {
                    this.saving = false;
                    this.message.addDangerMessage(this.app('LBL_ACTION_ERROR'));
                    this.cdr.detectChanges();
                }
            }));
    }
}
