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

import {ChangeDetectorRef, Component, Input, OnChanges, OnDestroy} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {Subscription} from 'rxjs';
import {take} from 'rxjs/operators';
import {ConfirmationModalService} from '../../../../services/modals/confirmation-modal.service';
import {LanguageStore} from '../../../../store/language/language.store';
import {MessageService} from '../../../../services/message/message.service';
import {Process, ProcessService} from '../../../../services/process/process.service';
import {DashletData, DashletFeed, DashletFeedItem} from '../../dashboard.model';

/**
 * An Activity Stream (SugarFeed) dashlet: the posts the legacy
 * dashlet finds (its categories and "my items"), with their replies, a post
 * form when user posts are enabled, replies, and deletion of the user's own
 * posts (or any post for an admin). The actions are the legacy dashlet's own
 * (see DashboardHandler::feedAction()).
 */
@Component({
    selector: 'scrm-feed-dashlet',
    standalone: true,
    imports: [CommonModule, FormsModule],
    template: `
        <p *ngIf="error" class="dashboard-dashlet-message dashboard-error">{{ label('LBL_DASHBOARD_DASHLET_ERROR') }}</p>
        <p *ngIf="!error && !feed && loading" class="dashboard-dashlet-message">{{ label('LBL_LOADING') }}</p>
        <div *ngIf="!error && feed" class="dashboard-feed" [class.is-loading]="loading">
            <form *ngIf="feed.canPost" class="dashboard-feed-post" (ngSubmit)="post()">
                <input class="form-control" name="text" maxlength="250" [(ngModel)]="text"
                       [placeholder]="label('LBL_DASHBOARD_POST_PLACEHOLDER')"
                       [attr.aria-label]="label('LBL_DASHBOARD_POST_PLACEHOLDER')">
                <button type="submit" class="btn btn-sm btn-main" [disabled]="loading || !text.trim()">{{ label('LBL_DASHBOARD_POST') }}</button>
            </form>
            <ul class="dashboard-feed-list">
                <li *ngFor="let item of feed.items">
                    <ng-container *ngTemplateOutlet="entry; context: {$implicit: item}"></ng-container>
                    <ul *ngIf="item.replies?.length" class="dashboard-feed-replies">
                        <li *ngFor="let reply of item.replies">
                            <ng-container *ngTemplateOutlet="entry; context: {$implicit: reply}"></ng-container>
                        </li>
                    </ul>
                    <form *ngIf="replyTo === item.id" class="dashboard-feed-post dashboard-feed-reply" (ngSubmit)="reply(item)">
                        <input class="form-control" name="reply" maxlength="250" [(ngModel)]="replyText"
                               [attr.aria-label]="label('LBL_DASHBOARD_REPLY')">
                        <button type="submit" class="btn btn-sm btn-main" [disabled]="loading || !replyText.trim()">{{ label('LBL_DASHBOARD_SEND') }}</button>
                    </form>
                    <button *ngIf="replyTo !== item.id" type="button" class="btn btn-link dashboard-feed-action"
                            (click)="replyTo = item.id; replyText = ''">{{ label('LBL_DASHBOARD_REPLY') }}</button>
                </li>
            </ul>
            <p *ngIf="!feed.items.length" class="dashboard-dashlet-message">{{ label('LBL_DASHBOARD_NO_DATA') }}</p>
            <div *ngIf="feed.offsets.prev >= 0 && feed.offsets.current > 0 || feed.offsets.next > 0" class="dashboard-pager">
                <button type="button" class="dashboard-page-btn" [disabled]="!(feed.offsets.prev >= 0 && feed.offsets.current > 0) || loading"
                        [attr.aria-label]="label('LBL_DASHBOARD_PREV')" (click)="load(feed.offsets.prev)">‹</button>
                <button type="button" class="dashboard-page-btn" [disabled]="!(feed.offsets.next > 0) || loading"
                        [attr.aria-label]="label('LBL_DASHBOARD_NEXT')" (click)="load(feed.offsets.next)">›</button>
            </div>
        </div>

        <ng-template #entry let-item>
            <div class="dashboard-feed-entry">
                <div class="dashboard-feed-text" [innerHTML]="item.html"></div>
                <div class="dashboard-feed-meta">
                    <span [title]="item.date">{{ item.age }}</span>
                    <button *ngIf="item.canDelete" type="button" class="btn btn-link dashboard-feed-action"
                            (click)="remove(item)">{{ label('LBL_DASHBOARD_DELETE') }}</button>
                </div>
            </div>
        </ng-template>
    `
})
export class FeedDashletComponent implements OnChanges, OnDestroy {

