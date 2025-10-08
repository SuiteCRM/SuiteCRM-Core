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

import {
    Component,
    computed,
    HostBinding,
    Input,
    OnDestroy,
    OnInit,
    signal,
    Signal,
    Type,
    WritableSignal
} from '@angular/core';
import {Record} from '../../common/record/record.model';
import {Field} from '../../common/record/field.model';
import {EDITABLE_VIEW_MODES, ViewMode} from '../../common/views/view.model';
import {Router} from '@angular/router';
import {ModuleNameMapper} from '../../services/navigation/module-name-mapper/module-name-mapper.service';
import {ModuleNavigation} from '../../services/navigation/module-navigation/module-navigation.service';
import {DynamicLabelService} from '../../services/language/dynamic-label.service';
import {
    LinkRouteAsyncActionService
} from '../../services/navigation/link-route-async-action/link-route-async-action.service';
import {Subscription} from "rxjs";
import {ControlEvent, TouchedChangeEvent} from "@angular/forms";
import {ActiveFieldsChecker} from "../../services/condition-operators/active-fields-checker.service";
import {deepClone} from "../../common/utils/object-utils";

@Component({
    selector: 'scrm-dynamic-field',
    templateUrl: './dynamic-field.component.html',
    styleUrls: []
})
export class DynamicFieldComponent implements OnInit, OnDestroy {

    @Input('mode') mode: string;
    @Input('originalMode') originalMode: string;
    @Input('type') type: string;
    @Input('field') field: Field;
    @Input('record') record: Record = null;
    @Input('parent') parent: Record = null;
    @Input('klass') klass: { [key: string]: any } = null;
    @Input('componentType') componentType: Type<any>;

    @HostBinding('class') class = 'dynamic-field';

    isInvalid: Signal<boolean> = signal(false);
    touched: WritableSignal<boolean> = signal(false);
    activeFootnotes: WritableSignal<any[]> = signal([]);
    validateOnlyOnSubmit: boolean = false;
    protected subs: Subscription[] = [];

    constructor(
        protected navigation: ModuleNavigation,
        protected moduleNameMapper: ModuleNameMapper,
        protected router: Router,
        protected dynamicLabelService: DynamicLabelService,
        protected linkRouteAsyncActionService: LinkRouteAsyncActionService,
        protected activeFieldsChecker: ActiveFieldsChecker
    ) {
    }

    get getRelateLink(): string {
        let linkModule = this.field.definition.module ?? this.record.attributes[this.field.definition.type_name];

        if (this.field.definition.type_name === 'parent_type') {
            linkModule = this.record.attributes.parent_type;
        }

        if (this.field.definition.id_name && linkModule) {
            const moduleName = this.moduleNameMapper.toFrontend(linkModule);
            return this.navigation.getRecordRouterLink(
                moduleName,
                this.record.attributes[this.field.definition.id_name]
            );
        }

        return '';
    }

    ngOnInit(): void {
        this.setHostClass();
        this.validateOnlyOnSubmit = this.record?.metadata?.validateOnlyOnSubmit;

        if (this.record?.validationTriggered) {
            this.isInvalid = computed(() => {
                if (this.validateOnlyOnSubmit && this.record?.validationTriggered() && this.field.formControl?.invalid) {
                    return true;
                }
                return false;
            })
        }

        if (this?.field?.formControl?.touched) {
            this.touched.set(this.field.formControl.touched)
        }

        if (this?.field?.formControl?.events) {
            this.subs.push(this.field.formControl.events.subscribe((event: ControlEvent) => {
                if (!(event instanceof TouchedChangeEvent)) {
                    return;
                }

                const touched = event?.touched ?? null;

                if (touched === null) {
                    return;
                }

                if (event.touched && !this.touched()) {
                    this.touched.set(event.touched);
                    return;
                }

                if (!event.touched && this.touched()) {
                    this.touched.set(event.touched);
                    return;
                }


            }));
        }

        this.initHelpFootnotes();
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
        this.subs = [];
    }

