export interface EmailTemplatesVariableModule {
    key: string;
    label: string;
}

export interface EmailTemplatesVariableDef {
    name: string;
    value: string;
}

export interface EmailTemplatesTemplateFieldDefsResponse {
    modules: EmailTemplatesVariableModule[];
    fieldDefs: Record<string, EmailTemplatesVariableDef[]>;
}

export interface EmailTemplatesAttachmentItem {
    id: string;
    filename: string;
    mimeType: string;
    downloadUrl: string;
}

export interface EmailTemplatesAttachmentsListResponse {
    items: EmailTemplatesAttachmentItem[];
}