    @Input() dashlet: DashletData;

    feed: DashletFeed | null = null;
    loading = false;
    error = false;
    text = '';
    replyTo = '';
    replyText = '';

    protected sub: Subscription | null = null;

    constructor(
        protected processService: ProcessService,
        protected languageStore: LanguageStore,
        protected confirmation: ConfirmationModalService,
        protected messages: MessageService,
        protected cdr: ChangeDetectorRef
    ) {
    }

    ngOnChanges(): void {
        this.render(this.dashlet);
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
    }

    label(key: string): string {
        return this.languageStore.getFieldLabel(key, 'home') || key;
    }

    /** Reloads the current page (refresh button of the dashlet). */
    refresh(): void {
        this.load(this.feed?.offsets?.current ?? 0);
    }

    post(): void {
        if (this.text.trim()) {
            this.run({feedAction: 'post', text: this.text}, () => this.text = '');
        }
    }

    reply(item: DashletFeedItem): void {
        if (this.replyText.trim()) {
            this.run({feedAction: 'reply', parentId: item.id, text: this.replyText}, () => {
                this.replyTo = '';
                this.replyText = '';
            });
        }
    }

    remove(item: DashletFeedItem): void {
        this.confirmation.showModal(['LBL_DASHBOARD_DELETE_POST_CONFIRM'], () => {
            this.run({feedAction: 'delete', recordId: item.id});
        });
    }

    load(offset: number): void {
        if (offset < 0) {
            return;
        }

        this.sub?.unsubscribe();
        this.loading = true;
        this.sub = this.processService.submit('dashboard', {action: 'dashlet', id: this.dashlet.id, offset})
            .pipe(take(1))
            .subscribe({
                next: (process: Process) => {
                    const data = process?.data as DashletData;
                    if (process?.status !== 'success' || !data || (data as any).error === true) {
                        this.loading = false;
                        this.error = true;
                        this.cdr.detectChanges();
                        return;
                    }
                    // The error of a previous load does not stay.
                    this.dashlet = {...this.dashlet, ...data, error: data.error, deferred: false};
                    this.render(this.dashlet);
                },
                error: () => {
                    this.loading = false;
                    this.error = true;
                    this.cdr.detectChanges();
                }
            });
    }

    /** A feed action, then the first page again (the new post is on it). */
    protected run(options: { [key: string]: string }, done: () => void = () => {}): void {
        this.loading = true;
        this.processService.submit('dashboard', {action: 'feed', id: this.dashlet.id, ...options}).pipe(take(1)).subscribe({
            next: (process: Process) => {
                if (process?.status !== 'success' || !process.data?.done) {
                    this.messages.addDangerMessageByKey('LBL_ACTION_ERROR');
                } else {
                    done();
                }
                this.load(options.feedAction === 'delete' ? (this.feed?.offsets?.current ?? 0) : 0);
            },
            error: () => {
                this.messages.addDangerMessageByKey('LBL_ACTION_ERROR');
                this.load(this.feed?.offsets?.current ?? 0);
            }
        });
    }

    protected render(dashlet: DashletData): void {
        this.loading = false;
        this.error = !!dashlet?.error;
        this.feed = dashlet?.feed ?? null;
        if (dashlet?.deferred && !this.error) {
            // The content is loaded here, after the dashboard.
            this.load(0);
            return;
        }
        this.cdr.detectChanges();
    }
}
