const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const {
  copyFileSync,
  existsSync,
  mkdirSync,
  mkdtempSync,
  readFileSync,
  rmSync,
  symlinkSync,
} = require('node:fs');
const {tmpdir} = require('node:os');
const {dirname, join, resolve} = require('node:path');
const {test} = require('node:test');

const repo = resolve(__dirname, '../../..');

function fixture(t) {
  const cwd = mkdtempSync(join(tmpdir(), 'suitecrm angular '));
  t.after(() => rmSync(cwd, {recursive: true, force: true}));

  for (const file of [
    'package.json',
    '.yarnrc.yml',
    'core/yarn/plugins/merge-angular-json.js',
    'core/angular/base-angular.json',
    'extensions/defaultExt/app/angular.json',
  ]) {
    const target = join(cwd, file);
    mkdirSync(dirname(target), {recursive: true});
    copyFileSync(join(repo, file), target);
  }
  return cwd;
}

function merge(cwd) {
  const corepack = process.platform === 'win32' ? 'corepack.cmd' : 'corepack';
  const result = spawnSync(`${corepack} yarn merge-angular-json`, {
    cwd,
    encoding: 'utf8',
    shell: true,
  });
  assert.ifError(result.error);
  return result;
}

test('merge fails without installed dependencies and does not write a workspace', (t) => {
  const cwd = fixture(t);
  const {status, stderr, stdout} = merge(cwd);
  assert.equal(status, 1, stdout + stderr);
  assert.match(stdout + stderr, /please run 'yarn install'/);
  assert.equal(existsSync(join(cwd, 'angular.json')), false);
});

test('merge builds the core, shell and default extension workspace in a path with spaces', (t) => {
  const cwd = fixture(t);
  const dependencies = join(repo, 'node_modules');
  assert.ok(existsSync(dependencies), 'Run yarn install first');
  symlinkSync(dependencies, join(cwd, 'node_modules'), 'junction');
  const {status, stderr, stdout} = merge(cwd);
  assert.equal(status, 0, stdout + stderr);

  const workspace = JSON.parse(readFileSync(join(cwd, 'angular.json'), 'utf8'));
  assert.deepEqual(Object.keys(workspace.projects).sort(), ['core', 'defaultExt', 'shell']);
  assert.equal(workspace.projects.shell.architect.build.options.outputPath, 'public/dist/');
  assert.equal(workspace.projects.defaultExt.architect.build.options.outputPath, 'extensions/defaultExt/public');
});
