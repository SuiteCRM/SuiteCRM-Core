import {ChangeDetectionStrategy, Component} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';

@Component({
    selector: 'scrm-emailtemplates-variable-tools-detail',
    template: '',
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class EmailTemplatesVariableToolsDetailFieldComponent extends BaseFieldComponent {

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();

        // Variable insertion tools are edit-only (legacy parity).
        this.field?.display?.set?.('none');
    }
}

