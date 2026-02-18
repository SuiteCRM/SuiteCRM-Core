import {ChangeDetectionStrategy, ChangeDetectorRef, Component} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import {LanguageStore} from '../../../../store/language/language.store';

@Component({
    selector: 'scrm-emailtemplates-plain-text-toggle-detail',
    templateUrl: './emailtemplates-plain-text-toggle.component.html',
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class EmailTemplatesPlainTextToggleDetailFieldComponent extends BaseFieldComponent {

    protected textOnlyMode = false;
    protected visibilityRetryTimer: ReturnType<typeof setTimeout> | null = null;

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected language: LanguageStore,
        protected cdr: ChangeDetectorRef,
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.initTextOnlyMode();
        this.bindTextOnlyChanges();
        this.applyVisibilityForCurrentModeWithRetry();
    }

    ngOnDestroy(): void {
        if (this.visibilityRetryTimer) {
            clearTimeout(this.visibilityRetryTimer);
            this.visibilityRetryTimer = null;
        }
        super.ngOnDestroy();
    }

    togglePlainText(): void {
        if (this.textOnlyMode) {
            return;
        }

        const body: any = this.record?.fields?.body;
        if (!body?.display?.set) {
            return;
        }

        body.display.set(this.isPlainTextVisible() ? 'none' : 'show');
    }

    isPlainTextVisible(): boolean {
        const body: any = this.record?.fields?.body;
        if (!body?.display) {
            return false;
        }
        return body.display() !== 'none';
    }

    get buttonLabel(): string {
        const key = this.isPlainTextVisible() ? 'LBL_HIDE_ALT_TEXT' : 'LBL_SHOW_ALT_TEXT';
        return this.language.getFieldLabel(key) || (this.isPlainTextVisible() ? 'Hide Plain Text' : 'Show Plain Text');
    }

    isToggleVisible(): boolean {
        return !this.textOnlyMode;
    }

    private initTextOnlyMode(): void {
        const textOnly: any = this.record?.fields?.text_only;
        this.textOnlyMode = this.toBoolean(textOnly?.value ?? textOnly?.formControl?.value ?? false);
    }

    private bindTextOnlyChanges(): void {
        const textOnly: any = this.record?.fields?.text_only;
        if (textOnly?.valueChanges$) {
            const fieldValueSub = textOnly.valueChanges$.subscribe((change: any) => {
                this.textOnlyMode = this.toBoolean(change?.value);
                this.applyVisibilityForCurrentModeWithRetry();
                this.cdr.markForCheck();
            });
            this.subs.push(fieldValueSub);
        }
    }

    private applyVisibilityForCurrentModeWithRetry(attempt: number = 0): void {
        const applied = this.applyVisibilityForCurrentMode();
        if (applied || attempt >= 8) {
            return;
        }

        if (this.visibilityRetryTimer) {
            clearTimeout(this.visibilityRetryTimer);
        }

        this.visibilityRetryTimer = setTimeout(() => {
            this.applyVisibilityForCurrentModeWithRetry(attempt + 1);
        }, 60);
    }

    private applyVisibilityForCurrentMode(): boolean {
        const body: any = this.record?.fields?.body;
        const bodyHtml: any = this.record?.fields?.body_html;
        if (!body?.display?.set || !bodyHtml?.display?.set) {
            return false;
        }

        if (this.textOnlyMode) {
            if (this.field) {
                this.field.metadata = this.field.metadata ?? {};
                (this.field.metadata as any).wrapperCollapsed = true;
            }
            bodyHtml.display.set('none');
            body.display.set('show');
            return true;
        }

        if (this.field) {
            this.field.metadata = this.field.metadata ?? {};
            (this.field.metadata as any).wrapperCollapsed = false;
        }
        bodyHtml.display.set('show');
        body.display.set('none');
        return true;
    }

    private toBoolean(value: any): boolean {
        if (typeof value === 'boolean') {
            return value;
        }

        if (typeof value === 'number') {
            return value === 1;
        }

        if (typeof value === 'string') {
            const normalized = value.trim().toLowerCase();
            return normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on';
        }

        return false;
    }
}
