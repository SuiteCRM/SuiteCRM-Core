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
    Component,
    ElementRef,
    EventEmitter, HostListener,
    Input,
    OnChanges, OnDestroy,
    Output, signal, SimpleChanges,
    ViewChild, WritableSignal
} from "@angular/core";
import {UploadedFile} from "../uploaded-file/uploaded-file.model";
import {SystemConfigStore} from "../../store/system-config/system-config.store";
import {Subscription} from "rxjs";
import {RecordViewStore} from "../../views/record/store/record-view/record-view.store";

@Component({
    selector: 'scrm-multiple-uploaded-files',
    templateUrl: './multiple-uploaded-file.component.html',
    styles: [],
})
export class MultipleUploadedFileComponent implements OnChanges, AfterViewInit, OnDestroy {

    maxPerRow: number;
    maxPerRowWidth: number;
    chunkedArray: WritableSignal<any[][]> = signal([]);

    popover: WritableSignal<HTMLElement> = signal({} as HTMLElement);
    loading: WritableSignal<boolean> = signal(true);
    protected subs: Subscription[] = [];

    @Input() files: UploadedFile[] = [];
    @Input() allowClear: boolean = true;
    @Input() compact: boolean = false;
    @Input() chunks: number;
    @Input() breakpoint: number;
    @Input() maxTextWidth: string;
    @Input() wrapper: HTMLElement = {} as HTMLElement;
    @Input() ancestorSelector: string;
    @Input() minWidth: string = '185px';
    @Input() popoverMinWidth: string = '355px';
    @Input() popoverMaxTextLength: string = '250px';
    @Input() popoverTarget: HTMLElement;
    @Input() clickable: boolean = false;
    @Input() displayType: string = 'default';
    @ViewChild('popoverDefaultTarget') popoverDefaultTarget: ElementRef;
    @Output('clear') clear: EventEmitter<UploadedFile> = new EventEmitter<UploadedFile>();

    constructor(
        public recordViewStore: RecordViewStore,
        protected systemConfigStore: SystemConfigStore,
    ) {
    }

    @HostListener('window:resize', ['$event'])
    onResize(): void {
        this.recalculateWithLoading();
    }

    calculateDynamicMaxPerRow(): void {

        const ancestorSelector = this.ancestorSelector ?? 'scrm-attachments-edit';
        const ancestor = this.findAncestor(this.wrapper, ancestorSelector);

        if (!ancestor) {
            return;
        }

        const offset = ancestor.offsetWidth;
        const parentWidth = offset - 115;

        if (parentWidth > 0 || !this.maxPerRowWidth) {
            this.maxPerRowWidth = parentWidth;
        }

        if (this.maxPerRowWidth < 0) {
            return;
        }

        const itemMinWidth = parseInt(this.minWidth, 10);
        const gap = 10;
        const calculatedChunks = Math.floor((this.maxPerRowWidth - gap) / (itemMinWidth + gap));
        const maxCalculated = Math.max(1, calculatedChunks);
        const maxAllowed = this.chunks ?? 100;

        this.maxPerRow = Math.min(maxCalculated, maxAllowed);

        const visibleFiles = this.files.slice(0, this.breakpoint);
        this.chunkedArray.set(this.chunkArray(visibleFiles, this.maxPerRow));
    }

    ngAfterViewInit() {
        this.subs.push(this.recordViewStore.showSidebarWidgets$.subscribe((value) => {
            this.recalculateWithLoading();
        }));

        setTimeout(() => {
            this.loading.set(true);
        }, 200);
        setTimeout(() => {
            this.calculateDynamicMaxPerRow();

            this.setPopover();
            this.loading.set(false);
        }, 500)
    }

    ngOnChanges(changes: SimpleChanges): void {
        this.calculateDynamicMaxPerRow();
        setTimeout(() => {
            this.setPopover();
            this.loading.set(false);
        }, 200)
    }

    setPopover(): void {
        let target = this.popoverDefaultTarget?.nativeElement;

        if (this?.popoverTarget ?? false) {
            target = this.popoverTarget;
        }

        this.popover.set(target);
    }

    chunkArray<T>(arr: T[], chunkSize: number): T[][] {
        if (!arr) return [];
        const out = [];
        for (let i = 0; i < arr.length; i += chunkSize) {
            out.push(arr.slice(i, i + chunkSize));
        }
        return out;
    }

    clearFiles(event): void {
        this.clear.emit(event)
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

    protected recalculateWithLoading(): void {
        this.loading.set(true);
        setTimeout(() => {
            this.calculateDynamicMaxPerRow();
            this.loading.set(false);
        }, 150);
    }

    ngOnDestroy() {
        this.subs.forEach(sub => sub.unsubscribe());
    }

}
