/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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
import {Injectable} from "@angular/core";
import {Record} from "../../../../common/record/record.model";
import {ViewFieldDefinition} from "../../../../common/metadata/metadata.model";
import {StandardValidationErrors} from "../../../../common/services/validators/validators.model";
import {AbstractControl, AsyncValidatorFn} from "@angular/forms";
import {AsyncProcessValidatorInterface} from "../aync-validator.Interface";
import {Process, ProcessService} from "../../../process/process.service";
import {map, switchMap, take} from "rxjs/operators";
import {Observable, of, Subject} from "rxjs";
import {AsyncValidationDefinition} from "../../../../common/record/field.model";
import {ConfirmationModalService} from "../../../modals/confirmation-modal.service";
import {FieldMapper} from "../../field/field.mapper";
import {DataTypeFormatter} from "../../../formatters/data-type.formatter.service";
import {SavedFilter} from "../../../../store/saved-filters/saved-filter.model";

export const asyncValidator = (
    validator: AsyncValidationDefinition,
    viewField: ViewFieldDefinition,
    record: Record,
    processService: ProcessService,
    confirmationModalService: ConfirmationModalService,
    fieldMapper: FieldMapper,
    formatter: DataTypeFormatter
): AsyncValidatorFn => (
    (control: AbstractControl): Promise<StandardValidationErrors | null> | Observable<StandardValidationErrors | null> => {

        const processKey = validator.key;

        const fieldType = viewField?.type ?? viewField.fieldDefinition?.type ?? '';

        const value = formatter.toInternalFormat(fieldType, control.value ?? '');

        const attributes = fieldMapper.getAttributesMappedFromFields(record);

        const options = {
            value: value,
            inputValue: control.value ?? '',
            definition: viewField,
            attributes: attributes,
            originalAttributes: record?.attributes ?? {},
            params: validator?.params ?? {}
        };

        return processService.submit(processKey, options).pipe(switchMap((process: Process) => {

            if (process.status !== 'error') {
                return of(null);
            }

            if (process?.data?.displayConfirmation ?? false) {
                const confirmationSubject = new Subject<boolean>();
                const confirmation$ = confirmationSubject.asObservable();

                const confirmationLabel = process.data?.confirmationLabel ?? '';
                const confirmationMessages = process.data?.confirmationMessages ?? [];
                const confirmationTitle = process.data?.confirmationTitle ?? '';
                const confirmation = [confirmationLabel, ...confirmationMessages];

                if (Object.entries(confirmation).length === 0) {
                    confirmation.push('LBL_GENERIC_CONFIRMATION');
                }

                confirmationModalService.showModal(
                    confirmation,
                    () => confirmationSubject.next(true),
                    () => confirmationSubject.next(false),
                    record.fields,
                    {value: control.value},
                    confirmationTitle
                )

                return confirmation$.pipe(
                    take(1),
                    map((confirmed: boolean) => {
                        if (confirmed) {
                            return null;
                        }

                        const error = {
                            [processKey]: {
                                message: {
                                    labels: {
                                        startLabelKey: '',
                                        icon: '',
                                        endLabelKey: '',
                                    },
                                    context: {
                                        value: control.value
                                    }
                                },
                                silent: true
                            }
                        }

                        if (process?.data?.errors ?? false) {
                            Object.keys(process?.data?.errors).forEach((key) => {
                                if (error[processKey].message.labels[key] === '') {
                                    error[processKey].message.labels[key] = process?.data?.errors[key];
                                }
                            });
                        }

                        record.fields[viewField.name].asyncValidationErrors = error;

                        return error;
                    })
                );
            }

            const error = {
                [processKey]: {
                    message: {
                        labels: {
                            startLabelKey: '',
                            icon: '',
                            endLabelKey: '',
                        },
                        context: {
                            value: control.value
                        }
                    }
                }
            }

            if (process?.data?.errors ?? false) {
                Object.keys(process?.data?.errors).forEach((key) => {
                    if (error[processKey].message.labels[key] === '') {
                        error[processKey].message.labels[key] = process?.data?.errors[key];
                    }
                });
            }


            record.fields[viewField.name].asyncValidationErrors = error;

            return of(error);
        }), take(1));
    }
);

