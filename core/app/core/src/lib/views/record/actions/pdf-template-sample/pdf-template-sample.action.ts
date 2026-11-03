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
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {take} from 'rxjs/operators';
import {ViewMode} from '../../../../common/views/view.model';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {MessageService} from '../../../../services/message/message.service';
import {RecordActionData, RecordActionHandler} from '../record.action';
import {
    PdfTemplateSample,
    PdfTemplateSampleModalComponent
} from '../../../../components/pdf-template-sample-modal/pdf-template-sample-modal.component';

/**
 * "Load Sample" of the PDF template create and edit views (recordActions of
 * AOS_PDF_Templates/metadata/detailviewdefs.php), as the sample field of the legacy editor: a
 * sample template (modules/AOS_PDF_Templates/samples, process pdf-template-editor) sets the type,
 * body, header and footer of the template.
 */
@Injectable({providedIn: 'root'})
export class PdfTemplateSampleAction extends RecordActionHandler {

    static readonly PROCESS = 'pdf-template-editor';
    static readonly FIELDS: { [field: string]: keyof PdfTemplateSample } = {
        type: 'type',
        description: 'body',
        pdfheader: 'header',
        pdffooter: 'footer',
    };

    key = 'pdf-load-sample';
    modes = ['edit', 'create'] as ViewMode[];

    constructor(
        protected processService: ProcessService,
        protected modals: NgbModal,
        protected message: MessageService
    ) {
        super();
    }

    shouldDisplay(data: RecordActionData): boolean {
        return true;
    }

    run(data: RecordActionData): void {
        const fields = data?.store?.recordStore?.getStaging()?.fields ?? {};

        this.processService.submit(PdfTemplateSampleAction.PROCESS, {action: 'samples'}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                const samples = (process?.data?.samples ?? []) as PdfTemplateSample[];
                if (process?.status !== 'success' || !samples.length) {
                    this.message.addDangerMessageByKey('LBL_ACTION_ERROR');
                    return;
                }

                const modal = this.modals.open(PdfTemplateSampleModalComponent);
                modal.componentInstance.samples = samples;
                modal.componentInstance.overwrite = ['description', 'pdfheader', 'pdffooter'].some(name => {
                    return `${fields[name]?.value ?? ''}`.replace(/<[^>]*>|&nbsp;|\s/g, '') !== '';
                });
                modal.closed.pipe(take(1)).subscribe((sample: PdfTemplateSample) => {
                    Object.entries(PdfTemplateSampleAction.FIELDS).forEach(([name, property]) => {
                        const formControl = fields[name]?.formControl;
                        if (sample && formControl) {
                            formControl.setValue(sample[property]);
                            formControl.markAsDirty();
                        }
                    });
                });
            },
            error: () => this.message.addDangerMessageByKey('LBL_ACTION_ERROR')
        });
    }
}
