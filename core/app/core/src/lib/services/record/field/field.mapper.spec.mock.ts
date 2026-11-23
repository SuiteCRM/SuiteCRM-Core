import {FieldMapper} from './field.mapper';
import {RecordMapperRegistry} from '../../../common/record/record-mappers/record-mapper.registry';
import {BaseSaveRecordMapper} from '../../../store/record/record-mappers/base-save.record-mapper';
import {activeFieldsChecker} from '../../condition-operators/condition-operator.manager.spec.mock';

export const baseSaveRecordMapper = new BaseSaveRecordMapper(activeFieldsChecker);
export const fieldMapper = new FieldMapper(new RecordMapperRegistry(), baseSaveRecordMapper);
