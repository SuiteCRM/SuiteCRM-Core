import {Inject, Injectable, LOCALE_ID} from '@angular/core';
import {FormatOptions, Formatter} from '../formatter.model';
import { FormControlUtils } from '../../record/field/form-control.utils';
import {UserPreferenceStore} from "../../../store/user-preference/user-preference.store";

@Injectable({
    providedIn: 'root'
})
export class TextFormatter implements Formatter {

    private defaultMap: { [key: string]: string } = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        'à': '&agrave;', 'á': '&aacute;', 'â': '&acirc;', 'ã': '&atilde;', 'ä': '&auml;',
        'è': '&egrave;', 'é': '&eacute;', 'ê': '&ecirc;', 'ë': '&euml;',
        'ì': '&igrave;', 'í': '&iacute;', 'î': '&icirc;', 'ï': '&iuml;',
        'ò': '&ograve;', 'ó': '&oacute;', 'ô': '&ocirc;', 'õ': '&otilde;', 'ö': '&ouml;',
        'ù': '&ugrave;', 'ú': '&uacute;', 'û': '&ucirc;', 'ü': '&uuml;',
        'ñ': '&ntilde;', 'Ñ': '&Ntilde;',
        'ç': '&ccedil;', 'Ç': '&Ccedil;',
        '€': '&euro;',
        '¥': '&yen;',
        '£': '&pound;',
        '¢': '&cent;',
        '©': '&copy;',
        '®': '&reg;',
        '™': '&trade;',
        '§': '&sect;',
        '¶': '&para;',
        '•': '&bull;',
        '…': '&hellip;',
        '–': '&ndash;',
        '—': '&mdash;',
        // ... add more characters as needed
    };

    constructor(
        protected preferences: UserPreferenceStore,
        protected formUtils: FormControlUtils,
        @Inject(LOCALE_ID) public locale: string
    ) {}


    /**
     * Converts the value to a format suitable for display to the user.
     * Escapes HTML special characters.
     */
    toUserFormat(value: string, options?: FormatOptions): string {
        // Reverse the map for decoding
        const decodeMap: { [key: string]: string } = Object.entries(this.defaultMap).reduce((acc, [char, entity]) => {
            acc[entity] = char;
            return acc;
        }, {});

        // Create a regex to match all entities in the text
        const entityRegex = new RegExp(Object.keys(decodeMap).join('|'), 'g');

        // Recursively decode until no more encoded entities remain
        let decodedValue = value;
        let previousValue;

        do {
            previousValue = decodedValue;
            decodedValue = decodedValue?.replace(entityRegex, (match) => decodeMap[match] || match);
        } while (decodedValue !== previousValue); // Repeat until no changes

        return decodedValue;
    }

    /**
     * Converts the user input back to the internal format.
     * Decodes HTML special characters.
     */
    toInternalFormat(value): string {
        const formmatedValue = value?.replace(/[<>&"'\u00A0-\uFFFF]/g, (match) => {
            const encoded = this.defaultMap[match];
            return encoded !== undefined ? encoded : match;
        });
        return formmatedValue;
    }

}
