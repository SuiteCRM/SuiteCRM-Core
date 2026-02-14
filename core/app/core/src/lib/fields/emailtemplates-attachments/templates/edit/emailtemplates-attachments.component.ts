import {ChangeDetectionStrategy, Component, ViewChild} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {EmailTemplatesToolsService} from '../../../../services/email-templates/email-templates-tools.service';
import {
    EmailTemplatesAttachmentItem,
    EmailTemplatesAttachmentsListResponse
} from '../../../../services/email-templates/email-templates-tools.model';
import {SelectModalService} from '../../../../services/modals/select-modal.service';
import {Record} from '../../../../common/record/record.model';

@Component({
    selector: 'scrm-emailtemplates-attachments-edit',
    templateUrl: './emailtemplates-attachments.component.html',
    styles: [],
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class EmailTemplatesAttachmentsEditFieldComponent extends BaseFieldComponent {

    items: EmailTemplatesAttachmentItem[] = [];
    loading = false;

    @ViewChild('fileInput') fileInput: any;

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected tools: EmailTemplatesToolsService,
        protected selectModal: SelectModalService,
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.refresh();
    }

    refresh(): void {
        const templateId = this.record?.id ?? '';
        if (!templateId) {
            return;
        }

        this.loading = true;
        this.tools.listAttachments(templateId).subscribe({
            next: (resp: EmailTemplatesAttachmentsListResponse) => {
                this.items = resp?.items ?? [];
                this.loading = false;
            },
            error: () => {
                this.items = [];
                this.loading = false;
            }
        });
    }

    triggerUpload(): void {
        this.fileInput?.nativeElement?.click();
    }

    onFilesSelected(event: any): void {
        const files: FileList = event?.target?.files;
        if (!files || files.length === 0) {
            return;
        }

        const templateId = this.record?.id ?? '';
        if (!templateId) {
            return;
        }

        Array.from(files).forEach((file) => {
            this.tools.uploadAttachment(templateId, file).subscribe({
                next: (item: EmailTemplatesAttachmentItem) => {
                    this.items = [item, ...this.items];
                }
            });
        });

        // reset input
        event.target.value = '';
    }

    attachDocument(): void {
        const templateId = this.record?.id ?? '';
        if (!templateId) {
            return;
        }

        this.selectModal.showSelectModal('documents', (doc: Record) => {
            const documentId = doc?.id ?? '';
            if (!documentId) {
                return;
            }

            this.tools.attachDocument(templateId, documentId).subscribe({
                next: (item: EmailTemplatesAttachmentItem) => {
                    this.items = [item, ...this.items];
                }
            });
        });
    }

    deleteItem(item: EmailTemplatesAttachmentItem): void {
        const templateId = this.record?.id ?? '';
        if (!templateId || !item?.id) {
            return;
        }

        this.tools.deleteAttachment(templateId, item.id).subscribe({
            next: () => {
                this.items = this.items.filter(i => i.id !== item.id);
            }
        });
    }
}
