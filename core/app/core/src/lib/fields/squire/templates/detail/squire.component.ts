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

import {
    AfterViewInit,
    ChangeDetectionStrategy,
    Component,
    ElementRef,
    HostListener,
    OnDestroy,
    signal,
    ViewChild,
    WritableSignal
} from '@angular/core';
import {BaseFieldComponent} from '../../../base/base-field.component';
import {DataTypeFormatter} from '../../../../services/formatters/data-type.formatter.service';
import {FieldLogicManager} from '../../../field-logic/field-logic.manager';
import {SystemConfigStore} from '../../../../store/system-config/system-config.store';
import {merge} from 'lodash-es';
import {FieldLogicDisplayManager} from '../../../field-logic-display/field-logic-display.manager';
import Squire from 'squire-rte'
import {DomSanitizer} from "@angular/platform-browser";
import {SquireConfig} from "squire-rte/dist/types/Editor";
import {
    ScreenSize,
    ScreenSizeObserverService
} from "../../../../services/ui/screen-size-observer/screen-size-observer.service";
import {LanguageStore} from "../../../../store/language/language.store";


@Component({
    selector: 'scrm-squire-detail',
    templateUrl: './squire.component.html',
    styleUrls: [],
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class SquireDetailFieldComponent extends BaseFieldComponent implements OnDestroy, AfterViewInit {

    @ViewChild('editorEl') editorEl: ElementRef;
    @ViewChild('editorWrapper') editorWrapper: ElementRef;

    settings: any = {};
    modelEvents = 'change'
    ignoreEvents = "onKeyDown,onKeyPress,onKeyUp,onSelectionChange"
    value: string = '';
    isMobile = signal(false);
    minHeight: WritableSignal<string> = signal('40vh');
    height: WritableSignal<string> = signal('18vh');
    maxHeight: WritableSignal<string> = signal('45vh');
    maxWidth: WritableSignal<string> = signal('100vh');

    protected currentEditorPath: WritableSignal<string> = signal('');
    protected editor: Squire;

    @HostListener('window:resize', ['$event'])
    onResize(): void {
        this.calculateDynamicMaxHeight();
    }

    constructor(
        protected typeFormatter: DataTypeFormatter,
        protected logic: FieldLogicManager,
        protected logicDisplay: FieldLogicDisplayManager,
        protected config: SystemConfigStore,
        protected sanitizer: DomSanitizer,
        protected screenSize: ScreenSizeObserverService,
        protected language: LanguageStore
    ) {
        super(typeFormatter, logic, logicDisplay);
    }

    ngOnInit(): void {
        super.ngOnInit();
        this.subscribeValueChanges();
        this.value = this.getValue();
        this.initSettings();

        this.subs.push(this.screenSize.screenSize$.subscribe((size) => {
            if (size === ScreenSize.XSmall && !this.isMobile()) {
                this.isMobile.set(true);
                this.minHeight.set('39vh');
            } else if (size !== ScreenSize.XSmall && this.isMobile()) {
                this.isMobile.set(false);

                if (this?.settings?.minHeight) {
                    this.minHeight.set(this?.settings?.minHeight);
                } else {
                    this.minHeight.set('40vh');
                }
            }
        }));
    }

    ngAfterViewInit(): void {

        setTimeout(() => {
            this.calculateDynamicMaxHeight();
        }, 400);

    }

    protected setFieldValue(newValue): void {
        this.value = newValue;
        this.field.value = newValue;

        if (this.editor.getHTML() === newValue) {
            return;
        }

        this.editor.setHTML(newValue);
    }

    initSettings(): void {

        let defaultStyle = '';
        let fixedWidthStyle =
            'border:1px solid #ccc;border-radius:3px;background:#f6f6f6;font-family:menlo,consolas,monospace;font-size:90%;';

        const codeStyle = fixedWidthStyle + 'padding:1px 3px;';
        const preStyle = fixedWidthStyle + 'margin:7px 0;padding:7px 10px;white-space:pre-wrap;word-wrap:break-word;overflow-wrap:break-word;';

        const defaults = {
            label: 'MAIL_MESSAGE_BODY',
            tabIndex: 9,
            editorConfig: {
                blockAttributes: null,
                tagAttributes: {
                    blockquote: {
                        type: 'cite',
                    },
                    li: null,
                    pre: {
                        style: preStyle,
                    },
                    code: {
                        style: codeStyle,
                    },
                },
                classNames: {
                    color: 'squire-editor-color',
                    fontFamily: 'squire-editor-font',
                    fontSize: 'squire-editor-size',
                    highlight: 'squire-editor-highlight',
                },
                sanitizeToDOMFragment(html) {
                    return html;
                },
            },
            layout: {
                ...(this.isMobile()
                    ? {
                        minHeight: 270,
                    }
                    : {
                        height: 270,
                    }),
            },
        } as Partial<SquireConfig>;


        const ui = this.config.getConfigValue('ui');
        const systemDefaults = ui?.squire?.detail ?? {};
        const fieldConfig = this?.field?.metadata?.squire?.detail ?? {};
        let settings = {} as any;

        settings = merge(settings, defaults, systemDefaults, fieldConfig);

        this.settings = settings;

        if (this?.settings?.minHeight) {
            this.minHeight.set(this?.settings?.minHeight);
        }

        if (this?.settings?.height) {
            this.height.set(this?.settings?.height);
        }

        if (this?.settings?.maxHeight) {
            this.maxHeight.set(this?.settings?.maxHeight);
        }
    }

    protected calculateDynamicMaxHeight(): void {
        if (!this?.settings?.dynamicHeight) {
            return;
        }

        const ancestorSelector = this?.settings?.dynamicHeightAncestor ?? 'scrm-squire-detail'
        const dynamicHeightAdjustment = parseInt(this?.settings?.dynamicHeightAdjustment ?? 0);
        let containerHeight = '';

        const ancestor = this.findAncestor(this?.editorEl?.nativeElement, ancestorSelector);
        if (ancestor) {
            let offSetHeight = ancestor?.offsetHeight ?? 0;

            if (offSetHeight && dynamicHeightAdjustment) {
                offSetHeight = offSetHeight + dynamicHeightAdjustment;
            }
            containerHeight = (offSetHeight).toString();
        }

        if (containerHeight) {
            containerHeight = containerHeight + 'px';
        } else {
            containerHeight = this?.settings?.height;
        }

        this.maxHeight.set(containerHeight)
        this.height.set(containerHeight)
    }

    protected findAncestor(el: HTMLElement, selector: string) {
        let found = false;
        let iterations = 0;

        while (!found || iterations > 50) {
            el = el?.parentElement ?? null;
            if (!el) {
                found = true;
                break;
            }

            if (el.matches(selector)) {
                found = true;
            }
            iterations++;
        }

        if (!found) {
            el = null;
        }

        return el;
    }


    getValue(): string {
        let value = this.field.value;
        if (value === '' && (this.field.default ?? false)) {
            value = this.field.default;
        }
        return value;
    }

    initIframeEditor(iframe) {
        this.setEditor(iframe.contentWindow.editor);
        iframe.contentWindow.editorContainer.setAttribute("contenteditable", "false");
        this.initHtml();
    }

    setEditor(editor: any) {
        this.editor = editor;
    }

    initHtml() {
        this.editor.setHTML(this?.value ?? '');
    }
}
