import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';

import {EmailTemplatesAttachmentsEditFieldComponent} from './emailtemplates-attachments.component';
import {LabelModule} from '../../../../components/label/label.module';

@NgModule({
    declarations: [EmailTemplatesAttachmentsEditFieldComponent],
    imports: [CommonModule, LabelModule],
    exports: [EmailTemplatesAttachmentsEditFieldComponent]
})
export class EmailTemplatesAttachmentsEditFieldModule {
}
