import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';

import {EmailTemplatesVariableToolsEditFieldComponent} from './emailtemplates-variable-tools.component';

@NgModule({
    declarations: [EmailTemplatesVariableToolsEditFieldComponent],
    imports: [CommonModule, FormsModule],
    exports: [EmailTemplatesVariableToolsEditFieldComponent]
})
export class EmailTemplatesVariableToolsEditFieldModule {
}
