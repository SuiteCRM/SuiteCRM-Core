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

/**
 * Posts to a legacy ModuleBuilder action as the legacy Studio posts its forms (in its
 * session; to_pdf: its output without the page), for the actions repairing the application: the
 * repair clears the Symfony cache, which a GraphQL request cannot do while it runs (its next
 * services are loaded from that cache). Its output.
 */
export async function postStudioAction(params: { [key: string]: string }): Promise<string> {
    const response = await fetch('legacy/index.php', {
        method: 'POST',
        body: new URLSearchParams({module: 'ModuleBuilder', to_pdf: '1', ...params}),
        credentials: 'same-origin'
    });
    if (!response.ok) {
        throw new Error(`${response.status}`);
    }

    return response.text();
}

/**
 * The center section of a legacy Studio screen answered by an action (AjaxCompose JSON), its
 * content as lines of text; null when it has none.
 */
export function studioActionLines(output: string): string[] | null {
    const start = output.indexOf('{"center"');
    if (start < 0) {
        return null;
    }
    try {
        const content = String(JSON.parse(output.slice(start))?.center?.content ?? '').replace(/<script[\s\S]*?<\/script>/gi, '');
        const doc = new DOMParser().parseFromString(content.replace(/<br\s*\/?>/gi, '\n'), 'text/html');

        return (doc.body.textContent ?? '').split('\n').map(line => line.trim()).filter(line => line !== '');
    } catch (e) {
        return null;
    }
}
