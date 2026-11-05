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

import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../store/language/language.store';
import {MessageService} from '../../../services/message/message.service';
import {Process, ProcessService} from '../../../services/process/process.service';
import {Record} from '../../../common/record/record.model';
import {RecordActionData, RecordActionHandler} from '../../record/actions/record.action';
import {SelectModalService} from '../../../services/modals/select-modal.service';
import {ViewMode} from '../../../common/views/view.model';
import {ReportViewerState} from '../services/report-viewer.state';
import {ReportFile} from '../report.model';

/**
 * Actions of the report page (modules/AOR_Reports/metadata/detailviewdefs.php),
 * as the buttons of the legacy report page.
 */
abstract class ReportAction extends RecordActionHandler {
    modes = ['detail'] as ViewMode[];

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }

    protected recordId(data: RecordActionData): string {
        return data?.store?.recordStore?.getBaseRecord()?.id ?? '';
    }
}

/** Edit: the report editor (route reports/edit/:record). */
@Injectable({providedIn: 'root'})
export class ReportEditAction extends ReportAction {
    key = 'report-edit';

    constructor(protected router: Router) {
        super();
    }

    run(data: RecordActionData): void {
        const id = this.recordId(data);
        if (id) {
            this.router.navigate(['/reports/edit', id]).then();
        }
    }
}

/** Duplicate: the report editor with a copy of the report (legacy Duplicate button). */
@Injectable({providedIn: 'root'})
export class ReportDuplicateAction extends ReportAction {
    key = 'report-duplicate';

    constructor(protected router: Router) {
        super();
    }

    run(data: RecordActionData): void {
        const id = this.recordId(data);
        if (id) {
            this.router.navigate(['/reports/edit'], {queryParams: {record: id, isDuplicate: 'true'}}).then();
        }
    }
}

/**
 * Export (CSV) and Download PDF: the file of the report with the parameter values of the
 * report shown on the page (and its charts for the PDF), made by the backend as the legacy
 * Export and Download PDF actions (process `report-viewer`, action `export`), then saved.
 */
@Injectable()
abstract class ReportDownloadAction extends ReportAction {

    protected abstract format: 'csv' | 'pdf';

    constructor(
        protected state: ReportViewerState,
        protected processService: ProcessService,
        protected messages: MessageService,
        protected language: LanguageStore
    ) {
        super();
    }

    run(data: RecordActionData): void {
        const id = this.recordId(data);
        if (!id) {
            return;
        }

        const report = this.state.get(id);
        const charts = this.format === 'pdf' && report ? report.chartImages() : Promise.resolve([]);
        charts.then(images => {
            this.processService.submit('report-viewer', {
                action: 'export',
                id,
                format: this.format,
                parameters: report?.parameters ?? {},
                charts: images
            }).pipe(take(1)).subscribe({
                next: (process: Process) => {
                    const file = process?.data as ReportFile;
                    if (process?.status !== 'success' || !file?.content) {
                        this.messages.addDangerMessage(this.label('LBL_REPORT_VIEWER_EXPORT_ERROR'));
                        return;
                    }
                    this.save(file);
                },
                error: () => this.messages.addDangerMessage(this.label('LBL_REPORT_VIEWER_EXPORT_ERROR'))
            });
        });
    }

    protected save(file: ReportFile): void {
        const bytes = Uint8Array.from(atob(file.content), character => character.charCodeAt(0));
        const url = URL.createObjectURL(new Blob([bytes], {type: file.type}));
        const link = document.createElement('a');
        link.href = url;
        link.download = file.name;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    protected label(key: string): string {
        return this.language.getFieldLabel(key, 'reports') || key;
    }
}

@Injectable({providedIn: 'root'})
export class ReportExportAction extends ReportDownloadAction {
    key = 'report-export';
    protected format = 'csv' as const;
}

@Injectable({providedIn: 'root'})
export class ReportPdfAction extends ReportDownloadAction {
    key = 'report-pdf';
    protected format = 'pdf' as const;
}

/**
 * Add To Target List: the records of the report (with the parameter values of the page) are
 * added to the target list picked in the list of target lists.
 */
@Injectable({providedIn: 'root'})
export class ReportTargetListAction extends ReportAction {
    key = 'report-target-list';

    constructor(
        protected state: ReportViewerState,
        protected selectModal: SelectModalService,
        protected processService: ProcessService,
        protected messages: MessageService,
        protected language: LanguageStore
    ) {
        super();
    }

    run(data: RecordActionData): void {
        const id = this.recordId(data);
        if (!id) {
            return;
        }

        this.selectModal.showSelectModal('prospect-lists', (targetList: Record) => {
            if (!targetList?.id) {
                return;
            }

            this.processService.submit('report-viewer', {
                action: 'targetList',
                id,
                parameters: this.state.get(id)?.parameters ?? {},
                targetListId: targetList.id
            }).pipe(take(1)).subscribe({
                next: (process: Process) => {
                    if (process?.status !== 'success') {
                        this.messages.addDangerMessage(this.label('LBL_REPORT_VIEWER_TARGET_LIST_ERROR'));
                        return;
                    }
                    const name = targetList.attributes?.name ?? '';
                    this.messages.addSuccessMessage(this.label('LBL_REPORT_VIEWER_TARGET_LIST_DONE')
                        .replace('%s', String(process.data?.count ?? 0))
                        .replace('%s', name));
                },
                error: () => this.messages.addDangerMessage(this.label('LBL_REPORT_VIEWER_TARGET_LIST_ERROR'))
            });
        });
    }

    protected label(key: string): string {
        return this.language.getFieldLabel(key, 'reports') || key;
    }
}