    isLink(): boolean {

        if (EDITABLE_VIEW_MODES.includes(this.mode as ViewMode)) {
            return false;
        }

        if (!this.field || !this.record) {
            return false;
        }

        if (this.type === 'relate') {
            return true;
        }

        return !!(this?.field?.metadata && this?.field?.metadata?.link);
    }

    hasOnClick(): boolean {

        const fieldMetadata = this?.field?.metadata ?? {};
        const linkAsyncAction = fieldMetadata?.linkAsyncAction ?? null;
        const linkOnClick = fieldMetadata?.onClick ?? null;

        return !!(linkAsyncAction || linkOnClick);
    }

    isEdit(): boolean {
        return this.mode === 'edit' || this.mode === 'filter';
    }

    getLink(): string {
        if (this.type === 'relate') {
            return this.getRelateLink;
        }

        const fieldMetadata = this?.field?.metadata ?? null;
        const linkRoute = fieldMetadata.linkRoute ?? null;
        if (fieldMetadata && linkRoute) {
            return this.dynamicLabelService.parse(linkRoute, {}, this.record.fields, this.record.attributes);
        }

        return this.navigation.getRecordRouterLink(this.record.module, this.record.id);
    }




    onClick(): boolean {

        const fieldMetadata = this?.field?.metadata ?? null;
        if (fieldMetadata && fieldMetadata.onClick) {
            this.field.metadata.onClick(this.field, this.record);
            return;
        }

        const linkAsyncAction = fieldMetadata.linkAsyncAction ?? null;
        if (fieldMetadata && linkAsyncAction) {
            this.linkRouteAsyncActionService.run(linkAsyncAction, this.field, this.record);
            return;
        }

        this.router.navigateByUrl(this.getLink()).then();
        return false;
    }

    public setHostClass() {
        const classes = [];
        classes.push('dynamic-field');

        if (this.mode) {
            classes.push('dynamic-field-mode-' + this.mode)
        }

        if (this.type) {
            classes.push('dynamic-field-type-' + this.type)
        }

        if (this.field && this.field.name) {
            classes.push('dynamic-field-name-' + this.field.name)
        }

        this.class = classes.join(' ');
    }

    protected initHelpFootnotes(): void {

        const footnotes = this?.field?.definition?.footnotes ?? [];
        if (!footnotes.length) {
            this.activeFootnotes.set([]);
            return;
        }

        this.initActiveFootnotes();
        this.subs.push(this.field.valueChanges$.subscribe(() => {
            this.initActiveFootnotes();
        }));

    }

    protected initActiveFootnotes() {
        const footnotes = this?.field?.definition?.footnotes ?? [];

        const activeFootnotes: any[] = [];
        const defaultFootnotes: any[] = [];
        footnotes.forEach((footnote) => {

            const activeFootnote = this.initFootnote(footnote);
            if (!activeFootnote) {
                return;
            }

            if (activeFootnote?.default) {
                defaultFootnotes.push(activeFootnote);
                return;
            }

            if (!activeFootnote?.activeOn) {
                activeFootnotes.push(activeFootnote);
                return;
            }

            const isActive = this.activeFieldsChecker.isValueActive(this.record, this.field, footnote.activeOn);
            if (isActive) {
                activeFootnotes.push(activeFootnote);
            }
        });

        if (!activeFootnotes?.length && defaultFootnotes.length) {
            this.activeFootnotes.set(defaultFootnotes);
            return;
        }

        this.activeFootnotes.set(activeFootnotes);
    }

    protected initFootnote(footnote: any): any {
        const displayModes = footnote?.displayModes ?? [];
        if (!displayModes?.length) {
            return null;
        }

        if (!displayModes.includes(this.originalMode)) {
            return null;
        }

        const footnoteEntry = deepClone(footnote);
        footnoteEntry.context = footnote?.context ?? {};
        footnoteEntry.context.module = this.record?.module ?? '';

        return footnoteEntry;
    }

    getErrorPosition(): string {
        return this?.field?.metadata?.errorPosition ?? 'bottom';
    }

}
