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

import {Component, OnInit} from '@angular/core';
import {Message} from '../../common/types/messages';
import {Observable} from 'rxjs';
import {transition, trigger, useAnimation} from '@angular/animations';
import {fadeIn, fadeOut} from 'ng-animate';
import {LanguageStore, LanguageStringMap} from '../../store/language/language.store';
import {MessageService} from '../../services/message/message.service';


@Component({
    selector: 'scrm-message-ui',
    templateUrl: './message.component.html',
    styleUrls: [],
    animations: [
        trigger('fade', [
            transition(':enter', useAnimation(fadeIn, {
                params: {timing: 0.5, delay: 0}
            })),
            transition(':leave', useAnimation(fadeOut, {
                params: {timing: 0.5, delay: 0}
            })),
        ])
    ]
})
export class MessageUiComponent implements OnInit {

    messages$: Observable<Message[]>;

    appStrings$: Observable<LanguageStringMap>;

    constructor(
        public messageService: MessageService,
        public languages: LanguageStore
    ) {
        this.appStrings$ = languages.appStrings$;
    }

    ngOnInit(): void {
        this.messages$ = this.messageService.messages$;
    }

    close(message: Message): void {
        this.messageService.contains(message, true);
    }
}
