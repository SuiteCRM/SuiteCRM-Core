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
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {ActivatedRoute, ParamMap, Router} from '@angular/router';
import {DomSanitizer, SafeResourceUrl} from '@angular/platform-browser';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ImageModule} from '../../../../components/image/image.module';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {ThemeImageMap, ThemeImagesStore} from '../../../../store/theme-images/theme-images.store';
import {
    STUDIO_FOLDERS,
    STUDIO_GRID_LAYOUTS,
    STUDIO_ICONS,
    STUDIO_LEGACY_ACTIONS,
    STUDIO_LIST_LAYOUTS,
    STUDIO_MODULE_ICONS,
    STUDIO_PROCESS,
    StudioHelp,
    StudioLabels,
    StudioModule,
    StudioModuleData,
    StudioNode,
    StudioScreen,
    StudioTile,
    StudioView
} from '../../studio.model';
import {StudioLabelsComponent} from '../studio-labels/studio-labels.component';
import {StudioFieldsComponent} from '../studio-fields/studio-fields.component';
import {StudioListLayoutComponent} from '../studio-list-layout/studio-list-layout.component';
import {StudioGridLayoutComponent} from '../studio-grid-layout/studio-grid-layout.component';
import {StudioResetComponent} from '../studio-reset/studio-reset.component';
import {StudioExportComponent} from '../studio-export/studio-export.component';
import {StudioRelationshipsComponent} from '../studio-relationships/studio-relationships.component';

const PANELS_KEY = 'studio-panels';

/**
 * Studio of the administration, as a SuiteCRM 8 page laid out as the legacy Studio:
 * the tree of the modules and their components on the left, the screen in the center (its title,
 * path and icons to open: the modules, the components of a module, its layouts, subpanels and
 * filters; its labels, fields (and the field editor) and relationships; Reset Module and Export
 * Customizations) and the help of the screen on the right. A legacy Studio action not in the
 * page opens in the legacy Studio.
 *
 * Route module-builder/index?type=studio (admin panel link), with view_module (the module), view
 * (labels, fields, a folder of the module: layouts, subpanels, search, dashlet, popup; layout: a
 * list layout, its legacy view in layout and its subpanel; relationships; reset; export; legacy),
 * field (fields: a field in the editor, empty for a new one) and mb (the legacy action).
 */
@Component({
    selector: 'scrm-studio',
    standalone: true,
    imports: [CommonModule, FormsModule, ImageModule, StudioLabelsComponent, StudioFieldsComponent, StudioListLayoutComponent,
        StudioGridLayoutComponent, StudioResetComponent, StudioExportComponent, StudioRelationshipsComponent],
    templateUrl: './studio.component.html',
    // Layout shared with the duplicates and Dropdown Editor pages (not encapsulated).
    styleUrls: []
})
export class StudioComponent implements OnInit, OnDestroy {

    modules: StudioModule[] = null;
    labels: StudioLabels = {};
    help: StudioHelp = {};
    module = '';
    view = '';
    mb = '';
    /** The list layout (its legacy view) and subpanel of the layout view. */
    layout = '';
    subpanel = '';
    /** The modules loaded (their components), for the screen and the tree. */
    loaded: { [module: string]: StudioModuleData } = {};
    /** The open nodes of the tree: modules, and their folders by module and action. */
    expanded = new Set<string>();
    screen: StudioScreen = null;
    hoverHelp: string = null;
    /** A relationship open in the editor: the help of the editor. */
    relationshipEditing = false;
    /** The field to open in the field editor ('' for a new one), from the route. */
    field: string = null;
    /** The field in the field editor ('' for a new one), null without editor. */
    fieldEditing: string = null;
    treeHidden = false;
    helpHidden = false;
    frameUrl: SafeResourceUrl = null;

    protected images: ThemeImageMap = {};
    protected pending = new Set<string>();
    protected paramsSub: Subscription;
    protected subs = new Subscription();

