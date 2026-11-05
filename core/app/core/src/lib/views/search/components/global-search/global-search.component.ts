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

import {ChangeDetectorRef, Component, ElementRef, OnDestroy, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {forkJoin, Observable, of, Subject, Subscription} from 'rxjs';
import {catchError, map, switchMap, take, takeUntil} from 'rxjs/operators';
import {ColumnDefinition} from '../../../../common/metadata/list.metadata.model';
import {Record} from '../../../../common/record/record.model';
import {LanguageStore} from '../../../../store/language/language.store';
import {MetadataStore} from '../../../../store/metadata/metadata.store.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {FieldManager} from '../../../../services/record/field/field.manager';

/** A module the user can search in. */
export interface GlobalSearchModuleOption {
    module: string;
    label: string;
    selected: boolean;
}

/** The results of one module, as returned by the global-search process. */
export interface GlobalSearchSection {
    module: string;
    count: number;
    records: Record[];
}

/** Data of the global-search process. */
export interface GlobalSearchData {
    modules: GlobalSearchModuleOption[];
    counts: { [module: string]: number };
    module: string;
    offset: number;
    pageSize: number;
    sections: GlobalSearchSection[];
}

interface GlobalSearchSectionView {
    module: string;
    label: string;
    count: number;
    more: boolean;
    columns: ColumnDefinition[];
    records: Record[];
}

/**
 * Global search results view, opened by the navbar search
 * (/home/search and /home/unified-search).
 *
 * The search runs in the backend global-search process, with the search engine set
 * in the configuration. The view shows a tab per module with its result count: the
 * first results of every module, or the results of one module with its own paging.
 * Each module shows the default columns of its list view, rendered with the list
 * field components.
 *
 * The search term, module and page are query params (query_string, module, offset).
 */
@Component({
    selector: 'scrm-global-search',
    templateUrl: './global-search.component.html',
    styleUrls: [],
})
export class GlobalSearchComponent implements OnInit, OnDestroy {

    searchTerm = '';
    input = '';
    module = '';
    offset = 0;

    loading = false;
    error = false;
    searched = false;

    modules: GlobalSearchModuleOption[] = [];
    modulesOpen = false;
    modulesWarning = false;

    tabs: { module: string, label: string, count: number }[] = [];
    total = 0;
    emptyModules = '';
    sections: GlobalSearchSectionView[] = [];
    pager: { from: number, to: number, total: number, previous: boolean, next: boolean } | null = null;

    protected pageSize = 20;
    protected words: string[] = [];
    // Modules picked in the modules menu, sent with the search that follows the navigation.
    protected pendingModules: string[] | null = null;
    protected searchSub: Subscription | null = null;
    protected readonly destroy$ = new Subject<void>();

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected processService: ProcessService,
        protected metadataStore: MetadataStore,
        protected fieldManager: FieldManager,
        protected language: LanguageStore,
        protected elementRef: ElementRef<HTMLElement>,
        protected changeDetector: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.route.queryParamMap.pipe(takeUntil(this.destroy$)).subscribe(params => {
            this.searchTerm = (params.get('query_string') ?? '').trim();
            this.module = params.get('module') ?? '';
            this.offset = Math.max(0, parseInt(params.get('offset') ?? '0', 10) || 0);
            this.input = this.searchTerm;
            this.search();
        });
    }

    ngOnDestroy(): void {
        this.searchSub?.unsubscribe();
        this.destroy$.next();
        this.destroy$.complete();
    }

    get selectedModulesCount(): number {
        return this.modules.filter(option => option.selected).length;
    }

    submit(): void {
        const searchTerm = this.input.trim();
        if (searchTerm === this.searchTerm && this.module === '' && this.offset === 0) {
            this.search();
            return;
        }
        this.navigate(searchTerm, '', 0);
    }

    openModule(module: string): void {
        this.navigate(this.searchTerm, module, 0);
    }

    changePage(step: number): void {
        this.navigate(this.searchTerm, this.module, Math.max(0, this.offset + step * this.pageSize));
    }

    toggleModules(): void {
        this.modulesOpen = !this.modulesOpen;
        this.modulesWarning = false;
    }

    selectAllModules(selected: boolean): void {
        this.modules.forEach(option => option.selected = selected);
        this.modulesWarning = false;
    }

    /** Searches in the checked modules; the backend keeps them as the user's choice. */
    applyModules(): void {
        const modules = this.modules.filter(option => option.selected).map(option => option.module);
        if (!modules.length) {
            this.modulesWarning = true;
            return;
        }
        this.modulesOpen = false;
        if (this.module !== '' || this.offset !== 0) {
            // The module shown may no longer be searched: back to every module.
            this.navigate(this.searchTerm, '', 0, modules);
            return;
        }
        this.search(modules);
    }

    protected navigate(searchTerm: string, module: string, offset: number, modules: string[] = null): void {
        this.pendingModules = modules;
        const queryParams: { [key: string]: string | number } = {query_string: searchTerm};
        if (module) {
            queryParams.module = module;
        }
        if (offset) {
            queryParams.offset = offset;
        }
        this.router.navigate(['/home/search'], {queryParams}).then();
    }

    protected search(modules: string[] = null): void {
        modules = modules ?? this.pendingModules;
        this.pendingModules = null;
        this.searchSub?.unsubscribe();
        this.error = false;
        this.sections = [];
        this.tabs = [];
        this.pager = null;
        this.searched = this.searchTerm !== '';
        this.loading = true;
        this.words = this.getWords(this.searchTerm);

        // An empty search only loads the modules of the modules menu.
        const options: { [key: string]: any } = {searchTerm: this.searchTerm, module: this.module, offset: this.offset};
        if (modules) {
            options.modules = modules;
        }

        this.searchSub = this.processService.submit('global-search', options).pipe(
            take(1),
            switchMap((process: Process) => {
                if (process?.status !== 'success' || !process?.data) {
                    return of(null);
                }

                return this.loadColumns(process.data as GlobalSearchData);
            }),
            catchError(() => of(null))
        ).subscribe(result => {
            this.loading = false;
            if (result) {
                this.render(result.data, result.columns);
            } else {
                this.error = true;
            }

            // The process response is not always followed by a change detection: render now,
            // then mark the searched words in the rendered field components.
            this.changeDetector.detectChanges();
            if (result) {
                setTimeout(() => this.highlight());
            }
        });
    }

    /** Words to highlight: the searched words of at least two characters, longest first. */
    protected getWords(searchTerm: string): string[] {
        return searchTerm.split(/[^\p{L}\p{N}]+/u)
            .filter(word => word.length > 1)
            .sort((a, b) => b.length - a.length);
    }

    /** Default list view columns of every module shown, as the list view displays them. */
    protected loadColumns(data: GlobalSearchData): Observable<{ data: GlobalSearchData, columns: ColumnDefinition[][] }> {
        if (!data.sections?.length) {
            return of({data, columns: []});
        }

        return forkJoin(data.sections.map(section =>
            this.metadataStore.getMetadata(section.module, ['listView']).pipe(
                take(1),
                map(metadata => (metadata?.listView?.fields ?? []).filter(
                    column => !column.hasOwnProperty('default') || column.default === true
                )),
                catchError(() => of([] as ColumnDefinition[]))
            )
        )).pipe(map(columns => ({data, columns})));
    }

    protected render(data: GlobalSearchData, columns: ColumnDefinition[][]): void {
        this.modules = data.modules ?? [];
        this.module = data.module ?? '';
        this.pageSize = data.pageSize ?? this.pageSize;

        if (!this.searched) {
            return;
        }

        const counts = data.counts ?? {};
        this.total = Object.values(counts).reduce((sum, count) => sum + count, 0);
        this.tabs = this.modules
            .filter(option => (counts[option.module] ?? 0) > 0)
            .map(option => ({module: option.module, label: option.label, count: counts[option.module]}));
        this.emptyModules = this.modules
            .filter(option => option.selected && !(counts[option.module] ?? 0))
            .map(option => option.label)
            .join(', ');

        const labels: { [module: string]: string } = {};
        this.modules.forEach(option => labels[option.module] = option.label);

        this.sections = data.sections.map((section, index) => {
            const sectionColumns = columns[index] ?? [];
            const records = section.records.map(item => {
                const record: Record = {
                    id: item.id,
                    module: item.module,
                    type: item.type,
                    attributes: item.attributes,
                    acls: item.acls
                };
                sectionColumns.forEach(column => this.fieldManager.addField(record, column, sectionColumns, this.language));

                return record;
            });

            return {
                module: section.module,
                label: labels[section.module] ?? section.module,
                count: section.count,
                more: this.module === '' && section.count > records.length,
                columns: sectionColumns,
                records
            };
        });

        const pageSection = this.module !== '' ? data.sections[0] : null;
        this.pager = pageSection ? {
            from: this.offset + 1,
            to: Math.min(this.offset + this.pageSize, pageSection.count),
            total: pageSection.count,
            previous: this.offset > 0,
            next: this.offset + this.pageSize < pageSection.count
        } : null;
    }

    /** Wraps the searched words in <mark> in the result cells (dropdown values excepted). */
    protected highlight(): void {
        if (!this.words.length) {
            return;
        }

        const escaped = this.words.map(word => word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        const pattern = new RegExp('(' + escaped.join('|') + ')', 'giu');
        const cells = this.elementRef.nativeElement.querySelectorAll(
            'td.global-search-cell:not(.column-type-enum):not(.column-type-multienum)'
            + ':not(.column-type-dynamicenum):not(.column-type-radioenum)'
        );

        cells.forEach(cell => {
            const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
            const nodes: Text[] = [];
            while (walker.nextNode()) {
                const node = walker.currentNode as Text;
                pattern.lastIndex = 0;
                if (node.parentElement?.tagName !== 'MARK' && pattern.test(node.data)) {
                    nodes.push(node);
                }
            }

            nodes.forEach(node => {
                const fragment = document.createDocumentFragment();
                node.data.split(pattern).forEach((part, index) => {
                    if (part === '') {
                        return;
                    }
                    if (index % 2) {
                        const mark = document.createElement('mark');
                        mark.textContent = part;
                        fragment.appendChild(mark);
                    } else {
                        fragment.appendChild(document.createTextNode(part));
                    }
                });
                node.replaceWith(fragment);
            });
        });
    }
}
