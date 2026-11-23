# Build the frontend from source on Windows

Use native Windows x64 Node.js 22 LTS (22.13.0 or newer in the 22.x line) with
Corepack. The build was verified with Node.js 22.23.5 on Linux. The checkout
pins Yarn 4.10.3 in `package.json`; do not install Yarn Classic or use
`npm install` for this build.

Node 22 is in the [Angular 18.2 compatibility range](https://angular.dev/reference/versions).
The [SuiteCRM 8.10.x compatibility matrix](https://docs.suitecrm.com/8.x/admin/compatibility-matrix/)
lists Node `^20.11.1`. Node 20 has reached end of life. Node 22 is the choice for
this source build, not a change to SuiteCRM's published support matrix.

In PowerShell, enter the checkout root and run these commands in order. Stop if
any command fails. Explicit `.cmd` launchers work without changing PowerShell's
script execution policy or enabling global Yarn shims.

```powershell
node.exe --version
corepack.cmd yarn --version
corepack.cmd yarn install --immutable
corepack.cmd yarn merge-angular-json
corepack.cmd yarn build
corepack.cmd yarn build:extension defaultExt
```

Check that the first two commands report a supported Node 22.x and Yarn 4.10.3.
The merge generates the local `angular.json` from the base configuration and
any extension configurations. Rerun it when those configurations change. It
exits nonzero if its dependencies are missing; install them before building.

`build` writes the compiled library to `dist/core` and the deployable shell to
`public/dist`. `build:extension defaultExt` writes production extension files
to `extensions/defaultExt/public` for SuiteCRM's extension asset publishing
step. It requires the compiled library, so run it after `build`.

To check the workspace generator itself after installing dependencies, run:

```powershell
node.exe --test core/yarn/plugins/merge-angular-json.test.cjs
```

## Angular unit tests

After building core, install Google Chrome and run both complete suites:

```powershell
corepack.cmd yarn ng test core --watch=false --browsers=ChromeHeadless
corepack.cmd yarn ng test shell --watch=false --browsers=ChromeHeadless
```

The Chrome launcher detects standard Windows installations. For a nonstandard
installation, set `$env:CHROME_BIN` to the full path of `chrome.exe` first.
Do not add `--include` when verifying the complete suite. Check the final
executed count, failures, and skipped count, not just successful compilation.

Angular's test builder discovers the specs. The core test bootstrap enables
fixture teardown and errors for unknown elements and properties. Its spec-only
TypeScript configuration leaves DI metadata emission to Angular, avoiding
runtime decorator import cycles without changing the production compiler.