export const recordAsyncValidator = (
    validator: AsyncValidationDefinition,
    record: Record,
    processService: ProcessService,
    confirmationModalService: ConfirmationModalService,
    fieldMapper: FieldMapper
): AsyncValidatorFn => (
    (control: AbstractControl): Promise<StandardValidationErrors | null> | Observable<StandardValidationErrors | null> => {

        const processKey = validator.key;
        const attributes = fieldMapper.getAttributesMappedFromFields(record);

        const options = {
            attributes: attributes,
            originalAttributes: record?.attributes ?? {},
            params: validator?.params ?? {},
            module: record?.module ?? ''
        };

        return processService.submit(processKey, options).pipe(switchMap((process: Process) => {

            if (process.status !== 'error') {
                return of(null);
            }

            if (process?.data?.displayListConfirmation ?? false) {
                const confirmationSubject = new Subject<boolean>();
                const confirmation$ = confirmationSubject.asObservable();

                const presetConfig = process.data?.preset ?? {};
                const presetFilter = {
                    key: 'default',
                    criteria: {
                        preset: {
                            type: presetConfig.type ?? '',
                            params: presetConfig.params ?? {}
                        }
                    }
                } as SavedFilter;

                confirmationModalService.showListModal({
                    titleKey: process.data?.confirmationTitle ?? '',
                    messageKey: process.data?.confirmationLabel ?? '',
                    module: process.data?.module ?? record?.module ?? '',
                    presetFilter,
                    showFilter: process.data?.showFilter ?? false,
                    columnFields: process.data?.fields ?? [],
                    actions: process.data?.actions ?? [],
                    onProceed: () => confirmationSubject.next(true),
                    onClose: () => confirmationSubject.next(false)
                });

                return confirmation$.pipe(
                    take(1),
                    map((confirmed: boolean) => {
                        if (confirmed) {
                            return null;
                        }

                        return buildRecordValidationError(processKey, process, true);
                    })
                );
            }

            if (process?.data?.displayConfirmation ?? false) {
                const confirmationSubject = new Subject<boolean>();
                const confirmation$ = confirmationSubject.asObservable();

                const confirmationLabel = process.data?.confirmationLabel ?? '';
                const confirmationMessages = process.data?.confirmationMessages ?? [];
                const confirmationTitle = process.data?.confirmationTitle ?? '';
                const confirmation = [confirmationLabel, ...confirmationMessages];

                if (Object.entries(confirmation).length === 0) {
                    confirmation.push('LBL_GENERIC_CONFIRMATION');
                }

                confirmationModalService.showModal(
                    confirmation,
                    () => confirmationSubject.next(true),
                    () => confirmationSubject.next(false),
                    record.fields,
                    {},
                    confirmationTitle
                );

                return confirmation$.pipe(
                    take(1),
                    map((confirmed: boolean) => {
                        if (confirmed) {
                            return null;
                        }

                        return buildRecordValidationError(processKey, process, true);
                    })
                );
            }

            return of(buildRecordValidationError(processKey, process));
        }), take(1));
    }
);

function buildRecordValidationError(
    processKey: string,
    process: Process,
    silent: boolean = false
): StandardValidationErrors {
    return {
        [processKey]: {
            message: {
                labelKey: process?.data?.errorLabelKey ?? '',
                context: {}
            },
            silent
        }
    };
}

@Injectable({
    providedIn: 'root'
})
export class AsyncProcessValidator implements AsyncProcessValidatorInterface {

    constructor(
        protected processService: ProcessService,
        protected confirmationModalService: ConfirmationModalService,
        protected fieldMapper: FieldMapper,
        protected formatter: DataTypeFormatter
    ) {
    }

    applies(record: Record, viewField: ViewFieldDefinition): boolean {
        return !(!viewField || !viewField.fieldDefinition);
    }

    getValidator(validator: AsyncValidationDefinition, viewField: ViewFieldDefinition, record: Record): AsyncValidatorFn {
        if (!viewField || !viewField.fieldDefinition) {
            return null;
        }

        if (!validator?.key) {
            return null;
        }

        return asyncValidator(validator, viewField, record, this.processService, this.confirmationModalService, this.fieldMapper, this.formatter);
    }

    getRecordValidator(validator: AsyncValidationDefinition, record: Record): AsyncValidatorFn {
        if (!validator?.key) {
            return null;
        }

        return recordAsyncValidator(validator, record, this.processService, this.confirmationModalService, this.fieldMapper);
    }
}
