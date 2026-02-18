import {ChangeDetectionStrategy, Component} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {
    EmailTemplatesTemplateFieldDefsResponse,
    EmailTemplatesVariableDef,
    EmailTemplatesVariableModule
} from '../../../../services/email-templates/email-templates-tools.model';
import {EmailTemplatesToolsService} from '../../../../services/email-templates/email-templates-tools.service';

@Component({
    selector: 'scrm-emailtemplates-variable-tools-edit',
    templateUrl: './emailtemplates-variable-tools.component.html',
    styles: [],
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class EmailTemplatesVariableToolsEditFieldComponent extends BaseFieldComponent {

    loaded = false;
    modules: EmailTemplatesVariableModule[] = [];
    fieldDefs: Record<string, EmailTemplatesVariableDef[]> = {};

    selectedModule = '';
    fields: EmailTemplatesVariableDef[] = [];
    selectedField = '';

    variableText = '';

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected tools: EmailTemplatesToolsService,
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();

        this.tools.getTemplateFieldDefs().subscribe({
            next: (resp: EmailTemplatesTemplateFieldDefsResponse) => {
                this.modules = resp?.modules ?? [];
                this.fieldDefs = resp?.fieldDefs ?? {};
                this.loaded = true;

                // Default selection: Contacts (legacy default), fallback to first module.
                const preferred = this.modules.find(m => m.key === 'Contacts')?.key;
                this.selectedModule = preferred ?? (this.modules[0]?.key ?? '');
                this.onModuleChange();
            },
            error: () => {
                this.loaded = true;
            }
        });
    }

    onModuleChange(): void {
        this.fields = this.fieldDefs[this.selectedModule] ?? [];
        this.selectedField = this.fields[0]?.name ?? '';
        this.onFieldChange();
    }

    onFieldChange(): void {
        this.variableText = this.selectedField ? `$${this.selectedField}` : '';
    }

    insertToSubject(): void {
        this.insertIntoField('subject', this.variableText);
    }

    insertToBody(): void {
        if (!this.variableText) {
            return;
        }

        // Best-effort cursor insertion for TinyMCE.
        const w = window as any;
        if (w?.tinymce?.activeEditor) {
            try {
                w.tinymce.activeEditor.execCommand('mceInsertRawHTML', false, this.variableText);
                return;
            } catch {
                // Fall back to appending.
            }
        }

        this.insertIntoField('body_html', this.variableText);
    }

    protected insertIntoField(fieldName: string, text: string): void {
        if (!text || !this.record?.fields?.[fieldName]) {
            return;
        }

        const f: any = this.record.fields[fieldName];
        const current = (f?.formControl?.value ?? f?.value ?? '') as string;
        const next = current + text;

        if (f?.formControl) {
            f.formControl.setValue(next);
            f.formControl.markAsDirty();
        }

        f.value = next;
    }
}
