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

import {ChangeDetectorRef, Component, Input, OnChanges, OnDestroy, SimpleChanges} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {STUDIO_PROCESS, StudioLabels, StudioResetAction} from '../../studio.model';
import {postStudioAction, studioActionLines} from '../../studio-legacy';

/**
 * Reset Module of Studio (legacy view.resetmodule): the customizations of the module
 * to remove (all checked, as the legacy screen), removed once confirmed by the legacy action (its
 * relationships and extensions repair the application), then what it removed.
 */
@Component({
    selector: 'scrm-studio-reset',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './studio-reset.component.html',
    styleUrls: []
})
export class StudioResetComponent implements OnChanges, OnDestroy {

    @Input() module = '';
    @Input() labels: StudioLabels = {};

    actions: StudioResetAction[] = null;
    checked: { [name: string]: boolean } = {};
    /** The lines of what the reset removed, once done. */
    removed: string[] = null;
    loading = false;

    protected sub: Subscription;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected confirmation: ConfirmationModalService,
        protected message: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes.module) {
            this.actions = null;
            this.removed = null;
            this.submit({action: 'resetActions', module: this.module}, data => {
                this.actions = data.actions ?? [];
                this.checked = {};
                this.actions.forEach(action => this.checked[action.name] = true);
            });
        }
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.labels[key] || this.languageStore.getAppString(key) || key;
    }

    get hasChecked(): boolean {
        return (this.actions ?? []).some(action => this.checked[action.name]);
    }

    reset(): void {
        if (!this.hasChecked || this.loading) {
            return;
        }
        const params: { [key: string]: string } = {action: 'resetmodule', view_module: this.module, handle: 'execute'};
        this.actions.filter(action => this.checked[action.name]).forEach(action => params[action.name] = action.name);
        this.confirmation.showModal(['LBL_STUDIO_VIEW_RESET_CONFIRM'], () => this.reset$(params), () => {}, {}, {module: 'module-builder'});
    }

    trackName(index: number, action: StudioResetAction): string {
        return action.name;
    }

    /**
     * Runs the legacy Reset Module (ViewResetmodule::handleSave()) and shows the lines it answers
     * (the last one, its Complete!, as the request processed).
     */
    protected async reset$(params: { [key: string]: string }): Promise<void> {
        this.loading = true;
        this.cdr.detectChanges();
        try {
            const lines = studioActionLines(await postStudioAction(params));
            if (lines === null) {
                throw new Error('No answer');
            }
            this.removed = lines.slice(0, -1);
            this.message.addSuccessMessageByKey('LBL_REQUEST_PROCESSED');
        } catch (e) {
            this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
        } finally {
            this.loading = false;
            this.cdr.detectChanges();
        }
    }

    protected submit(options: { [key: string]: any }, next: (data: any) => void): void {
        this.sub?.unsubscribe();
        this.loading = true;
        this.cdr.detectChanges();
        this.sub = this.processService.submit(STUDIO_PROCESS, options).pipe(take(1)).subscribe({
            next: (process: Process) => {
                this.loading = false;
                if (process?.status === 'success') {
                    next(process.data ?? {});
                } else {
                    this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                }
                this.cdr.detectChanges();
            },
            error: () => {
                this.loading = false;
                this.message.addDangerMessage(this.label('LBL_ACTION_ERROR'));
                this.cdr.detectChanges();
            }
        });
    }
}
