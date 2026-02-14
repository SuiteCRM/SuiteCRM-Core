import {ChangeDetectionStrategy, Component} from '@angular/core';
import {EmailTemplatesAttachmentsEditFieldComponent} from '../../edit/emailtemplates-attachments.component';

@Component({
    selector: 'scrm-emailtemplates-attachments-detail',
    templateUrl: '../../edit/emailtemplates-attachments.component.html',
    styles: [],
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class EmailTemplatesAttachmentsDetailFieldComponent extends EmailTemplatesAttachmentsEditFieldComponent {
}
