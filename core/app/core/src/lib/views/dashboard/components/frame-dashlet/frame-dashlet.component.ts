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

import {Component, Input, OnChanges} from '@angular/core';
import {CommonModule} from '@angular/common';
import {DomSanitizer, SafeResourceUrl} from '@angular/platform-browser';
import {LanguageStore} from '../../../../store/language/language.store';
import {DashletData} from '../../dashboard.model';

/**
 * An iFrame (or SuiteCRM News) dashlet: the page of its options,
 * with their height. The URL is checked by the server as by the legacy
 * dashlet (http or https only, not this CRM).
 */
@Component({
    selector: 'scrm-frame-dashlet',
    standalone: true,
    imports: [CommonModule],
    template: `
        <iframe *ngIf="url; else invalid" class="dashboard-frame" [src]="url" [style.height.px]="height"
                [title]="dashlet.title" referrerpolicy="no-referrer"
                sandbox="allow-scripts allow-same-origin allow-popups allow-forms"></iframe>
        <ng-template #invalid>
            <p class="dashboard-dashlet-message">{{ label('LBL_DASHBOARD_INVALID_URL') }}</p>
        </ng-template>
    `
})
export class FrameDashletComponent implements OnChanges {

    @Input() dashlet: DashletData;

    url: SafeResourceUrl | null = null;
    height = 315;

    constructor(protected sanitizer: DomSanitizer, protected languageStore: LanguageStore) {
    }

    ngOnChanges(): void {
        const frame = this.dashlet?.frame;
        this.height = frame?.height || 315;
        this.url = frame?.url && /^https?:\/\//i.test(frame.url)
            ? this.sanitizer.bypassSecurityTrustResourceUrl(frame.url)
            : null;
    }

    label(key: string): string {
        return this.languageStore.getFieldLabel(key, 'home') || key;
    }

    /** Reloads the page (refresh button of the dashlet). */
    refresh(): void {
        this.url = null;
        setTimeout(() => this.ngOnChanges());
    }
}
