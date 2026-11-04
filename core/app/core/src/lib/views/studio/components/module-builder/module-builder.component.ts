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

import {Component} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {ParamMap} from '@angular/router';
import {take} from 'rxjs/operators';
import {ImageModule} from '../../../../components/image/image.module';
import {Process} from '../../../../services/process/process.service';
import {StudioHelp, StudioOption, StudioScreen} from '../../studio.model';
import {StudioComponent} from '../studio/studio.component';
import {StudioLabelsComponent} from '../studio-labels/studio-labels.component';
import {StudioFieldsComponent} from '../studio-fields/studio-fields.component';
import {StudioListLayoutComponent} from '../studio-list-layout/studio-list-layout.component';
import {StudioGridLayoutComponent} from '../studio-grid-layout/studio-grid-layout.component';
import {StudioRelationshipsComponent} from '../studio-relationships/studio-relationships.component';
import {postStudioAction} from '../../studio-legacy';

const MODULE_BUILDER_PROCESS = 'module-builder';

/** A package of Module Builder and its modules. */
interface MbPackage {
    name: string;
    modules: string[];
}

/** The properties of a package (original: its name before the save, '' for a new one). */
interface MbPackageForm {
    original: string;
    name: string;
    key: string;
    author: string;
    description: string;
    readme: string;
    dateModified: string;
    modules: string[];
}

/** The properties of a module of a package (original: its name before the save, '' for a new one). */
interface MbModuleForm {
    original: string;
    name: string;
    label: string;
    type: string;
    importable: boolean;
    hasTab: boolean;
    types: string[];
}

/**
 * Module Builder of the administration, as a SuiteCRM 8 page laid out as the legacy
 * Module Builder (the Studio page for the modules of the packages): the packages, their modules
 * and the components of a module on the left; in the center the packages, a package (its
 * properties, modules, Deploy, Publish, Export, Duplicate, Delete), a module (its properties,
 * Duplicate, Delete, and its components) and the components of Studio for the module of the
 * package (fields, labels, relationships, layouts); the help on the right.
 *
 * Route module-builder/index?type=mb (admin panel link), with view_package (the package),
 * view_module (its module), view (newPackage, newModule, or the views of Studio) and the
 * parameters of the Studio views. Process `scrm-module-builder` for the packages and modules,
 * `scrm-studio` with the package for the components.
 */
@Component({
    selector: 'scrm-module-builder',
    standalone: true,
    imports: [CommonModule, FormsModule, ImageModule, StudioLabelsComponent, StudioFieldsComponent, StudioListLayoutComponent,
        StudioGridLayoutComponent, StudioRelationshipsComponent],
    templateUrl: './module-builder.component.html',
    styleUrls: []
})
export class ModuleBuilderComponent extends StudioComponent {

    package = '';
    packages: MbPackage[] = null;
    /** The types of a new module (templates). */
    types: StudioOption[] = [];
    packageForm: MbPackageForm = null;
    /** The labels of Module Builder (kept apart: the Studio loads set the labels of Studio). */
    mbLabels: { [key: string]: string } = {};
    /** The help of the legacy Module Builder screens (package, module), kept apart as its labels. */
    protected mbHelpTexts: StudioHelp = {};
    moduleForm: MbModuleForm = null;
    busy = false;

    protected routeType = 'mb';
    protected mbPending = new Set<string>();

    label(key: string): string {
        return this.mbLabels[key] || super.label(key);
    }

    home(): void {
        this.navigate({view_package: null});
    }

    openPackage(name: string): void {
        this.navigate({view_package: name});
    }

    newPackage(): void {
        this.navigate({view_package: null, view: 'newPackage'});
    }

    newModule(): void {
        this.navigate({view: 'newModule'});
    }

    openPackageModule(packageName: string, module: string): void {
        this.navigate({view_package: packageName, view_module: module});
    }

    /** The help of a button of the legacy Module Builder (by group and key). */
    mbHelp(group: string, key: string): string {
        return this.helpText(group, key);
    }

    protected helpText(group: string, key: string): string {
        return this.mbHelpTexts?.[group]?.[key] ?? super.helpText(group, key);
    }

    /** The types of a module saved (its templates). */
    get moduleTypeNames(): string {
        return (this.moduleForm?.types ?? []).map(type => this.typeLabel(type)).join(', ');
    }

    get isPackageScreen(): boolean {
        return this.view === 'newPackage' || (!!this.package && !this.module && this.view !== 'newModule');
    }

    get isModuleScreen(): boolean {
        return this.view === 'newModule' || (!!this.module && !this.view);
    }

    get validPackage(): boolean {
        const form = this.packageForm;

        return !!form && this.isName(form.name) && this.isName(form.key);
    }

    get validModule(): boolean {
        const form = this.moduleForm;

        return !!form && this.isName(form.name) && !!form.label.trim() && (!!form.original || !!form.type);
    }

