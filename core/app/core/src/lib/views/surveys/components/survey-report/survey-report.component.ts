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
import {ActivatedRoute, RouterModule} from '@angular/router';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface ReportCount {
    key: string;
    label: string;
    count: number;
}

interface ReportQuestion {
    id: string;
    name: string;
    type: string;
    typeLabel: string;
    counts: ReportCount[];
    matrix: { id: string; label: string; counts: ReportCount[] }[];
    answers: { answer: string; contactId: string; contactName: string; time: string }[];
}

interface SurveyReport {
    id: string;
    name: string;
    responses: number;
    sent: number;
    distinctSent: number;
    questions: ReportQuestion[];
    labels: { [key: string]: string };
}

/**
 * The report of a survey (View Survey Reports of the survey record view, legacy
 * view.reports.php): the responses and the surveys sent by the campaigns, then by question the
 * count of each answer with its share (choices, checkbox, rating, scale; by row for a matrix), or
 * the answers (text and dates) with their contact and date, shown or hidden. Route
 * surveys/reports/:record; process `survey-questions`, action report.
 */
@Component({
    selector: 'scrm-survey-report',
    standalone: true,
    imports: [CommonModule, RouterModule],
    template: `
        <div class="survey-report">
            <div class="survey-report-head">
                <h2>{{ report?.name }} <span class="survey-report-sub">{{ label('LBL_VIEW_SURVEY_REPORTS') }}</span></h2>
                <a *ngIf="id" class="btn btn-sm btn-outline-main" [routerLink]="['/surveys/record', id]">{{ app('LBL_CANCEL_BUTTON_LABEL') }}</a>
            </div>
            <p *ngIf="error" class="survey-report-error">{{ app('LBL_ACTION_ERROR') }}</p>

            <ng-container *ngIf="report">
                <section class="survey-report-panel survey-report-stats">
                    <span><strong>{{ label('LBL_SURVEY_VIEW_RESPONSES') }}:</strong> {{ report.responses }}</span>
                    <span><strong>{{ label('LBL_SURVEYS_SENT') }}:</strong> {{ report.sent }}</span>
                    <span><strong>{{ label('LBL_SURVEY_DISTINCT') }}:</strong> {{ report.distinctSent }}</span>
                </section>

                <section *ngFor="let question of report.questions; let index = index" class="survey-report-panel">
                    <h3>{{ index + 1 }}. {{ question.name }} <span class="survey-report-sub">{{ question.typeLabel }}</span></h3>

                    <table *ngIf="question.counts.length" class="survey-report-table">
                        <thead>
                        <tr>
                            <th>{{ label('LBL_RESPONSE_ANSWER') }}</th>
                            <th class="survey-report-count">{{ label('LBL_RESPONSE_COUNT') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr *ngFor="let count of question.counts">
                            <td>{{ count.label }}</td>
                            <td class="survey-report-count">{{ count.count }}</td>
                            <td class="survey-report-bar-cell">
                                <div class="survey-report-bar"><div [style.width.%]="share(count, question.counts)"></div></div>
                                <span>{{ share(count, question.counts) | number:'1.0-0' }}%</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <table *ngIf="question.matrix.length" class="survey-report-table">
                        <thead>
                        <tr>
                            <th></th>
                            <th *ngFor="let count of question.matrix[0].counts" class="survey-report-count">{{ count.label }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr *ngFor="let row of question.matrix">
                            <td>{{ row.label }}</td>
                            <td *ngFor="let count of row.counts" class="survey-report-count">{{ count.count }}</td>
                        </tr>
                        </tbody>
                    </table>

                    <ng-container *ngIf="!question.counts.length && !question.matrix.length">
                        <button type="button" class="btn btn-sm btn-outline-main" [disabled]="!question.answers.length"
                                (click)="toggle(question.id)">
                            {{ label(open.has(question.id) ? 'LBL_HIDE_RESPONSES' : 'LBL_SHOW_RESPONSES') }} ({{ question.answers.length }})
                        </button>
                        <table *ngIf="open.has(question.id)" class="survey-report-table">
                            <thead>
                            <tr>
                                <th>{{ label('LBL_RESPONSE_ANSWER') }}</th>
                                <th>{{ label('LBL_RESPONSE_CONTACT') }}</th>
                                <th>{{ label('LBL_RESPONSE_TIME') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr *ngFor="let answer of question.answers">
                                <td>{{ answer.answer }}</td>
                                <td>
                                    <a *ngIf="answer.contactId" [routerLink]="['/contacts/record', answer.contactId]">{{ answer.contactName }}</a>
                                </td>
                                <td>{{ date(answer.time) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </ng-container>
                </section>
            </ng-container>
        </div>
    `,
    styles: [`
        .survey-report { padding: calc(3rem + 16px) 24px 48px; }
        .survey-report-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 12px; }
        .survey-report-head h2 { margin: 0; font-size: 22px; font-weight: 400; }
        .survey-report-sub { margin-left: 8px; color: #6a6676; font-size: 14px; font-weight: 400; }
        .survey-report-error { color: #b42318; }
        .survey-report-panel { margin-bottom: 16px; padding: 12px 16px; background: #fff; border: 1px solid #e3e0e8; border-radius: 4px; }
        .survey-report-panel h3 { margin: 4px 0 12px; font-size: 15px; font-weight: 600; }
        .survey-report-stats { display: flex; flex-wrap: wrap; gap: 32px; }
        .survey-report-table { width: 100%; margin-top: 8px; border-collapse: collapse; }
        .survey-report-table th { padding: 4px 8px; color: #6a6676; font-size: 12px; font-weight: 600; text-align: left; border-bottom: 1px solid #e3e0e8; }
        .survey-report-table td { padding: 6px 8px; border-bottom: 1px solid #f1eff4; }
        .survey-report-count { width: 1%; text-align: center !important; white-space: nowrap; }
        .survey-report-bar-cell { display: flex; gap: 8px; align-items: center; }
        .survey-report-bar { flex: 1; height: 12px; background: #f1eff4; border-radius: 6px; overflow: hidden; }
        .survey-report-bar div { height: 100%; background: #6f8fb5; }
        .survey-report-bar-cell span { width: 40px; color: #6a6676; font-size: 12px; text-align: right; }
    `]
})
export class SurveyReportComponent implements OnInit, OnDestroy {

