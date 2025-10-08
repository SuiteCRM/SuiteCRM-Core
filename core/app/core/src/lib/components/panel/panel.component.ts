/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {Button, ButtonInterface} from '../../common/components/button/button.model';
import {Observable, Subscription} from 'rxjs';
import {MinimiseButtonStatus} from '../minimise-button/minimise-button.component';
import {UserPreferenceStore} from "../../store/user-preference/user-preference.store";

export type PanelCollapseMode = 'collapsible' | 'closable' | 'none';

@Component({
    selector: 'scrm-panel',
    templateUrl: './panel.component.html',
    styleUrls: []
})
export class PanelComponent implements OnInit, OnDestroy {

    @Input() klass = '';
    @Input() bodyPadding = 2;
    @Input() title: string;
    @Input() titleKey: string;
    @Input() key: string;
    @Input() context: string;
    @Input() mode: PanelCollapseMode = 'closable';
    @Input() isCollapsed$: Observable<boolean>;
    @Input() close: ButtonInterface = {
        klass: ['btn', 'btn-outline-light', 'btn-sm']
    } as ButtonInterface;
    @Input() showHeader = true;

    isCollapsed = false;
    minimiseButton: ButtonInterface;
    minimiseStatus: MinimiseButtonStatus;

    protected buttonClasses = ['btn', 'btn-outline-light', 'btn-sm'];
    protected subs: Subscription[] = [];

    constructor(
        protected preferences: UserPreferenceStore
    ) {
    }

    ngOnInit(): void {

        if (this.context && this.key) {
            this.isCollapsed = this.preferences.getUi(this.context, 'panel-' + this.key + '-is-collapsed') ?? false;
        }

        if (this.isCollapsed$) {
            this.subs.push(this.isCollapsed$.subscribe(collapse => {
                this.isCollapsed = collapse;
                this.initMinimiseButton();
            }));
        }
        this.initMinimiseButton();
    }

    ngOnDestroy(): void {
        this.subs.forEach(sub => sub.unsubscribe());
    }

    getCloseButton(): ButtonInterface {
        if (!this.close) {
            return null;
        }

        const btn = Button.fromButton(this.close);
        btn.addClasses(this.buttonClasses);

        this.close = btn;

        return btn;
    }

    isClosable(): boolean {
        return this.mode === 'closable';
    }

    isCollapsible(): boolean {
        return this.mode === 'collapsible';
    }

    initMinimiseButton(): void {
        this.minimiseButton = {
            klass: ['btn', 'btn-outline-light', 'btn-sm'],
            onClick: () => {
                this.isCollapsed = !this.isCollapsed;
                if (this.context && this.key) {
                    this.preferences.setUi(this.context, 'panel-' + this.key + '-is-collapsed', !!this.isCollapsed);
                }
                this.initMinimiseStatus();
            },
        } as ButtonInterface;
        this.initMinimiseStatus();
    }

    initMinimiseStatus(): void {
        if (this.isCollapsed) {
            this.minimiseStatus = 'minimised';
            return;
        }
        this.minimiseStatus = 'maximised';
    }
}
