import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {Observable} from 'rxjs';
import {
    EmailTemplatesAttachmentItem,
    EmailTemplatesAttachmentsListResponse,
    EmailTemplatesTemplateFieldDefsResponse
} from './email-templates-tools.model';

@Injectable({
    providedIn: 'root'
})
export class EmailTemplatesToolsService {

    constructor(
        protected http: HttpClient,
    ) {
    }

    getTemplateFieldDefs(): Observable<EmailTemplatesTemplateFieldDefsResponse> {
        return this.http.get<EmailTemplatesTemplateFieldDefsResponse>('./api/email-templates/template-field-defs');
    }

    listAttachments(templateId: string): Observable<EmailTemplatesAttachmentsListResponse> {
        return this.http.get<EmailTemplatesAttachmentsListResponse>(`./api/email-templates/${templateId}/attachments`);
    }

    uploadAttachment(templateId: string, file: File): Observable<EmailTemplatesAttachmentItem> {
        const formData = new FormData();
        formData.append('file', file);
        return this.http.post<EmailTemplatesAttachmentItem>(`./api/email-templates/${templateId}/attachments/upload`, formData);
    }

    attachDocument(templateId: string, documentId: string): Observable<EmailTemplatesAttachmentItem> {
        return this.http.post<EmailTemplatesAttachmentItem>(
            `./api/email-templates/${templateId}/attachments/document`,
            {documentId}
        );
    }

    deleteAttachment(templateId: string, noteId: string): Observable<{ ok: boolean }> {
        return this.http.delete<{ ok: boolean }>(`./api/email-templates/${templateId}/attachments/${noteId}`);
    }
}
