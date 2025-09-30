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
    EventEmitter,
    Input,
    OnChanges,
    OnInit,
    Output, signal, SimpleChanges,
    ViewChild, WritableSignal
} from "@angular/core";
import {UploadedFile} from "../uploaded-file/uploaded-file.model";
import {
    ScreenSize,
    ScreenSizeObserverService
} from "../../services/ui/screen-size-observer/screen-size-observer.service";
import {SystemConfigStore} from "../../store/system-config/system-config.store";
import {BehaviorSubject} from "rxjs";

@Component({
    selector: 'scrm-multiple-uploaded-files',
    templateUrl: './multiple-uploaded-file.component.html',
    styles: [],
})
export class MultipleUploadedFileComponent implements OnChanges, AfterViewInit {

    limitPerRow: number;
    breakpointMax: number;
    chunkedArray: any[][];
    sizeConfig: any = {};
    breakpointConfig: any = {};
    limitSet: boolean = false;

    popover: WritableSignal<HTMLElement> = signal({} as HTMLElement);

    @Input() files: UploadedFile[] = [];
    @Input() allowClear: boolean = true;
    @Input() compact: boolean = false;
    @Input() chunks: number = 3;
    @Input() breakpoint: number = 3;
    @Input() maxTextWidth: string;
    @Input() minWidth: string;
    @Input() popoverMinWidth: string = '355px';
    @Input() popoverMaxTextLength: string = '250px';
    @Input() popoverTarget: HTMLElement = {} as HTMLElement;
    @Input() clickable: boolean = false;
    @Input() ignoreRowLimit: boolean = false;
    @Input() ignoreBreakpointLimit: boolean = false;
    @Input() limitConfigKey: string = 'recordview_attachment_limit';
    @Input() displayType: string = 'default';
    @ViewChild('popoverDefaultTarget') popoverDefaultTarget: ElementRef;
    @Output('clear') clear: EventEmitter<UploadedFile> = new EventEmitter<UploadedFile>();

    protected screen: ScreenSize = ScreenSize.Medium;
    protected screenSizeState: BehaviorSubject<ScreenSize>;

    constructor(
        protected screenSize: ScreenSizeObserverService,
        protected systemConfigStore: SystemConfigStore
    ) {
    }

    ngAfterViewInit() {
        setTimeout(() => {
            this.popover.set(this.popoverTarget);
        }, 300)
    }

    ngOnChanges(changes: SimpleChanges): void {
        this.initLimit();
        this.chunkedArray = this.chunkArray(this.files.slice(0, this.breakpoint), this.chunks);

        if (this.breakpointMax < this.breakpointConfig[ScreenSize.Large]) {
            return;
        }

        this.setPopover();
    }

    setPopover(): void {
        setTimeout(() => {
            if (!this.popoverDefaultTarget) {
                return;
            }

            this.popover.set(this.popoverDefaultTarget.nativeElement);
        }, 300)
    }

    chunkArray<T>(arr: T[], chunkSize: number): T[][] {
        if (!arr) return [];
        const out = [];
        for (let i = 0; i < arr.length; i += chunkSize) {
            out.push(arr.slice(i, i + chunkSize));
        }
        return out;
    }

    protected initLimit() {

        if (this.limitSet) {
            return;
        }

        const limit = this.systemConfigStore.getConfigValue('recordview_attachment_limit');

        this.sizeConfig = limit.row_default_limit;
        this.breakpointConfig = limit.breakpoints_default_limit

        if (this.compact) {
            this.sizeConfig = limit.row_compact_limit;
            this.breakpointConfig = limit.breakpoints_compact_limit
        }

        this.screenSizeState = this.screenSize.screenSize;

        this.limitPerRow = this.sizeConfig[this.screenSizeState.value];
        this.breakpointMax = this.breakpointConfig[this.screenSizeState.value];

        this.screenSize.screenSize$.subscribe((size) => {
            this.limitPerRow = this.sizeConfig[size] || this.limitPerRow;
            this.breakpointMax = this.breakpointConfig[size] || this.breakpointMax;
        })

        this.chunks = this.getChunks();
        this.breakpoint = this.getBreakpoint();

        this.limitSet = true;
    }

    clearFiles(event): void {
        this.clear.emit(event)
    }

    getChunks(): number {
        if (this.ignoreRowLimit){
            return this.chunks;
        }

        if (this.chunks > this.limitPerRow){
            return this.limitPerRow;
        }

        return this.chunks;
    }

    getBreakpoint(): number {
        if (this.ignoreBreakpointLimit){
            return this.breakpoint;
        }

        if (this.breakpoint > this.breakpointMax){
            return this.breakpointMax;
        }

        return this.breakpoint;
    }
}
