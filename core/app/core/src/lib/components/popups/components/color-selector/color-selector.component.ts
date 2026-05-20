/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

import {Component, EventEmitter, Input, OnInit} from '@angular/core';
import {KeyValuePipe, NgClass, NgForOf} from "@angular/common";
import {ColorSection} from "./color-selector.model";
import {PopupButtonModule} from "../popup-button/popup-button.module";
import {ButtonInterface} from "../../../../common/components/button/button.model";
import {
    DropdownButtonInterface, DropdownButtonSection,
    DropdownButtonSectionMap
} from "../../../../common/components/button/dropdown-button.model";
import {ButtonModule} from "../../../button/button.module";

@Component({
    selector: 'scrm-color-selector',
    standalone: true,
    imports: [
        PopupButtonModule,
        KeyValuePipe,
        NgClass,
        NgForOf,
        ButtonModule
    ],
    templateUrl: './color-selector.component.html',
})
export class ColorSelectorComponent implements OnInit {

    @Input('config') buttonConfig: ButtonInterface;
    @Input() openStatusEventEmitter: EventEmitter<boolean>;
    colorSelectorConfig: DropdownButtonInterface;

    ngOnInit(): void {

        this.colorSelectorConfig = {...this.buttonConfig};

        const colorsSections = this.getColorSections();
        this.colorSelectorConfig.sections = this.buildSectionButtons(colorsSections);

    }

    protected buildSectionButtons(colorsSections: ColorSection[]): DropdownButtonSectionMap {
        const colorSectionButtons = {} as DropdownButtonSectionMap;
        let index = 0;
        colorsSections.forEach(colorSection => {
            const colorSectionItems = colorSection?.items ?? {};
            const colorKeys = Object.keys(colorSectionItems);
            const section = {
                items: [],
                klass: colorSection?.klass ?? ''
            } as DropdownButtonSection

            colorKeys.forEach((key) => {
                const colorItem = colorSectionItems[key];
                const colorButton = {
                    key: key,
                    titleKey: 'LBL_COLOR_' + (key?.toUpperCase() ?? ''),
                    klass: 'squire-editor-button color-button btn btn-sm',
                    onClick: () => {
                        this.buttonConfig.onClick(colorItem);
                    },
                } as ButtonInterface;

                if (colorItem?.color) {
                    colorButton.style = 'background-color:' + colorItem?.color ?? '';
                }

                if (colorItem?.icon) {
                    colorButton.icon = colorItem.icon;
                }

                if (colorItem?.klass) {
                    colorButton.klass += ' ' + colorItem.klass;
                }

                section.items.push(colorButton);
            });

            colorSectionButtons[index] = section;
            index++;
        });

        return colorSectionButtons;
    }

    private getColorSections(): ColorSection[] {
        return [
            {
                klass: 'pb-1 mb-1 border-bottom color-section',
                items: {
                    'black': {color: '#000000'},
                    'dim-gray': {color: '#696969'},
                    'gray': {color: '#808080'},
                    'dark-gray': {color: '#a9a9a9'},
                    'light-gray': {color: '#d3d3d3'},
                    'white': {color: '#ffffff'},
                    'remove': {icon: 'x-lg', color: null, klass: 'color-remove p-0'},
                }
            },
            {
                klass: 'pb-1 mb-1 border-bottom color-section',
                items: {
                    'red': {color: '#ff0000'},
                    'dark-orange': {color: '#ff8c00'},
                    'yellow': {color: '#ffff00'},
                    'lime': {color: '#00ff00'},
                    'aqua': {color: '#00ffff'},
                    'blue': {color: '#0000ff'},
                    'violet-purple': {color: '#ee82ee'},
                }
            },
            {
                klass: 'color-section',
                items: {
                    'blush': {color: '#fff0f5'},
                    'antique-white': {color: '#faebd7'},
                    'light-yellow': {color: '#ffffe0'},
                    'honeydew': {color: '#f0fff0'},
                    'azure': {color: '#f0ffff'},
                    'alice-blue': {color: '#f0f8ff'},
                    'lavender': {color: '#e6e6fa'},
                }
            },
            {
                klass: 'color-section',
                items: {
                    'salmon': {color: '#ffa07a'},
                    'pale-orange': {color: '#ffcf64'},
                    'pale-yellow': {color: '#fdfd96'},
                    'pale-green': {color: '#98fb98'},
                    'pale-turquoise': {color: '#afeeee'},
                    'light-blue': {color: '#add8e6'},
                    'plum': {color: '#dda0dd'},
                }
            },
            {
                klass: 'color-section',
                items: {
                    'coral': {color: '#ff7f50'},
                    'orange': {color: '#ff7300'},
                    'gold': {color: '#ffd700'},
                    'green-yellow': {color: '#adff2f'},
                    'turquoise': {color: '#40e0d0'},
                    'dodger-blue': {color: '#1e90ff'},
                    'medium-violet-red': {color: '#c71585'},
                }
            },
            {
                klass: 'color-section',
                items: {
                    'firebrick': {color: '#b22222'},
                    'orange-red': {color: '#e1540f'},
                    'goldenrod': {color: '#daa520'},
                    'dark-green-yellow': {color: '#029c02'},
                    'dark-turquoise': {color: '#03a8a8'},
                    'medium-blue': {color: '#0000cd'},
                    'patriarch-purple': {color: '#800080'},
                }
            },
            {
                klass: 'color-section',
                items: {
                    'darker-red': {color: '#7c0000'},
                    'darker-orange-red': {color: '#a84517'},
                    'saddle-brown': {color: '#5e2c08'},
                    'darker-green': {color: '#004d00'},
                    'teal': {color: '#008080'},
                    'navy-blue': {color: '#000080'},
                    'indigo': {color: '#4b0082'},
                }
            }
        ];
    }
}
