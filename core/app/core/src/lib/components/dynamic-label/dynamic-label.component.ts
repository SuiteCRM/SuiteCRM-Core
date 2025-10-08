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

import {Component, Input, OnChanges, OnDestroy, OnInit, signal, SimpleChanges, WritableSignal} from '@angular/core';
import {Field, FieldMap} from '../../common/record/field.model';
import {StringMap} from '../../common/types/string-map';
import {Observable, Subscription} from 'rxjs';
import {LanguageStore, LanguageStrings} from '../../store/language/language.store';
import {DynamicLabelService} from '../../services/language/dynamic-label.service';

@Component({
    selector: 'scrm-dynamic-label',
    templateUrl: './dynamic-label.component.html',
    styleUrls: []
})
export class DynamicLabelComponent implements OnInit, OnChanges, OnDestroy {
    @Input() template = '';
    @Input() labelKey = '';
    @Input() emptyLabel = '';
    @Input() context: StringMap = {};
    @Input() fields: FieldMap = {};
    @Input() module: string = null;
    @Input() displayTitle: boolean = false;

    parsedLabel: WritableSignal<string> = signal('');
    vm$: Observable<LanguageStrings>;
    protected subs: Subscription[] = [];
    protected fieldSubs: Subscription[] = [];

    constructor(protected dynamicLabels: DynamicLabelService, protected language: LanguageStore) {
    }

    ngOnInit(): void {
        this.subs.push(this.language.vm$.subscribe(() => {
            if (this.labelKey) {
                this.template = this.language.getFieldLabel(this.labelKey, this.module);
            }
            this.parseLabel();
        }));

        this.initFieldSubs();
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
        this.clearFieldSubscriptions();
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes.fields) {
            this.clearFieldSubscriptions();
            this.initFieldSubs();
        }

        if (changes.template || changes.context || changes.labelKey || changes.fields) {
            this.parseLabel();
        }
    }

    protected parseLabel(): void {
        let label = this.dynamicLabels.parse(this.template, this.context, this.fields);
        if (label.length === 0){
            label = this.language.getFieldLabel(this.emptyLabel, this.module).toUpperCase();
        }
        this.parsedLabel.set(label);
    }

    protected initFieldSubs(): void {
        const fieldKeys = Object.keys(this.fields ?? {});
        if (fieldKeys.length) {
            fieldKeys.forEach(key => {
                const field = this?.fields[key] ?? {} as Field;
                const fieldValueChanges$ = field?.valueChanges$ ?? null;

                if (fieldValueChanges$) {
                    this.fieldSubs.push(fieldValueChanges$.subscribe(() => {
                        this.parseLabel();
                    }));
                }
            });
        }
    }

    protected clearFieldSubscriptions() {
        this.fieldSubs.forEach(sub => sub.unsubscribe());
        this.fieldSubs = [];
    }
}
