/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

import {ValidationManager} from './validation.manager';
import {RequiredValidator} from './validators/required.validator';
import {RangeValidator} from './validators/range.validator';
import {CurrencyValidator} from './validators/currency.validator';
import {DateValidator} from './validators/date.validator';
import {DateTimeValidator} from './validators/datetime.validator';
import {EmailValidator} from './validators/email.validator';
import {FloatValidator} from './validators/float.validator';
import {IntValidator} from './validators/int.validator';
import {PhoneValidator} from './validators/phone.validator';
import {numberFormatterMock} from '../../formatters/number/number-formatter.spec.mock';
import {dateFormatterMock} from '../../formatters/datetime/date-formatter.service.spec.mock';
import {datetimeFormatterMock} from '../../formatters/datetime/datetime-formatter.service.spec.mock';
import {phoneFormatterMock} from '../../formatters/phone/phone-formatter.spec.mock';
import {emailFormatterMock} from '../../formatters/email/email-formatter.spec.mock';
import {FormControlUtils} from '../field/form-control.utils';
import {PrimaryEmailValidator} from './validators/primary-email.validator';
import {DuplicateEmailValidator} from './validators/duplicate-email.validator';
import {LineItemsRequiredValidator} from './validators/line-items-required.validator';
import {AttachmentValidator} from './validators/attachment.validator';
import {SemanticVersionValidator} from './validators/semantic-version.validator';
import {AsyncProcessValidator} from './async-validators/async-process.validator';
import {ValidationDefinitionManager} from './validation-definition.manager';
import {confirmationModalServiceMock} from '../../modals/confirmation-modal.spec.mock';
import {preferenceProcessMock} from '../../process/process.service.spec.mock';
import {dataTypeFormatterMock} from '../../formatters/data-type.formatter.spec.mock';
import {fieldMapper} from '../field/field.mapper.spec.mock';

const validationDefinitions = new ValidationDefinitionManager();
export const validationManagerMock = new ValidationManager(
    new AsyncProcessValidator(preferenceProcessMock, confirmationModalServiceMock, fieldMapper, dataTypeFormatterMock),
    new RequiredValidator(new FormControlUtils()),
    new RangeValidator(validationDefinitions),
    new CurrencyValidator(numberFormatterMock),
    new DateValidator(dateFormatterMock, new FormControlUtils()),
    new DateTimeValidator(datetimeFormatterMock),
    new EmailValidator(emailFormatterMock),
    new FloatValidator(numberFormatterMock),
    new IntValidator(numberFormatterMock),
    new PhoneValidator(phoneFormatterMock),
    new PrimaryEmailValidator(),
    new DuplicateEmailValidator(),
    new LineItemsRequiredValidator(new FormControlUtils()),
    new AttachmentValidator(new FormControlUtils()),
    new SemanticVersionValidator(validationDefinitions)
);
