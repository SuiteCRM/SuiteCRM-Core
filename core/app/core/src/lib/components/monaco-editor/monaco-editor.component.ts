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

import {Component, ElementRef, EventEmitter, Input, Output, ViewChild} from '@angular/core';
import {interval, Observable, ReplaySubject} from "rxjs";
import {filter, take} from "rxjs/operators";
import {FormsModule} from "@angular/forms";


@Component({
    selector: 'scrm-monaco-editor',
    standalone: true,
    imports: [
        FormsModule
    ],
    templateUrl: './monaco-editor.component.html',
})
export class MonacoEditorComponent {

    @Input() content: string = '';
    editor?: any;
    @Output('valueChange') valueEvent = new EventEmitter<string>();
    initialised: boolean = false;

    @ViewChild('editorContainer', {static: true}) set editorContainer(container: ElementRef) {

        if ((window as any).monaco) {
            setTimeout(() => {
                this.initEditor((window as any).monaco, container);
            }, 0)
            return;
        }

        this.loadMonacoScripts().subscribe((monaco) => {

            if (!(window as any).monaco) {
                (window as any).monaco = monaco;
            }
            this.initEditor(monaco, container);
        });

    }

    setEditorValue(value: string): void {
        this.editor.getModel().setValue(value);
    }

    protected initEditor(monaco, container: ElementRef<any>) {
        this.editor = monaco.editor.create(container.nativeElement, {
            value: this.content ?? '',
            language: 'html',
            theme: 'vs-dark',
            automaticLayout: true,
            "autoIndent": "full",
            "formatOnPaste": true,
            "formatOnType": true
        });

        this.editor.getModel().onDidChangeContent(() => {
            this.valueEvent.emit(this.editor.getModel().getValue());
        });

        setTimeout(() => {
            this.editor.getAction('editor.action.formatDocument').run();
        }, 250);

        this.initialised = true;
    }

    protected loadMonacoScripts(): Observable<any> {
        const monacoPath = window.location.origin + window.location.pathname + 'dist/mona/vs';

        const loader = new ReplaySubject<any>(1);

        if ((window as any).monacoEditorLoading) {
            interval(200)
                .pipe(filter((_) => (window as any).monaco), take(1))
                .subscribe((_) => {
                    loader.next((window as any).monaco);
                    loader.complete();
                });
            return loader;
        }

        (window as any).monacoEditorLoading = true;

        const script = document.createElement('script');
        script.src = monacoPath + '/loader.js';
        script.type = 'text/javascript';
        script.async = true;

        script.onload = () => {
            (window as any).require.config({paths: {vs: monacoPath}});
            (window as any).require(['vs/editor/editor.main'], () => {
                loader.next((window as any).monaco);
                loader.complete();
            });
        };

        document.body.appendChild(script);

        return loader;
    }
}
