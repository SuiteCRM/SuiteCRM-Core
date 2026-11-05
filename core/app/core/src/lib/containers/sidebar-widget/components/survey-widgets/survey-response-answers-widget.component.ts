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
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {BaseWidgetComponent} from '../../../widgets/base-widget.model';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface SurveyAnswers {
    answers: { question: string; answers: string[] }[];
    labels: { [key: string]: string };
}

/**
 * The answers of a survey response by question under its details (record view, bottom
 * widget `survey-response-answers`), as the legacy detail view (question_responses_display).
 * Process `survey-questions`, action answers.
 */
@Component({
    selector: 'scrm-survey-response-answers-widget',
    standalone: true,
    imports: [CommonModule],
    template: `
        <div class="survey-answers" *ngIf="data">
            <table *ngIf="data.answers.length; else none" class="survey-answers-table">
                <thead>
                <tr>
                    <th>{{ label('LBL_SURVEY_QUESTION') }}</th>
                    <th>{{ label('LBL_RESPONSE_ANSWER') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr *ngFor="let line of data.answers">
                    <td>{{ line.question }}</td>
                    <td><div *ngFor="let answer of line.answers">{{ answer }}</div></td>
                </tr>
                </tbody>
            </table>
            <ng-template #none><p class="survey-answers-none">{{ label('LBL_SURVEY_VIEW_NO_ANSWER') }}</p></ng-template>
        </div>
    `,
    styles: [`
        .survey-answers { padding: 8px 16px 16px; }
        .survey-answers-table { width: 100%; border-collapse: collapse; }
        .survey-answers-table th { padding: 4px 8px; color: #6a6676; font-size: 12px; font-weight: 600; text-align: left; border-bottom: 1px solid #e3e0e8; }
        .survey-answers-table td { width: 50%; padding: 6px 8px; vertical-align: top; border-bottom: 1px solid #f1eff4; }
        .survey-answers-none { color: #6a6676; }
    `]
})
export class SurveyResponseAnswersWidgetComponent extends BaseWidgetComponent implements OnInit, OnDestroy {

    data: SurveyAnswers | null = null;

    protected responseId = '';
    protected subscriptions: Subscription[] = [];

    constructor(protected processService: ProcessService, protected cdr: ChangeDetectorRef) {
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

    protected open(id: string): void {
        if (!id || id === this.responseId) {
            return;
        }
        this.responseId = id;
        this.subscriptions.push(this.processService.submit('survey-questions', {action: 'answers', id}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.data = process?.status === 'success' ? process.data as SurveyAnswers : null;
                this.cdr.detectChanges();
            },
            error: () => {
                this.data = null;
                this.cdr.detectChanges();
            }
        }));
    }
}
