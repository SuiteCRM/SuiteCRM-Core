/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

import {ChangeDetectorRef, Component, OnDestroy, OnInit} from '@angular/core';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';

interface LogPosition {
    file: string;
    pos: number;
}

type LogPositions = { [source: string]: LogPosition };

interface LogSourceInfo {
    key: string;
    label: string;
    main: boolean;
    size: number;
    modified: string;
    end: LogPosition;
}

interface LogPart {
    key: string;
    levels: string[];
    sources: LogSourceInfo[];
}

interface LogViewerData {
    available: boolean;
    parts: LogPart[];
    labels: { [key: string]: string };
}

interface LogEntry {
    source: string;
    date: string;
    level: string;
    channel: string;
    pid: string;
    user: string;
    userName: string;
    head: string;
    body: string;
    truncated: boolean;
}

interface LogReadData {
    entries: LogEntry[];
    positions: LogPositions;
    ends: LogPositions;
    more: boolean;
    scanned: number;
    parts?: LogPart[];
}

/**
 * A line of the table: an entry of a log, with its message and what opens under it (the
 * context of a Symfony line, the following lines of a legacy entry).
 */
interface LogRow {
    entry: LogEntry;
    date: string;
    file: string;
    /** The name of the user of a legacy line, its id when the user is not known. */
    user: string;
    severity: string;
    message: string;
    detail: string;
    open: boolean;
}

interface LogFilters {
    level: string;
    search: string;
    pattern: RegExp;
}

/**
 * A part of the page (Symfony logs, legacy logs): each one keeps its filters, its lines and its
 * mark point.
 */
interface LogTab {
    part: LogPart;
    /** The log read, '' for all the logs of the part as one. */
    source: string;
    level: string;
    search: string;
    regex: boolean;
    /** The filters of the lines displayed (the ones of the form once submitted). */
    filters: LogFilters;
    rows: LogRow[];
    /** Where the last request stopped in every log. */
    positions: LogPositions;
    /** The ends of the logs when the lines displayed were read. */
    ends: LogPositions;
    mark: LogPositions;
    marked: string;
    /** Whether the lines displayed are the ones written after the mark point. */
    fromMark: boolean;
    more: boolean;
    loaded: boolean;
}

interface LogReading {
    tab: LogTab;
    added: number;
    scanned: number;
    steps: number;
}

/**
 * The log viewer of the administration (View Log of the System Settings), as a
 * SuiteCRM 8 view, in two parts: the Symfony logs, read as one by date, and the legacy logs. The
 * lines are read from the newest, a part at a time: a request reads a bounded part of the files,
 * and the page repeats it until it has a page of lines (level and text filters) or is stopped.
 *
 * As the legacy viewer: All, Mark Point, Refresh From Mark and Next, and the search with its
 * regular expression option, applied here on the lines received.
 *
 * Route configurator/LogView. Data from the `log-viewer` process (LogViewerProcessHandler).
 */
@Component({
    selector: 'scrm-log-viewer',
    templateUrl: './log-viewer.component.html',
    styleUrls: []
})
export class LogViewerComponent implements OnInit, OnDestroy {

    static readonly PROCESS = 'log-viewer';
    static readonly MARKS_STORAGE = 'logViewerMarks';

    /** The lines added by a click (first display, older lines). */
    static readonly PAGE = 200;
    /** The lines asked by a request when they are filtered here (LogJournal::MAX_ENTRIES). */
    static readonly MAX_ENTRIES = 500;
    /** The requests chained to get a page of lines, after which the page waits for a click. */
    static readonly MAX_STEPS = 60;

    protected static readonly SEVERITIES: { [level: string]: string } = {
        DEBUG: 'debug',
        INFO: 'info',
        NOTICE: 'warning',
        WARN: 'warning',
        WARNING: 'warning',
        DEPRECATED: 'warning',
        ERROR: 'error',
        FATAL: 'error',
        CRITICAL: 'error',
        ALERT: 'error',
        SECURITY: 'error',
        EMERGENCY: 'error'
    };

    data: LogViewerData = null;
    tabs: LogTab[] = [];
    tab: LogTab = null;
    loading = false;
    reading: LogReading = null;

    protected sub: Subscription;
    protected readSub: Subscription;