    report: SurveyReport | null = null;
    error = false;
    id = '';
    open = new Set<string>();

    protected subscriptions: Subscription[] = [];

    constructor(
        protected route: ActivatedRoute,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected formatter: DataTypeFormatter,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.subscriptions.push(this.route.paramMap.subscribe(params => {
            this.id = params.get('record') || this.route.snapshot.queryParamMap.get('record') || '';
            this.load();
        }));
    }

    ngOnDestroy(): void {
        this.subscriptions.forEach(subscription => subscription.unsubscribe());
    }

    label(key: string): string {
        return this.report?.labels?.[key] || key;
    }

    app(key: string): string {
        return this.language.getAppString(key) || key;
    }

    date(value: string): string {
        return value ? this.formatter.toUserFormat('datetime', value) : '';
    }

    /** The share of an answer in the answers of its question (percent). */
    share(count: ReportCount, counts: ReportCount[]): number {
        const total = counts.reduce((sum, item) => sum + item.count, 0);
        return total ? count.count * 100 / total : 0;
    }

    toggle(id: string): void {
        if (this.open.has(id)) {
            this.open.delete(id);
        } else {
            this.open.add(id);
        }
    }

    protected load(): void {
        this.report = null;
        this.error = false;
        if (!this.id) {
            this.error = true;
            return;
        }
        this.subscriptions.push(this.processService.submit('survey-questions', {action: 'report', id: this.id}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.report = process?.status === 'success' ? process.data as SurveyReport : null;
                this.error = !this.report;
                this.cdr.detectChanges();
            },
            error: () => {
                this.error = true;
                this.cdr.detectChanges();
            }
        }));
    }
}
