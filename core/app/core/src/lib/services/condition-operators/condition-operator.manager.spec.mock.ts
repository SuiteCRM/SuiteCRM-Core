import {ConditionOperatorManager} from './condition-operator.manager';
import {GreaterThanAction} from './greater-than/greater-than.action';
import {LessThanAction} from './less-than/less-than.action';
import {NotEmptyAction} from './not-empty/not-empty.action';
import {IsEmptyAction} from './is-empty/is-empty.action';
import {IsEqualAction} from './is-equal/is-equal.action';
import {NotEqualAction} from './not-equal/not-equal.action';
import {AnyValueAction} from './any-value/any-value.action';
import {IsTrueAction} from './is-true/is-true.action';
import {IsFalseAction} from './is-false/is-false.action';
import {PreviousValueEqualAction} from './previous-value-equal/previous-value-equal.action';
import {PreviousValueNotEqualAction} from './previous-value-not-equal/previous-value-not-equal.action';
import {ActiveFieldsChecker} from './active-fields-checker.service';

const operatorManager = new ConditionOperatorManager(
    new GreaterThanAction(),
    new LessThanAction(),
    new NotEmptyAction(),
    new IsEmptyAction(),
    new IsEqualAction(),
    new NotEqualAction(),
    new AnyValueAction(),
    new IsTrueAction(),
    new IsFalseAction(),
    new PreviousValueEqualAction(),
    new PreviousValueNotEqualAction()
);

export const activeFieldsChecker = new ActiveFieldsChecker(operatorManager);