    typeLabel(type: string): string {
        return this.types.find(option => option.value === type)?.label ?? type;
    }

    // Packages.

    savePackage(duplicate = false): void {
        const form = this.packageForm;
        if (!this.validPackage || this.busy) {
            this.message.addWarningMessage(this.label(this.isName(form?.name ?? '') ? 'LBL_JS_VALIDATE_KEY' : 'LBL_JS_VALIDATE_NAME'));
            return;
        }
        this.run({
            action: 'savePackage',
            original: form.original,
            duplicate,
            values: {name: form.name.trim(), key: form.key.trim(), author: form.author, description: form.description, readme: form.readme}
        }, data => {
            this.reloadPackages();
            this.packageForm = null;
            this.navigate({view_package: data.name, view: null});
        });
    }

    deletePackage(): void {
        if (!this.package || !window.confirm(this.label('LBL_JS_REMOVE_PACKAGE'))) {
            return;
        }
        this.run({action: 'deletePackage', package: this.package}, () => {
            this.reloadPackages();
            this.home();
        });
    }

    async deployPackage(): Promise<void> {
        // As the legacy Deploy: a confirmation when the package was deployed before.
        if (!this.package || this.busy || !window.confirm(this.label('LBL_JS_DEPLOY_PACKAGE'))) {
            return;
        }
        this.message.addSuccessMessage(this.label('LBL_AJAX_DEPLOYPROGRESS'));
        this.busy = true;
        this.refresh();
        try {
            // The install repairs the application (Symfony cache cleared): posted to the legacy action.
            const output = await postStudioAction({action: 'DeployPackage', package: this.package});
            output.includes('complete') ? this.message.addSuccessMessage(this.label('LBL_MODULE_DEPLOYED'))
                : this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
        } catch (e) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
        } finally {
            this.busy = false;
            this.refresh();
        }
    }

    publishPackage(): void {
        this.message.addSuccessMessage(this.label('LBL_AJAX_BUILDPROGRESS'));
        this.run({action: 'publishPackage', package: this.package}, data => this.download(data.fileName, data.content));
    }

    exportPackage(): void {
        const form = this.packageForm;
        this.message.addSuccessMessage(this.label('LBL_AJAX_BUILDPROGRESS'));
        this.run({
            action: 'exportPackage',
            package: this.package,
            values: {author: form?.author ?? '', description: form?.description ?? '', readme: form?.readme ?? ''}
        }, data => this.download(data.fileName, data.content));
    }

    // Modules.

    saveModule(duplicate = false): void {
        const form = this.moduleForm;
        if (!this.validModule || this.busy) {
            this.message.addWarningMessage(this.label(!this.isName(form?.name ?? '') ? 'LBL_JS_VALIDATE_NAME'
                : !form.label.trim() ? 'LBL_JS_VALIDATE_LABEL' : 'LBL_JS_VALIDATE_TYPE'));
            return;
        }
        this.run({
            action: 'saveModule',
            package: this.package,
            original: form.original,
            duplicate,
            values: {name: form.name.trim(), label: form.label.trim(), type: form.type, importable: form.importable, hasTab: form.hasTab}
        }, data => {
            this.reloadPackages();
            delete this.loaded[this.loadKey(data.name)];
            if (form.original) {
                delete this.loaded[this.loadKey(form.original)];
            }
            this.moduleForm = null;
            this.navigate({view_module: data.name, view: null});
        });
    }

    deleteModule(): void {
        if (!this.module || !window.confirm(this.label('LBL_JS_REMOVE_MODULE'))) {
            return;
        }
        this.run({action: 'deleteModule', package: this.package, module: this.module}, () => {
            delete this.loaded[this.loadKey(this.module)];
            this.reloadPackages();
            this.navigate({view_module: null, view: null});
        });
    }

    protected show(params: ParamMap): void {
        this.package = params.get('view_package') ?? '';
        const module = params.get('view_module') ?? '';
        const view = params.get('view') ?? '';

        if (this.packages === null) {
            this.reloadPackages();
        }
        // The properties of the package or module of the screen.
        if (view === 'newPackage') {
            this.packageForm = {original: '', name: '', key: '', author: '', description: '', readme: '', dateModified: '', modules: []};
        } else if (this.package && !module && view !== 'newModule' && this.packageForm?.original !== this.package) {
            this.loadForm('package', {action: 'package', package: this.package}, data => this.packageForm = {...data, original: data.name});
        }
        if (view === 'newModule') {
            this.moduleForm = {original: '', name: '', label: '', type: '', importable: false, hasTab: true, types: []};
        } else if (module && !view && (this.moduleForm?.original !== module || this.packageForm?.original !== this.package)) {
            this.loadForm('module', {action: 'module', package: this.package, module}, data => this.moduleForm = {...data, type: '', original: data.name});
        }

        super.show(params);

        // A legacy Module Builder action not in the page yet: the legacy Module Builder.
        if (this.view === 'legacy' && this.isLegacyAction(this.mb)) {
            this.frameUrl = this.sanitizer.bypassSecurityTrustResourceUrl('legacy/index.php?module=ModuleBuilder&action=index&type=mb#mbContent=' + encodeURIComponent(this.mb));
        }
    }

    protected loadKey(module: string): string {
        return `${this.package}|${module}`;
    }

    protected moduleRequest(module: string): { [key: string]: string } {
        return {module, package: this.package};
    }

    protected navigate(params: { [key: string]: string | null }, replaceUrl = false): void {
        super.navigate({view_package: this.package || null, ...params}, replaceUrl);
    }

    /**
     * The screen of the legacy Module Builder: the packages, a package, a module (its properties
     * and components), or a component of Studio for the module, in the package.
     */
    protected buildScreen(): StudioScreen {
        const title = this.label('LBL_MODULEBUILDER');
        const home = {name: title, open: () => this.home()};

        if (this.view === 'newPackage') {
            return {title: this.label('LBL_NEW_PACKAGE'), crumbs: [home, {name: this.label('LBL_NEW_PACKAGE')}], help: this.helpText('package', 'create')};
        }
        if (!this.package) {
            if (!this.packages) {
                return null;
            }

            return {
                title,
                crumbs: [{name: title}],
                question: this.label('LBL_QUESTION_PACKAGE'),
                help: this.helpText('package', 'create'),
                tiles: this.packages.map(item => ({name: item.name, icon: 'folder', open: () => this.openPackage(item.name)}))
            };
        }

        const packageCrumb = {name: this.package, open: () => this.openPackage(this.package)};
        if (this.view === 'newModule') {
            return {
                title: this.label('LBL_NEW_MODULE'),
                crumbs: [home, packageCrumb, {name: this.label('LBL_NEW_MODULE')}],
                help: this.helpText('module', 'create')
            };
        }
        if (!this.module) {
            return {
                title: this.package,
                crumbs: [home, {name: this.package}],
                help: this.helpText('package', 'modify'),
                tiles: (this.packageForm?.modules ?? this.packages?.find(item => item.name === this.package)?.modules ?? [])
                    .map(module => ({name: module, icon: 'basic', open: () => this.openModule(module)}))
            };
        }

        const screen = super.buildScreen();
        if (!screen) {
            return null;
        }
        screen.crumbs = [home, packageCrumb, ...screen.crumbs.slice(1)];
        if (!this.view) {
            screen.title = this.module;
            screen.question = '';
            screen.help = this.helpText('module', 'modify');
        }

        return screen;
    }

    protected reloadPackages(): void {
        if (this.mbPending.has('packages')) {
            return;
        }
        this.mbPending.add('packages');
        this.subs.add(this.processService.submit(MODULE_BUILDER_PROCESS, {action: 'packages'}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.mbPending.delete('packages');
                if (process?.status === 'success') {
                    this.packages = process.data.packages ?? [];
                    this.types = process.data.types ?? [];
                    this.mbLabels = process.data.labels ?? {};
                    this.mbHelpTexts = process.data.help ?? {};
                } else {
                    this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                }
                this.refresh();
            },
            error: () => {
                this.mbPending.delete('packages');
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.refresh();
            }
        }));
    }

    protected loadForm(key: string, options: { [key: string]: any }, next: (data: any) => void): void {
        if (this.mbPending.has(key)) {
            return;
        }
        this.mbPending.add(key);
        this.run(options, data => next(data), () => this.mbPending.delete(key));
    }

    /**
     * Runs an action of Module Builder: its data, or the error of its checks.
     */
    protected run(options: { [key: string]: any }, next: (data: any) => void, done: () => void = () => {
    }): void {
        this.busy = true;
        this.refresh();
        this.subs.add(this.processService.submit(MODULE_BUILDER_PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.busy = false;
                done();
                if (process?.status === 'success') {
                    next(process.data ?? {});
                } else {
                    this.message.addDangerMessage(process?.data?.error || this.label('LBL_ACTION_ERROR'));
                }
                this.refresh();
            },
            error: () => {
                this.busy = false;
                done();
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.refresh();
            }
        }));
    }

    /** The zip of a package for the browser to save. */
    protected download(fileName: string, content: string): void {
        if (!fileName || !content) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
            return;
        }
        const bytes = Uint8Array.from(atob(content), char => char.charCodeAt(0));
        const url = URL.createObjectURL(new Blob([bytes], {type: 'application/zip'}));
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    /** A package, key or module name (legacy DBName check). */
    protected isName(value: string): boolean {
        return /^[a-zA-Z][a-zA-Z0-9_]*$/.test((value ?? '').trim());
    }
}