    constructor(
        protected route: ActivatedRoute,
        protected router: Router,
        protected sanitizer: DomSanitizer,
        protected processService: ProcessService,
        protected language: LanguageStore,
        protected themeImages: ThemeImagesStore,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnInit(): void {
        this.readPanels();
        this.subs.add(this.themeImages.images$.subscribe(images => {
            this.images = images ?? {};
            this.refresh();
        }));
        this.paramsSub = this.route.queryParamMap.subscribe(params => this.show(params));
    }

    ngOnDestroy(): void {
        this.paramsSub?.unsubscribe();
        this.subs.unsubscribe();
    }

    label(key: string): string {
        return this.labels[key] || this.language.getAppString(key) || key;
    }

    get shownHelp(): string {
        return this.hoverHelp || this.screen?.help || '';
    }

    home(): void {
        this.navigate({});
    }

    /**
     * The screen above (the path before the screen): the browser history also has the moves in
     * the legacy Studio frame.
     */
    back(): void {
        const crumbs = this.screen?.crumbs ?? [];
        const parent = crumbs.slice(0, -1).reverse().find(crumb => crumb.open);
        if (parent) {
            parent.open();
        } else {
            this.home();
        }
    }

    openModule(module: string): void {
        this.navigate({view_module: module});
    }

    /**
     * Opens a component of a module: its labels, fields and folders in the page, the others in
     * the legacy Studio.
     */
    openNode(node: StudioNode): void {
        const target = this.target(node);
        this.navigate({
            view_module: target.module,
            view: target.view,
            mb: target.mb || null,
            layout: target.layout || null,
            subpanel: target.subpanel || null
        });
    }

    open(view: StudioView): void {
        this.navigate({view_module: this.module || null, view: view.view, mb: view.mb ?? null, layout: view.layout ?? null, subpanel: view.subpanel ?? null});
    }

    /**
     * Whether the layout is a grid layout (edit view, detail view, quick create).
     */
    get isGridLayout(): boolean {
        return STUDIO_GRID_LAYOUTS.includes(this.layout.toLowerCase());
    }

    /**
     * The help group of the legacy editor of the layout.
     */
    get layoutHelpGroup(): string {
        if (this.isGridLayout) {
            return 'layoutEditor';
        }

        return this.subpanel ? 'subPanelEditor' : STUDIO_LIST_LAYOUTS[this.layout.toLowerCase()] ?? 'listViewEditor';
    }

    exportCustomizations(): void {
        this.navigate({view: 'export'});
    }

    resetModule(): void {
        this.open({view: 'reset'});
    }

    /**
     * The help of a button while hovered or focused (as the legacy Studio), the help of the
     * screen otherwise.
     */
    hover(help: string): void {
        this.hoverHelp = help || null;
    }

    setFieldEditing(field: string | null): void {
        this.fieldEditing = field;
        // The editor closed: no field of the route to open again.
        if (field === null && this.field !== null) {
            this.navigate({view_module: this.module, view: 'fields'}, true);
            return;
        }
        this.refresh();
    }

    setRelationshipEditing(editing: boolean): void {
        this.relationshipEditing = editing;
        this.refresh();
    }

    exportHelp(): string {
        return this.helpText('studioWizard', 'exportBtn');
    }

    isExpanded(key: string): boolean {
        return this.expanded.has(key);
    }

    toggleModule(module: string): void {
        this.toggle(module);
        this.loadModule(module);
    }

    toggle(key: string): void {
        if (this.expanded.has(key)) {
            this.expanded.delete(key);
        } else {
            this.expanded.add(key);
        }
    }

    folderKey(module: string, node: StudioNode): string {
        return `${module}|${node.action}`;
    }

    isCurrent(node: StudioNode): boolean {
        const target = this.target(node);

        return target.module === this.module && target.view === this.view && (target.view !== 'legacy' || target.mb === this.mb)
            && (target.view !== 'layout' || (target.layout === this.layout && target.subpanel === this.subpanel));
    }

    toggleTree(): void {
        this.treeHidden = !this.treeHidden;
        this.writePanels();
    }

    toggleHelp(): void {
        this.helpHidden = !this.helpHidden;
        this.writePanels();
    }

    trackModule(index: number, item: StudioModule): string {
        return item.module;
    }

    trackNode(index: number, node: StudioNode): string {
        return node.action + '|' + node.name;
    }

    trackTile(index: number, tile: StudioTile): string {
        return tile.name + '|' + index;
    }

    protected show(params: ParamMap): void {
        // A legacy Studio action the page has its own screen for (an earlier link): that screen.
        if (params.get('view') === 'legacy' && params.get('mb')) {
            const native = this.target({name: '', action: params.get('mb')});
            if (native.view !== 'legacy') {
                this.navigate({
                    view_module: native.module || null,
                    view: native.view,
                    layout: native.layout || null,
                    subpanel: native.subpanel || null,
                    field: native.field ?? null
                }, true);
                return;
            }
        }

        this.module = params.get('view_module') ?? '';
        this.view = params.get('view') ?? '';
        this.mb = params.get('mb') ?? '';
        this.layout = params.get('layout') ?? '';
        this.subpanel = params.get('subpanel') ?? '';
        this.hoverHelp = null;
        this.relationshipEditing = false;
        this.field = params.get('field');
        this.fieldEditing = this.view === 'fields' ? this.field : null;
        this.frameUrl = this.view === 'legacy' && this.isLegacyAction(this.mb)
            ? this.sanitizer.bypassSecurityTrustResourceUrl('legacy/index.php?module=ModuleBuilder&action=index&type=studio#mbContent=' + encodeURIComponent(this.mb))
            : null;

        if (this.modules === null && !this.pending.has('')) {
            this.pending.add('');
            this.load({action: 'modules'}, data => {
                this.modules = data.modules ?? [];
                this.labels = data.labels ?? {};
                this.help = data.help ?? {};
            }, () => this.pending.delete(''));
        }
        if (this.module) {
            this.loadModule(this.module);
            this.expandCurrent();
        }
        this.refresh();
    }

    protected loadModule(module: string): void {
        if (this.loaded[module] || this.pending.has(module)) {
            return;
        }
        this.pending.add(module);
        this.load({action: 'module', module}, data => {
            this.loaded[module] = data as StudioModuleData;
            this.labels = data.labels ?? this.labels;
            if (module === this.module) {
                this.expandCurrent();
            }
        }, () => this.pending.delete(module));
    }

    /**
     * Opens the module of the screen in the tree, and the folders down to its component.
     */
    protected expandCurrent(): void {
        const data = this.loaded[this.module];
        if (!data) {
            return;
        }
        this.expanded.add(this.module);
        (this.findPath(data.nodes, node => this.isCurrent(node)) ?? [])
            .filter(node => node.children?.length)
            .forEach(node => this.expanded.add(this.folderKey(this.module, node)));
    }

    protected refresh(): void {
        this.screen = this.buildScreen();
        this.cdr.detectChanges();
    }

    /**
     * The screen of the legacy Studio for the route (legacy view.wizard, view.labels,
     * view.modulefields): its title, path, question, help and icons.
     */
    protected buildScreen(): StudioScreen {
        const studio = this.label('LBL_STUDIO');
        const home = {name: studio, open: () => this.home()};

        if (!this.module) {
            if (this.view === 'export') {
                return {title: this.label('LBL_EC_TITLE'), crumbs: [home, {name: this.label('LBL_BTN_EXPORT')}], help: this.helpText('exportcustom', 'exportHelp')};
            }
            if (!this.modules) {
                return null;
            }

            return {
                title: studio,
                crumbs: [{name: studio}],
                question: this.label('LBL_QUESTION_EDIT'),
                help: this.helpText('studioWizard', 'studioHelp'),
                tiles: this.modules.map(item => ({
                    name: item.name,
                    icon: this.moduleIcon(item.module),
                    help: this.helpText('studioWizard', 'moduleBtn'),
                    open: () => this.openModule(item.module)
                }))
            };
        }

        const data = this.loaded[this.module];
        if (!data) {
            return null;
        }
        const moduleCrumb = {name: data.name, open: () => this.openModule(this.module)};

        if (!this.view) {
            return {
                title: `${this.label('LBL_EDIT')} ${data.name}`,
                crumbs: [home, {name: data.name}],
                question: this.label('LBL_QUESTION_MODULE'),
                help: this.helpText('studioWizard', 'moduleHelp'),
                tiles: data.nodes.map(node => this.tile(node))
            };
        }
        if (this.view === 'labels') {
            return {
                title: this.label('LBL_SECTION_EDLABELS'),
                crumbs: [home, moduleCrumb, {name: this.label('LBL_LABELS')}],
                help: this.helpText('labelsHelp', 'default')
            };
        }
        if (this.view === 'relationships') {
            return {
                title: `${data.name} ${this.label('LBL_RELATIONSHIPS')}`,
                crumbs: [home, moduleCrumb, {name: this.label('LBL_RELATIONSHIPS')}],
                help: this.helpText('studioWizard', this.relationshipEditing ? 'relationshipHelp' : 'relationshipsHelp')
            };
        }
        if (this.view === 'reset') {
            return {
                title: `${this.label('LBL_RESET')} ${data.name}`,
                crumbs: [home, moduleCrumb, {name: `${this.label('LBL_RESET')} ${data.name}`}],
                help: this.helpText('studioWizard', 'moduleHelp')
            };
        }
        if (this.view === 'fields') {
            const fieldsCrumb = {name: this.label('LBL_FIELDS'), open: () => this.open({view: 'fields'})};

            return {
                title: this.label('LBL_EDIT_FIELDS'),
                crumbs: this.fieldEditing === null
                    ? [home, moduleCrumb, {name: this.label('LBL_FIELDS')}]
                    : [home, moduleCrumb, fieldsCrumb, {name: this.fieldEditing || this.label('LBL_BTN_ADDFIELD')}],
                help: this.helpText('fieldsEditor', this.fieldEditing === null ? 'default' : this.fieldEditing ? 'editField' : 'addField')
            };
        }

        if (this.view === 'layout') {
            const path = this.findPath(data.nodes, node => this.isCurrent(node)) ?? [];

            return {
                title: path[path.length - 1]?.name ?? data.name,
                crumbs: [home, moduleCrumb, ...path.map((item, index) => index < path.length - 1 ? {name: item.name, open: () => this.openNode(item)} : {name: item.name})],
                // The help of the layout: its editor (the grid one by layout: default for the edit view).
                help: this.helpText(this.layoutHelpGroup, this.isGridLayout
                    ? (this.layout.toLowerCase() === 'editview' ? 'default' : 'default' + this.layout.toLowerCase())
                    : 'modify')
            };
        }

        const folder = STUDIO_FOLDERS[this.view];
        if (folder) {
            const path = this.findPath(data.nodes, node => this.isFolder(node, this.view)) ?? [];
            const node = path[path.length - 1];

            return {
                title: node ? `${data.name} ${node.name}` : data.name,
                crumbs: [home, moduleCrumb, ...path.map((item, index) => index < path.length - 1
                    ? {name: item.name, open: () => this.openNode(item)}
                    : {name: item.name})],
                question: this.label(folder.question),
                help: this.helpText('studioWizard', folder.help),
                tiles: (node?.children ?? []).map(child => this.tile(child))
            };
        }

        return {title: data.name, crumbs: [home, moduleCrumb, ...this.legacyCrumbs(data)], help: ''};
    }

    /**
     * The path of a legacy action of the module: its component in the tree, or the fields for
     * the field editor.
     */
    protected legacyCrumbs(data: StudioModuleData): { name: string; open?: () => void }[] {
        const path = this.findPath(data.nodes, node => node.action === this.mb);
        if (path) {
            return path.map((item, index) => index < path.length - 1 ? {name: item.name, open: () => this.openNode(item)} : {name: item.name});
        }

        return [];
    }

    protected tile(node: StudioNode): StudioTile {
        return {
            name: node.name,
            icon: this.nodeIcon(node),
            help: node.help ? this.helpText('studioWizard', node.help) : '',
            open: () => this.openNode(node)
        };
    }

    /**
     * Where a component opens: its module and the view of the page (labels, fields, its folder),
     * or its legacy action.
     */
    protected target(node: StudioNode): { module: string; view: string; mb: string; layout?: string; subpanel?: string; field?: string } {
        const params = new URLSearchParams(node.action);
        const module = params.get('view_module') || this.module;
        const action = params.get('action');
        if (action === 'editLabels') {
            return {module, view: 'labels', mb: ''};
        }
        if (action === 'modulefields') {
            return {module, view: 'fields', mb: ''};
        }
        if (action === 'modulefield') {
            return {module, view: 'fields', mb: '', field: params.get('field') ?? ''};
        }
        if (action === 'relationships') {
            return {module, view: 'relationships', mb: ''};
        }
        if (action === 'resetmodule') {
            return {module, view: 'reset', mb: ''};
        }
        if (action === 'exportcustomizations') {
            return {module: '', view: 'export', mb: ''};
        }
        if (action === 'wizard' && STUDIO_FOLDERS[params.get('view') ?? '']) {
            return {module, view: params.get('view'), mb: ''};
        }
        const layout = (params.get('view') ?? '').toLowerCase();
        if (action === 'editLayout' && (STUDIO_LIST_LAYOUTS[layout] || STUDIO_GRID_LAYOUTS.includes(layout))) {
            return {module, view: 'layout', mb: '', layout: params.get('view'), subpanel: params.get('subpanel') ?? ''};
        }

        return {module, view: 'legacy', mb: node.action};
    }

    protected isFolder(node: StudioNode, view: string): boolean {
        const params = new URLSearchParams(node.action);

        return !!node.children?.length && params.get('action') === 'wizard' && params.get('view') === view;
    }

    /**
     * The nodes from the top of the tree down to the first node matching.
     */
    protected findPath(nodes: StudioNode[], match: (node: StudioNode) => boolean): StudioNode[] | null {
        for (const node of nodes ?? []) {
            if (match(node)) {
                return [node];
            }
            const path = this.findPath(node.children, match);
            if (path) {
                return [node, ...path];
            }
        }

        return null;
    }

    /**
     * The SuiteCRM 8 theme icon of a module (the legacy theme ones are for dark backgrounds),
     * else the one of the legacy Studio for it, else the basic module icon.
     */
    protected moduleIcon(module: string): string {
        const isThemeIcon = (name: string): boolean => this.images?.[name]?.type === 'svg' && !!this.images[name].path?.startsWith('dist/');

        return [module, STUDIO_MODULE_ICONS[module]].find(name => name && isThemeIcon(name)) ?? 'basic';
    }

    /**
     * The icon of a component: its module for a subpanel, the theme icon of its action otherwise.
     */
    protected nodeIcon(node: StudioNode): string {
        if (node.module) {
            return this.moduleIcon(node.module);
        }
        const params = new URLSearchParams(node.action);
        const action = params.get('action') ?? '';
        const key = action === 'wizard' || action === 'editLayout' ? `${action}:${(params.get('view') ?? '').toLowerCase()}` : action;

        return STUDIO_ICONS[key] ?? 'list';
    }

    protected helpText(group: string, key: string): string {
        return this.help?.[group]?.[key] ?? '';
    }

    /**
     * Whether a legacy action is one the page opens in the legacy Studio (its views only).
     */
    protected isLegacyAction(mb: string): boolean {
        const params = new URLSearchParams(mb);

        return params.get('module') === 'ModuleBuilder' && STUDIO_LEGACY_ACTIONS.includes(params.get('action') ?? '');
    }

    protected navigate(params: { [key: string]: string | null }, replaceUrl = false): void {
        this.router.navigate([], {relativeTo: this.route, queryParams: {type: 'studio', ...params}, replaceUrl});
    }

    /**
     * The panels hidden by the user (tree, help), kept in the browser as the legacy Studio.
     */
    protected readPanels(): void {
        try {
            const panels = JSON.parse(localStorage.getItem(PANELS_KEY) ?? '{}');
            this.treeHidden = panels?.tree === true;
            this.helpHidden = panels?.help === true;
        } catch (e) {
            this.treeHidden = this.helpHidden = false;
        }
    }

    protected writePanels(): void {
        try {
            localStorage.setItem(PANELS_KEY, JSON.stringify({tree: this.treeHidden, help: this.helpHidden}));
        } catch (e) {
        }
    }

    protected load(options: { [key: string]: any }, next: (data: any) => void, done: () => void = () => {
    }): void {
        this.subs.add(this.processService.submit(STUDIO_PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                done();
                if (process?.status === 'success') {
                    next(process.data ?? {});
                } else {
                    this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                }
                this.refresh();
            },
            error: () => {
                done();
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.refresh();
            }
        }));
    }
}
