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
    computed,
    ElementRef,
    EventEmitter,
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
import {ButtonInterface, ButtonInterfaceMap} from "../../../../common/components/button/button.model";
import {floor} from "mathjs";
import {AnyButtonInterface, DropdownButtonInterface} from "../../../../common/components/button/dropdown-button.model";
import {ColorButton} from "../../../../components/popups/components/color-selector/color-selector.model";
import {isEmail, isURL} from "../../../../common/utils/value-utils";
import {
    ScreenSize,
    ScreenSizeObserverService
} from "../../../../services/ui/screen-size-observer/screen-size-observer.service";
import {MonacoEditorComponent} from "../../../../components/monaco-editor/monaco-editor.component";
import {LanguageStore} from "../../../../store/language/language.store";


@Component({
    selector: 'scrm-squire-edit',
    templateUrl: './squire.component.html',
    styleUrls: [],
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class SquireEditFieldComponent extends BaseFieldComponent implements OnDestroy, AfterViewInit {

    @ViewChild('editorEl') editorEl: ElementRef<HTMLIFrameElement>;
    @ViewChild('editorWrapper') editorWrapper: ElementRef;
    @ViewChild('toolbar') toolbar: ElementRef;
    @ViewChild('monacoEditor') monacoEditor: MonacoEditorComponent;

    settings: any = {};
    availableButtons = {} as ButtonInterfaceMap;
    modelEvents = 'change'
    ignoreEvents = "onKeyDown,onKeyPress,onKeyUp,onSelectionChange"
    value: string = '';
    isMobile = signal(false);
    activeButtonLayout: WritableSignal<Array<DropdownButtonInterface[]>> = signal([]);
    baseButtonLayout: WritableSignal<Array<ButtonInterface[]>> = signal([]);
    collapsedButtons: WritableSignal<ButtonInterface[]> = signal([]);
    collapsedDropdownButton: WritableSignal<ButtonInterface> = signal(null);
    minHeight: WritableSignal<string> = signal('40vh');
    height: WritableSignal<string> = signal('18vh');
    maxHeight: WritableSignal<string> = signal('45vh');
    maxWidth: WritableSignal<string> = signal('100vh');
    styleSignal: WritableSignal<string> = signal('');
    editorMode: WritableSignal<string> = signal('html');

    showPopups: EventEmitter<boolean> = new EventEmitter()

    protected currentEditorPath: WritableSignal<string> = signal('');
    protected editor: Squire;

    @HostListener('window:resize', ['$event'])
    onResize(): void {
        if (this.baseButtonLayout().length) {
            this.calculateActiveButtons();
        }

        this.calculateDynamicMaxHeight();
    }

    @HostListener('window:message', ['$event'])
    onMessage(event) {
        if (event.data !== 'iframe-clicked'){
            return;
        }

        this.editorEl.nativeElement.click();
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
        this.initAvailableButtons();
        this.initButtons();
        this.collapsedDropdownButton.set({
            'icon': 'down_carret',
            klass: 'squire-editor-button squire-editor-collapsed-button btn btn-sm',
        } as ButtonInterface);

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

        if (this.editorMode() === 'code') {
            this.setMonacoEditor(newValue);
        }

        if (this.editor.getHTML() === newValue) {
            return;
        }

        this.editor.setHTML(newValue);
    }

    updateFieldValue(value): void {
        this.setFormControlValue(value);
    }

    setMonacoEditor(value: string): void {
        if ((this.monacoEditor?.initialised ?? false) === false) {
            return;
        }

        if (this.monacoEditor.editor.getValue() === value) {
            return;
        }

        this.monacoEditor.setEditorValue(value);
    }

    initSettings(): void {

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
                toPlainText: (html) => this.toPlainText(html),
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
        const systemDefaults = ui?.squire?.edit ?? {};
        const fieldConfig = this?.field?.metadata?.squire?.edit ?? {};
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

    initAvailableButtons(): void {

        this.availableButtons.html = {
            key: 'html',
            type: 'button',
            icon: 'code-slash',
            titleKey: 'LBL_HTML',
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => {
                if (this.editorMode() === 'html') {
                    this.field.formControl.setValue(this.editor.getHTML());
                    this.editorMode.set('code');
                    return;
                }
                this.field.formControl.setValue(this.monacoEditor.editor.getValue());
                this.editorMode.set('html');
            },
            dynamicClass: computed((): string => {
                const editorMode = this.editorMode();
                return editorMode === 'code' ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.bold = {
            key: 'bold',
            type: 'button',
            icon: 'bold',
            titleKey: 'LBL_BOLD',
            hotkey: 'ctrl+b',
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => {
                if (this.editorMode() === 'code'){
                    return;
                }
                const hasBold = this?.editor?.hasFormat('B');
                if (hasBold) {

                    if (this.styleSignal() === 'non-bold'){
                        this.styleSignal.set('bold');
                    }

                    this?.editor?.removeBold();
                    this.styleSignal.set('non-bold');
                    return;
                }
                this?.editor?.bold();
                this.styleSignal.set('bold');
            },
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                return this?.editor?.hasFormat('B') ? 'active squire-editor-button-active' : '';
            }),
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
        } as ButtonInterface;

        this.availableButtons.italic = {
            key: 'italic',
            type: 'button',
            icon: 'italic',
            titleKey: 'LBL_ITALIC',
            hotkey: 'ctrl+i',
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => {
                if (this.editorMode() === 'code'){
                    return;
                }
                const hasItalic = this?.editor?.hasFormat('I');
                if (hasItalic) {

                    if (this.styleSignal() === 'non-italic'){
                        this.styleSignal.set('italic');
                    }

                    this?.editor?.removeItalic();
                    this.styleSignal.set('non-italic');
                    return;
                }

                this?.editor?.italic();
                this.styleSignal.set('italic');
            },
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                return this?.editor?.hasFormat('I') ? 'active squire-editor-button-active' : '';
            }),
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
        } as ButtonInterface;

        this.availableButtons.underline = {
            key: 'underline',
            type: 'button',
            icon: 'underline',
            titleKey: 'LBL_UNDERLINE',
            hotkey: 'ctrl+u',
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => {
                if (this.editorMode() === 'code'){
                    return;
                }
                const hasUnderline = this?.editor?.hasFormat('U');
                if (hasUnderline) {

                    if (this.styleSignal() === 'non-underline'){
                        this.styleSignal.set('underline');
                    }

                    this?.editor?.removeUnderline();
                    this.styleSignal.set('non-underline');
                    return;
                }

                this?.editor?.underline();
                this.styleSignal.set('underline');
            },
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                return this?.editor?.hasFormat('U') ? 'active squire-editor-button-active' : '';
            }),
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
        } as ButtonInterface;

        this.availableButtons.strikethrough = {
            key: 'strikethrough',
            type: 'button',
            icon: 'strikethrough',
            titleKey: 'LBL_STRIKETHROUGH',
            hotkey: 'ctrl+shift+7',
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => {
                if (this.editorMode() === 'code'){
                    return;
                }
                const hasStrikeThrough = this?.editor?.hasFormat('S');
                if (hasStrikeThrough) {

                    if (this.styleSignal() === 'non-strikethrough'){
                        this.styleSignal.set('strikethrough');
                    }

                    this?.editor?.removeStrikethrough();
                    this.styleSignal.set('non-strikethrough');
                    return;
                }

                this?.editor?.strikethrough();
                this.styleSignal.set('strikethrough');
            },
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                return this?.editor?.hasFormat('S') ? 'active squire-editor-button-active' : '';
            }),
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
        } as ButtonInterface;

        this.availableButtons.font = {
            key: 'font',
            type: 'popup-button-list',
            icon: 'fonts',
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            }),
            showPopup: (): boolean => {
                return this.editorMode() !== 'code';
            },
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            titleKey: 'LBL_FONT_FACE',
            klass: 'squire-editor-button btn btn-sm ',
            items: [
                {
                    key: 'default',
                    labelKey: 'LBL_DEFAULT',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    onClick: () => {
                        this?.editor?.setFontFace(null);
                    },
                } as ButtonInterface,
                {
                    key: 'sans-serif',
                    label: 'Sans Serif',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    style: 'font-family:sans-serif',
                    onClick: () => {
                        this?.editor?.setFontFace('sans-serif');
                    },
                } as ButtonInterface,
                {
                    key: 'serif',
                    label: 'Serif',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    style: 'font-family:serif',
                    onClick: () => {
                        this?.editor?.setFontFace('serif');
                    },
                } as ButtonInterface,
                {
                    key: 'monospace',
                    label: 'Monospace',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    style: 'font-family:monospace',
                    onClick: () => {
                        this?.editor?.setFontFace('monospace');
                    },
                } as ButtonInterface,
            ] as AnyButtonInterface[],
        } as ButtonInterface;

        this.availableButtons.size = {
            key: 'size',
            type: 'popup-button-list',
            icon: 'text-size',
            showPopup: (): boolean => {
                return this.editorMode() !== 'code';
            },
            titleKey: 'LBL_TEXT_SIZE',
            klass: 'squire-editor-button btn btn-sm ',
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            }),
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            items: [
                {
                    key: 'Small',
                    labelKey: 'LBL_SMALL',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    style: 'font-size:x-small',
                    onClick: () => {
                        this?.editor?.setFontSize('x-small');
                    },
                } as ButtonInterface,
                {
                    key: 'normal',
                    labelKey: 'LBL_NORMAL',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    onClick: () => {
                        this?.editor?.setFontSize(null);
                    },
                } as ButtonInterface,
                {
                    key: 'large',
                    labelKey: 'LBL_LARGE',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    style: 'font-size:large',
                    onClick: () => {
                        this?.editor?.setFontSize('large');
                    },
                } as ButtonInterface,
                {
                    key: 'Huge',
                    labelKey: 'LBL_HUGE',
                    klass: 'squire-editor-button squire-editor-list-button btn btn-sm',
                    style: 'font-size:xx-large',
                    onClick: () => {
                        this?.editor?.setFontSize('xx-large');
                    },
                } as ButtonInterface
            ] as AnyButtonInterface[],
        } as ButtonInterface;


        this.availableButtons.textColour = {
            key: 'textColour',
            type: 'color-selector',
            icon: 'text-colour',
            titleKey: 'LBL_TEXT_COLOR',
            klass: 'squire-editor-button btn btn-sm',
            onClick: (color: ColorButton) => {
                this?.editor?.setTextColor(color.color);
            },
            showPopup: (): boolean => {
                return this.editorMode() !== 'code';
            },
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            })
        } as ButtonInterface;

        this.availableButtons.highlight = {
            key: 'highlight',
            type: 'color-selector',
            icon: 'highlighter',
            titleKey: 'LBL_TEXT_HIGHLIGHT',
            onClick: (color: ColorButton) => {
                this?.editor?.setHighlightColor(color.color);
            },
            klass: 'squire-editor-button btn btn-sm ',
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled ';
                }
            }),
            showPopup: (): boolean => {
                return this.editorMode() !== 'code';
            },
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
        } as ButtonInterface;

        const insertLink = {
            key: 'insertLink',
            type: 'insert-link',
            dynamicIcon: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                return this?.editor?.hasFormat('A') ? 'unlink-45deg' : 'link-45deg';
            }),
            titleKey: 'LBL_LINK',
            klass: 'squire-editor-button btn btn-sm',
            hotkey: 'ctrl+k',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                return this?.editor?.hasFormat('A') ? 'active squire-editor-button-active' : '';
            }),
            metadata: {
                openStatusEventEmitter: new EventEmitter(),
                displayButton: signal(false),
                linkURL: '',
                linkEventEmitter: new EventEmitter()
            },
        } as DropdownButtonInterface;

        insertLink.onClick = () => {
            if (this.editorMode() === 'code'){
                return;
            }
            const isLink = this?.editor?.hasFormat('A');
            if (isLink) {

                if (this.styleSignal() === 'non-link'){
                    this.styleSignal.set('link');
                }

                this?.editor?.removeLink();
                this.styleSignal.set('non-link');
                return;
            }

            const selectedText = this.editor?.getSelectedText() ?? '';
            if (isURL(selectedText.trim())) {
                insertLink.metadata.linkURL = selectedText;
            } else {
                insertLink.metadata.linkURL = '';
            }

            insertLink.metadata.openStatusEventEmitter.emit(true);
            insertLink.metadata.linkEventEmitter.emit(insertLink.metadata.linkURL);
        };

        insertLink.items = [
            {
                labelKey: 'LBL_APPLY',
                titleKey: 'LBL_APPLY',
                klass: 'btn btn-sm btn-main',
                onClick: (linkValue) => {
                    if (isEmail(linkValue)) {
                        linkValue = 'mailto:' + linkValue;
                    }

                    if (!linkValue.includes('https://') && !linkValue.includes('http://') && linkValue.includes('.')) {
                        linkValue = 'http://' + linkValue;
                    }

                    this?.editor?.makeLink(linkValue, {title: linkValue});
                    this.styleSignal.set('link');

                    insertLink.metadata.openStatusEventEmitter.emit(false);
                },
            } as ButtonInterface,
            {
                labelKey: 'LBL_CANCEL',
                titleKey: 'LBL_CANCEL',
                klass: 'btn btn-sm btn-outline-main',
                onClick: () => {
                    insertLink.metadata.openStatusEventEmitter.emit(false);
                },
            } as ButtonInterface
        ]

        this.availableButtons.insertLink = insertLink;

        this.availableButtons.unorderedList = {
            key: 'unorderedList',
            type: 'button',
            icon: 'list-ul',
            titleKey: 'LBL_UNORDERED_LIST',
            klass: 'squire-editor-button btn btn-sm ',
            hotkey: 'ctrl+shift+8',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            onClick: () => {
                if (this.editorMode() === 'code'){
                    return;
                }
                const isUL = this?.editor?.hasFormat('UL');
                if (isUL) {

                    if (this.styleSignal() === 'non-unordered-list'){
                        this.styleSignal.set('unordered-list');
                    }

                    this?.editor?.removeList();
                    this.styleSignal.set('non-unordered-list');
                    return;
                }

                this?.editor?.makeUnorderedList();
                this.styleSignal.set('unordered-list');
            },
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                return this?.editor?.hasFormat('UL') ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.orderedList = {
            key: 'orderedList',
            type: 'button',
            icon: 'list-ol',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            titleKey: 'LBL_ORDERED_LIST',
            klass: 'squire-editor-button btn btn-sm ',
            hotkey: 'ctrl+shift+9',
            onClick: () => {
                if (this.editorMode() === 'code'){
                    return;
                }
                const isOL = this?.editor?.hasFormat('OL');
                if (isOL) {

                    if (this.styleSignal() === 'non-ordered-list'){
                        this.styleSignal.set('ordered-list');
                    }

                    this?.editor?.removeList();
                    this.styleSignal.set('non-ordered-list');
                    return;
                }

                this?.editor?.makeOrderedList();
                this.styleSignal.set('ordered-list');
            },
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                const trigger = this.styleSignal();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                return this?.editor?.hasFormat('OL') ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.indentMore = {
            key: 'indentMore',
            type: 'button',
            icon: 'text-indent-left',
            titleKey: 'LBL_TEXT_INDENT_LEFT',
            klass: 'squire-editor-button btn btn-sm ',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            }),
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.increaseListLevel() : '',
        } as ButtonInterface;

        this.availableButtons.indentLess = {
            key: 'indentLess',
            type: 'button',
            icon: 'text-indent-right',
            titleKey: 'LBL_TEXT_INDENT_RIGHT',
            klass: 'squire-editor-button btn btn-sm ',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            }),
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.decreaseListLevel() : '',
        } as ButtonInterface;

        this.availableButtons.alignLeft = {
            key: 'alignLeft',
            type: 'button',
            icon: 'text-left',
            titleKey: 'LBL_ALIGN_LEFT',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.setTextAlignment('left') : '',
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                const alignment = this.getTextAlignment(path);
                return alignment === 'left' ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.alignCenter = {
            key: 'alignCenter',
            type: 'button',
            icon: 'text-center',
            titleKey: 'LBL_ALIGN_CENTER',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.setTextAlignment('center') : '',
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                const alignment = this.getTextAlignment(path);
                return alignment === 'center' ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.alignRight = {
            key: 'alignRight',
            type: 'button',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            icon: 'text-right',
            titleKey: 'LBL_ALIGN_RIGHT',
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.setTextAlignment('right') : '',
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                const alignment = this.getTextAlignment(path);
                return alignment === 'right' ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.justify = {
            key: 'justify',
            type: 'button',
            icon: 'justify',
            titleKey: 'LBL_JUSTIFY',
            klass: 'squire-editor-button btn btn-sm ',
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.setTextAlignment('justify') : '',
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                const alignment = this.getTextAlignment(path);
                return alignment === 'justify' ? 'active squire-editor-button-active' : '';
            }),
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            })
        } as ButtonInterface;

        this.availableButtons.quote = {
            key: 'quote',
            type: 'button',
            icon: 'quote',
            titleKey: 'LBL_QUOTE',
            klass: 'squire-editor-button btn btn-sm ',
            hotkey: 'ctrl+]',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.increaseQuoteLevel() : '',
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            })
        } as ButtonInterface;

        this.availableButtons.unquote = {
            key: 'unquote',
            type: 'button',
            icon: 'unquote',
            titleKey: 'LBL_UNQUOTE',
            klass: 'squire-editor-button btn btn-sm',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            hotkey: 'ctrl+[',
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.decreaseQuoteLevel() : '',
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            })
        } as ButtonInterface;


        this.availableButtons.leftToRight = {
            key: 'leftToRight',
            type: 'button',
            icon: 'text-left-to-right',
            titleKey: 'LBL_TEXT_LEFT_TO_RIGHT',
            klass: 'squire-editor-button btn btn-sm',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.setTextDirection('ltr') : '',
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                const direction = this.getTextDirection(path);
                return direction === 'ltr' ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.rightToLeft = {
            key: 'rightToLeft',
            type: 'button',
            icon: 'text-right-to-left',
            titleKey: 'LBL_TEXT_RIGHT_TO_LEFT',
            klass: 'squire-editor-button btn btn-sm',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.setTextAlignment('rtl') : '',
            dynamicClass: computed((): string => {
                const path = this.currentEditorPath();
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
                const direction = this.getTextDirection(path);
                return direction === 'rtl' ? 'active squire-editor-button-active' : '';
            })
        } as ButtonInterface;

        this.availableButtons.clearFormatting = {
            key: 'clearFormatting',
            type: 'button',
            icon: 'clear-formatting',
            titleKey: 'LBL_CLEAR_FORMATTING',
            disabled: computed((): boolean => {
                return this.editorMode() === 'code';
            }),
            klass: 'squire-editor-button btn btn-sm',
            onClick: () => this.editorMode() !== 'code' ? this?.editor?.removeAllFormatting() : '',
            dynamicClass: computed((): string => {
                if (this.editorMode() === 'code'){
                    return 'disabled';
                }
            })
        } as ButtonInterface;

        this.availableButtons.injectUnsubscribe = {
            key: 'injectUnsubscribe',
            type: 'button',
            icon: 'unsubscribe',
            titleKey: 'LBL_INJECT_UNSUBSCRIBE',
            klass: 'squire-editor-button btn btn-sm',
            onClick: () => {

                const unsubscribeLabel = this.language.getFieldLabel('LBL_UNSUBSCRIBE') || 'Unsubscribe';

                if (this.editorMode() === 'code') {
                    this.monacoEditor.editor.trigger('keyboard', 'type',
                        {text: '<a title="' + '{{ unsubscribe_link }}' + '" href="{{ unsubscribe_link }}">' + unsubscribeLabel + '</a>'}
                    );
                    return;
                }

                if (this.editor.getSelectedText() === '') {
                    this.editor.insertHTML('<a title="' + '{{ unsubscribe_link }}' + '" href="{{ unsubscribe_link }}">' + unsubscribeLabel + '</a>');
                    return;
                }

                const selectedText = this.editor.getSelectedText();
                this.editor.makeLink('{{ unsubscribe_link }}', {title: '{{ unsubscribe_link }}'});
            },
        } as ButtonInterface;
    }


    initButtons(): void {
        let buttonLayout = this.getDefaultButtonLayout();

        if (this.settings?.buttonLayout && this.settings?.buttonLayout.length) {
            buttonLayout = [...this.settings?.buttonLayout];
        }

        const buttonsGroups: ButtonInterface[][] = [];

        buttonLayout.forEach((buttonGroup: string[]) => {
            const group: ButtonInterface[] = [];
            buttonGroup.forEach((buttonKey: string) => {
                if (this.availableButtons[buttonKey]) {
                    group.push(this.availableButtons[buttonKey]);
                }
            });

            buttonsGroups.push(group);
        });

        this.baseButtonLayout.set(buttonsGroups);
    }

    private getDefaultButtonLayout(): string[][] {
        return [
            [
                'bold',
                'italic',
                'underline',
                'strikethrough',
            ],
            [
                'font',
                'size',
            ],
            [
                'textColour',
                'highlight',
            ],
            [
                'insertLink',
            ],
            [
                'unorderedList',
                'orderedList',
                'indentMore',
                'indentLess',
            ],
            [
                'alignLeft',
                'alignCenter',
                'alignRight',
                'justify',
            ],
            [
                'quote',
                'unquote',
            ],
            [
                'clearFormatting',
            ],
            [
                'html'
            ]
        ] as string[][];
    }

    protected calculateActiveButtons(): void {
        const totalCollapsed = this.collapsedButtons().length;
        const totalExpandedActions = this.baseButtonLayout().reduce((total, buttonGroup) => {
            return total + buttonGroup.length;
        }, 0);

        const limitConfig = this?.field?.metadata?.squire?.edit?.limit ?? {};

        const dynamicBreakpoint = this.calculateDynamicBreakpoint(limitConfig, totalCollapsed, totalExpandedActions);

        if (totalExpandedActions > dynamicBreakpoint) {
            const activeLayout: Array<ButtonInterface[]> = [];
            let count = 0;
            let collapsedButtons: ButtonInterface[] = [];
            this.baseButtonLayout().forEach((buttonGroup) => {

                if (count > dynamicBreakpoint) {
                    collapsedButtons = collapsedButtons.concat(buttonGroup);
                    return;
                }

                const activeGroup = []
                buttonGroup.forEach((button, index) => {
                    if (count < dynamicBreakpoint) {
                        activeGroup.push(button);
                        count++;
                        return
                    }

                    collapsedButtons.push(button);
                });

                if (activeGroup.length) {
                    activeLayout.push(activeGroup);
                }
            });
            this.activeButtonLayout.set(activeLayout);
            this.collapsedButtons.set(collapsedButtons);
        } else {
            this.activeButtonLayout.set(this.baseButtonLayout());
            this.collapsedButtons.set([]);
        }
    }

    protected calculateDynamicBreakpoint(limitConfig, totalCollapsed: number, totalExpandedActions: number): number {
        let buttonMax = 30;

        if (limitConfig?.dynamicBreakpoint?.buttonMax) {
            buttonMax = limitConfig?.dynamicBreakpoint?.buttonMax;
        }

        let dropdownWidth = 40;
        if (limitConfig?.dynamicBreakpoint?.dropdownMax) {
            dropdownWidth = limitConfig?.dynamicBreakpoint?.dropdownMax;
        }

        let containerWidth = this?.toolbar?.nativeElement?.parentElement?.parentElement?.offsetWidth ?? 560;

        if (!containerWidth || containerWidth < buttonMax) {
            return 6;
        }
        containerWidth = containerWidth - 10;


        const fitting = floor(containerWidth / buttonMax);
        const fittingWithDropdown = floor((containerWidth - dropdownWidth) / buttonMax);

        if (totalCollapsed) {
            return fittingWithDropdown;
        }

        if (totalExpandedActions <= fitting) {
            return fitting;
        }

        return fittingWithDropdown;
    }

    protected calculateDynamicMaxHeight(): void {
        if (!this?.settings?.dynamicHeight) {
            return;
        }

        const ancestorSelector = this?.settings?.dynamicHeightAncestor ?? 'scrm-squire-edit'
        const dynamicHeightAdjustment = parseInt(this?.settings?.dynamicHeightAdjustment ?? 0);
        let containerHeight = '';

        const ancestor = this.findAncestor(this?.toolbar?.nativeElement, ancestorSelector);
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

    initEditor() {

        this.editor.setHTML(this?.value ?? '');
        this.initEditorListeners();
        this.editor.focus();
    }

    protected initEditorListeners(): void {
        this.editor.addEventListener('input', (e: Event) => {
            this.setFormControlValue(this.editor.getHTML())
        });
        this.editor.addEventListener('pathChange', (e: Event) => {
            e.stopPropagation();
            this.currentEditorPath.set(this.editor.getPath());
        });
        this.editor.addEventListener('click', (e: Event) => {
            window.postMessage('iframe-clicked');
            e.stopImmediatePropagation();
            this.hidePopups();
        })
    }

    hidePopups(): void {
        this.showPopups.emit(false);

        if (this.availableButtons) {
            this.availableButtons.insertLink.metadata.openStatusEventEmitter.emit(false);
        }
    }

    toPlainText(html: any) {
        return html;
    }

    protected getTextAlignment(path: string): string {
        const results = /\.align-(\w+)/.exec(path);

        if (path !== '(selection)') {
            return results ? results[1] : 'left';
        }

        let alignment = '';

        this.editor.forEachBlock(
            (block) => {
                const align = block.style.textAlign || 'left';

                if (!alignment) {
                    alignment = align;
                    return false;
                }

                const isSame = alignment === align;
                if (isSame) {
                    alignment = '';
                    return false;
                }

                alignment = '';
                return true;
            },
            false
        );

        return alignment;
    }

    protected getTextDirection(path: string): string {
        const results = /\[dir=(\w+)\]/.exec(path);

        if (path !== '(selection)') {
            return results ? results[1] : 'ltr';
        }

        let direction = '';

        this.editor.forEachBlock(
            (block) => {
                const dir = block.dir || 'ltr';

                if (!direction) {
                    direction = dir;
                    return false;
                }

                const isSame = direction === dir;
                if (isSame) {
                    direction = '';
                    return false;
                }

                direction = '';
                return true;
            },
            false
        );

        return direction;
    }

    initIframeEditor(iframe) {
        this.setEditor(iframe.contentWindow.editor);
        this.initEditor();
        iframe.contentWindow.addEventListener('click', (event: Event) => {
            const selection = iframe.contentWindow.getSelection();
            window.postMessage('iframe-clicked');

            if (!selection?.isCollapsed || selection?.type !== 'None') {
                return;
            }

            this.editor.focus();
            this.editor.moveCursorToEnd();
            this.hidePopups();
        });
        this.initHtml()
        this.calculateActiveButtons();
    }

    setEditor(editor: any) {
        this.editor = editor;
    }

    initHtml() {
        this.editor.setHTML(this?.value ?? '');
    }
}