    constructor(
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.loading = true;
        this.sub = this.submit({action: 'sources'}, (data: LogViewerData) => {
            this.loading = false;
            this.data = data;
            const marks = this.storedMarks();
            this.tabs = (data.available ? data.parts : []).map(part => ({
                part,
                source: this.defaultSource(part),
                level: '',
                search: '',
                regex: false,
                filters: {level: '', search: '', pattern: null},
                rows: [],
                positions: null,
                ends: null,
                mark: marks[part.key]?.mark ?? null,
                marked: marks[part.key]?.marked ?? '',
                fromMark: false,
                more: false,
                loaded: false
            }));
            this.select(this.tabs.find(tab => tab.part.sources.length) ?? this.tabs[0]);
        }, () => this.loading = false);
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
        this.readSub?.unsubscribe();
    }

    /**
     * A label of the page (Configurator strings sent by the process), or of the application.
     */
    label(key: string): string {
        return this.data?.labels?.[key] || this.language.getAppString(key) || (this.data ? key : '');
    }

    /**
     * The label of a field, without the colon some legacy labels end with.
     */
    fieldLabel(key: string): string {
        return this.label(key).replace(/\s*:\s*$/, '');
    }

    /**
     * The label of the buttons of the legacy viewer, without their arrows ("Next>>").
     */
    buttonLabel(key: string): string {
        return this.label(key).replace(/\s*[<>]+\s*$/, '');
    }

    partLabel(tab: LogTab): string {
        return this.label('LBL_LOGVIEW_' + tab.part.key.toUpperCase());
    }

    /**
     * A size in bytes, as shown.
     */
    size(bytes: number): string {
        if (bytes < 1024) {
            return `${bytes} B`;
        }
        if (bytes < 1048576) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }

        return `${(bytes / 1048576).toFixed(1)} MB`;
    }

    /**
     * The options of the logs are kept when the logs are read again (their sizes change): the
     * select would lose its value with new ones.
     */
    trackSource(index: number, source: LogSourceInfo): string {
        return source.key;
    }

    get showFile(): boolean {
        return !this.tab.source && this.tab.part.sources.length > 1;
    }

    get showChannel(): boolean {
        return this.tab.rows.some(row => row.entry.channel !== '');
    }

    /**
     * The lines of the legacy logger have a user (the one of the request).
     */
    get showUser(): boolean {
        return this.tab.rows.some(row => row.entry.pid !== '');
    }

    get filtered(): boolean {
        return !!(this.tab.level || this.tab.search || this.tab.filters.level || this.tab.filters.search);
    }

    select(tab: LogTab): void {
        if (this.reading) {
            this.stop();
        }
        this.tab = tab ?? null;
        if (tab && !tab.loaded && tab.part.sources.length) {
            this.refresh();
        }
        this.cdr.detectChanges();
    }

    /**
     * Reads the lines again from the end of the logs, with the filters of the form.
     */
    refresh(): void {
        const tab = this.tab;
        let pattern: RegExp = null;
        const search = tab.search.trim();
        if (tab.regex && search) {
            try {
                pattern = new RegExp(search, 'i');
            } catch (error) {
                this.message.addDangerMessage(this.label('LBL_LOGVIEW_REG_EXP_ERROR'));
                return;
            }
        }

        tab.filters = {level: tab.level, search, pattern};
        tab.rows = [];
        tab.positions = null;
        tab.more = false;
        tab.loaded = true;
        this.read(tab);
    }

    clearFilters(): void {
        this.tab.level = '';
        this.tab.search = '';
        this.tab.regex = false;
        this.refresh();
    }

    /**
     * All the lines, from the end of the logs (All of the legacy viewer).
     */
    showAll(): void {
        this.tab.fromMark = false;
        this.refresh();
    }

    /**
     * Sets the mark point at the end of the logs of the part: Refresh From Mark then shows what
     * was written since.
     */
    setMark(): void {
        const tab = this.tab;
        this.sub?.unsubscribe();
        this.sub = this.submit({action: 'sources'}, (data: LogViewerData) => {
            const part = (data.parts ?? []).find(item => item.key === tab.part.key);
            if (!part) {
                return;
            }
            this.updatePart(tab, part);
            tab.mark = {};
            part.sources.forEach(source => tab.mark[source.key] = source.end);
            tab.marked = this.time();
            this.storeMarks();
        });
    }

    /**
     * The lines written since the mark point (all the lines when none was set, as the legacy
     * viewer).
     */
    showFromMark(): void {
        this.tab.fromMark = true;
        this.refresh();
    }

    /**
     * The lines written since the ones displayed were read: the mark point moves to the end of
     * the logs at that time (Next of the legacy viewer).
     */
    showNext(): void {
        const tab = this.tab;
        if (tab.ends) {
            tab.mark = tab.ends;
            tab.marked = this.time();
            this.storeMarks();
        }
        this.showFromMark();
    }

    /**
     * Older lines, from where the last request stopped.
     */
    older(): void {
        if (this.tab.more && !this.reading) {
            this.read(this.tab);
        }
    }

    stop(): void {
        this.readSub?.unsubscribe();
        this.reading = null;
        this.cdr.detectChanges();
    }

    /**
     * Opens or closes a line, but when its text is being selected.
     */
    toggle(row: LogRow): void {
        if (!window.getSelection()?.toString()) {
            row.open = !row.open;
        }
    }

    /**
     * The lines displayed as a file, in the order of the logs (the oldest first).
     */
    download(): void {
        const tab = this.tab;
        const text = tab.rows.slice().reverse().map(row => row.entry.head + row.entry.body).join('\n') + '\n';
        const now = new Date();
        const stamp = [now.getFullYear(), now.getMonth() + 1, now.getDate(), '-', now.getHours(), now.getMinutes(), now.getSeconds()]
            .map(part => typeof part === 'number' ? `${part}`.padStart(2, '0') : part).join('');
        const url = URL.createObjectURL(new Blob([text], {type: 'text/plain;charset=utf-8'}));
        const link = document.createElement('a');
        link.href = url;
        link.download = `${tab.part.key}-logs-${stamp}.log`;
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    /**
     * Reads a page of lines: requests are chained from the position of the one before, until
     * the page is complete, the logs are read, or the reading is stopped.
     */
    protected read(tab: LogTab): void {
        this.readSub?.unsubscribe();
        this.reading = {tab, added: 0, scanned: 0, steps: 0};
        this.step();
    }

    protected step(): void {
        const reading = this.reading;
        const tab = reading.tab;
        const first = tab.positions === null;
        const sources = tab.part.sources.filter(source => !tab.source || source.key === tab.source).map(source => source.key);

        this.readSub = this.submit({
            action: 'read',
            part: tab.part.key,
            sources,
            positions: tab.positions ?? {},
            floors: tab.fromMark && tab.mark ? tab.mark : {},
            level: tab.filters.level,
            // The regular expression is applied here, on the lines received.
            search: tab.filters.pattern ? '' : tab.filters.search,
            limit: tab.filters.pattern ? LogViewerComponent.MAX_ENTRIES : LogViewerComponent.PAGE - reading.added,
            parts: first
        }, (data: LogReadData) => {
            if (this.reading !== reading) {
                return;
            }
            const part = (data.parts ?? []).find(item => item.key === tab.part.key);
            if (part) {
                this.updatePart(tab, part);
            }

            const rows = (data.entries ?? [])
                .filter(entry => !tab.filters.pattern || tab.filters.pattern.test(entry.head + entry.body))
                .map(entry => this.toRow(tab, entry));
            tab.rows = tab.rows.concat(rows);
            tab.positions = data.positions ?? {};
            tab.more = !!data.more;
            if (first) {
                tab.ends = data.ends ?? null;
            }

            reading.added += rows.length;
            reading.scanned += data.scanned ?? 0;
            reading.steps++;
            if (tab.more && reading.added < LogViewerComponent.PAGE && reading.steps < LogViewerComponent.MAX_STEPS) {
                this.step();
            } else {
                this.reading = null;
            }
        }, () => this.reading = null);
    }

    /**
     * The logs of a part read again (sizes, files written since): the log selected stays so
     * while it exists.
     */
    protected updatePart(tab: LogTab, part: LogPart): void {
        tab.part = part;
        const known = part.sources.some(source => source.key === tab.source);
        const all = !tab.source && part.sources.length > 1;
        if (!known && !all) {
            tab.source = this.defaultSource(part);
        }
    }

    /**
     * The log read at first: the main one of the part (the legacy log), else its only log, else
     * all of them as one.
     */
    protected defaultSource(part: LogPart): string {
        const main = part.sources.find(source => source.main) ?? (part.sources.length === 1 ? part.sources[0] : null);

        return main?.key ?? '';
    }

    protected toRow(tab: LogTab, entry: LogEntry): LogRow {
        const end = entry.body.indexOf('\n');
        let message = end < 0 ? entry.body : entry.body.slice(0, end);
        const details = end < 0 ? [] : [entry.body.slice(end + 1)];

        if (entry.channel) {
            // A Monolog line: message, context and extra data.
            const [text, context] = this.splitContext(message);
            message = text;
            if (context) {
                details.unshift(context);
            }
        }

        const source = tab.part.sources.find(item => item.key === entry.source);

        return {
            entry,
            date: entry.date.replace(/^(\d{4}-\d\d-\d\d)T(\d\d:\d\d:\d\d)(\.\d{3})?.*$/, '$1 $2$3'),
            file: (source?.label ?? entry.source).replace(/^.*\//, ''),
            // "-none-": no user (command line, scheduler).
            user: entry.userName || (entry.user === '-none-' ? '' : entry.user),
            severity: LogViewerComponent.SEVERITIES[entry.level.toUpperCase()] ?? '',
            message,
            detail: details.join('\n'),
            open: false
        };
    }

    /**
     * The message of a Monolog line, and the JSON data that end it (context, extra) set out on
     * several lines; the empty ones are left out.
     */
    protected splitContext(line: string): [string, string] {
        const data: string[] = [];
        let message = line;

        for (let index = 0; index < 2; index++) {
            const start = this.trailingJson(message);
            if (start < 0) {
                break;
            }
            const json = message.slice(start + 1);
            message = message.slice(0, start);
            if (json !== '[]' && json !== '{}') {
                data.unshift(JSON.stringify(JSON.parse(json), null, 2));
            }
        }

        return [message, data.join('\n')];
    }

    /**
     * Where the JSON value a text ends with starts (the space before it), -1 when it ends with
     * none.
     */
    protected trailingJson(text: string): number {
        const last = text.slice(-1);
        if (last !== ']' && last !== '}') {
            return -1;
        }

        const open = last === ']' ? ' [' : ' {';
        let from = text.length;
        for (let tries = 0; tries < 40 && from > 0; tries++) {
            const start = text.lastIndexOf(open, from - 1);
            if (start < 0) {
                return -1;
            }
            try {
                JSON.parse(text.slice(start + 1));

                return start;
            } catch (error) {
                from = start;
            }
        }

        return -1;
    }

    protected time(): string {
        return new Date().toLocaleTimeString();
    }

    /**
     * The mark points, kept for the browser tab (the legacy viewer kept its one in the session).
     */
    protected storedMarks(): { [part: string]: { mark: LogPositions; marked: string } } {
        try {
            return JSON.parse(window.sessionStorage.getItem(LogViewerComponent.MARKS_STORAGE) ?? '{}') ?? {};
        } catch (error) {
            return {};
        }
    }

    protected storeMarks(): void {
        const marks: { [part: string]: { mark: LogPositions; marked: string } } = {};
        this.tabs.filter(tab => tab.mark).forEach(tab => marks[tab.part.key] = {mark: tab.mark, marked: tab.marked});
        try {
            window.sessionStorage.setItem(LogViewerComponent.MARKS_STORAGE, JSON.stringify(marks));
        } catch (error) {
            // The mark points are then kept until the page is left.
        }
    }

    protected submit(options: { [key: string]: any }, done: (data: any) => void, failed?: () => void): Subscription {
        this.cdr.detectChanges();

        return this.processService.submit(LogViewerComponent.PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                if (process?.status === 'success' && process?.data) {
                    done(process.data);
                } else {
                    failed?.();
                    this.message.addDangerMessage(this.label(process?.messages?.[0] || 'LBL_ACTION_ERROR'));
                }
                this.cdr.detectChanges();
            },
            error: () => {
                failed?.();
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }
}
