/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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
module.exports = {
  name: `merge-angular-json`,
  factory: internalRequire => {
    const {BaseCommand} = internalRequire(`@yarnpkg/cli`);
    const {xfs} = internalRequire(`@yarnpkg/fslib`);

    let Merger = null;
    let glob = null;
    try {
      Merger = require("json-merger");
      glob = require('glob');
      glob = glob.glob;
    } catch (e) {
    }


    class MergeAngularJson extends BaseCommand {
      static paths = [[`merge-angular-json`]];

      async execute() {

        if (!Merger || !glob) {
          this.context.stdout.write('merge-angular-json: dependencies are missing, please run \'yarn install\' \n');
          return;
        }

        const extensionFiles = await glob('extensions/**/app/angular.json');
        const angularJsonFiles = [
          'core/angular/base-angular.json',
          ...extensionFiles
        ];
        const merged = Merger.mergeFiles(angularJsonFiles);
        await xfs.writeJsonPromise(`angular.json`, merged);
      }
    }

    return {
      commands: [
        MergeAngularJson
      ],
    };
  }
};
