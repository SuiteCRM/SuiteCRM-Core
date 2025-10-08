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

import {ComponentFixture, TestBed, waitForAsync} from '@angular/core/testing';
import {CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {RouterTestingModule} from '@angular/router/testing';

import {ClassicViewUiComponent} from './classic-view.component';
import {ActivatedRoute} from '@angular/router';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {IframeResizeHandlerHandler} from '../../services/iframe-resize-handler.service';
import {AuthService} from '../../../../services/auth/auth.service';
import {IframePageChangeObserver} from '../../services/iframe-page-change-observer.service';

class ClassicViewUiComponentMock extends ClassicViewUiComponent {
    protected buildIframePageChangeObserver(): IframePageChangeObserver {
        return null;
    }

    protected buildIframeResizeHandlerHandler(): IframeResizeHandlerHandler {
        return null;
    }
}

describe('ClassicViewUiComponent', () => {

    let component: ClassicViewUiComponentMock;
    let fixture: ComponentFixture<ClassicViewUiComponentMock>;

    const legacyLink = 'about:blank';
    const route = ({
        data: {legacyUrl: legacyLink},
        snapshot: {
            data: {legacyUrl: legacyLink}
        }
    } as any) as ActivatedRoute;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            schemas: [CUSTOM_ELEMENTS_SCHEMA],
            imports: [
                RouterTestingModule,
                ApolloTestingModule
            ],
            providers: [
                {
                    provide: ActivatedRoute,
                    useValue: route
                },
                {
                    provide: AuthService,
                    useValue: jasmine.createSpyObj('AuthService', ['logout'])
                }
            ],
            declarations: [ClassicViewUiComponentMock]
        })
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(ClassicViewUiComponentMock);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should render', () => {
        expect(component).toBeTruthy();
    });

    it('should have correct legacy url', () => {
        expect(component).toBeTruthy();
        expect(component.url).toEqual(legacyLink);
    });

    it('should contain iframe wrapper', () => {
        const classicElement: HTMLElement = fixture.nativeElement;

        const wrapper = (classicElement.getElementsByClassName('classic-view-container')[0]);

        expect(wrapper).toBeTruthy();
    });

    it('should contain iframe', () => {
        const classicElement: HTMLElement = fixture.nativeElement;
        const wrapper = (classicElement.getElementsByClassName('classic-view-container')[0]);

        const iframe = (wrapper.getElementsByTagName('iframe')[0]);

        expect(iframe).toBeTruthy();
        expect(iframe.src).toEqual(component.url);
    });
});
