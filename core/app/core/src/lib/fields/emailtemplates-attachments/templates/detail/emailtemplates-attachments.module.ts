import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';

import {EmailTemplatesAttachmentsDetailFieldComponent} from './emailtemplates-attachments.component';
import {LabelModule} from '../../../../components/label/label.module';

@NgModule({
    declarations: [EmailTemplatesAttachmentsDetailFieldComponent],
    imports: [CommonModule, LabelModule],
    exports: [EmailTemplatesAttachmentsDetailFieldComponent]
})
export class EmailTemplatesAttachmentsDetailFieldModule {
}
