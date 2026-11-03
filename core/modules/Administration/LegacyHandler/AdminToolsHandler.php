<?php
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

namespace App\Module\Administration\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The tools pages of the administration for the admin tools view: Repair (legacy
 * Administration&action=Upgrade, its tools in its order), Backups and Diagnostic (their tool).
 * The tools run as the legacy pages run them (their legacy scripts, with the parameters of their
 * links and forms), their output shown as text. The tools of several steps (a confirmation, the
 * SQL of Quick Repair, the records of Remove XSS by batches, the backup once its settings
 * checked) give the next steps with their result.
 */
class AdminToolsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'admin-tools';

    /**
     * The records of Remove XSS saved by step.
     */
    protected const XSS_BATCH = 100;

    /**
     * The modules Remove XSS skips, as the legacy page.
     */
    protected const XSS_HIDDEN = ['Activities', 'Home', 'iFrames', 'Calendar', 'Dashboard'];

    protected const JS_TYPES = ['js_replace' => 'replace', 'js_concat' => 'concat', 'js_mini' => 'mini', 'js_repair' => 'repair'];

    /**
     * The options of the Diagnostic tool, with their label.
     */
    protected const DIAGNOSTIC_OPTIONS = [
        'configphp' => 'LBL_DIAGNOSTIC_CONFIGPHP',
        'custom_dir' => 'LBL_DIAGNOSTIC_CUSTOMDIR',
        'phpinfo' => 'LBL_DIAGNOSTIC_PHPINFO',
        'mysql_dumps' => 'LBL_DIAGNOSTIC_MYSQLDUMPS',
        'mysql_schema' => 'LBL_DIAGNOSTIC_MYSQLSCHEMA',
        'mysql_info' => 'LBL_DIAGNOSTIC_MYSQLINFO',
        'md5' => 'LBL_DIAGNOSTIC_MD5',
        'md5filesmd5' => 'LBL_DIAGNOSTIC_FILESMD5',
        'md5calculated' => 'LBL_DIAGNOSTIC_CALCMD5',
        'beanlistbeanfiles' => 'LBL_DIAGNOSTIC_BLBF',
        'sugarlog' => 'LBL_DIAGNOSTIC_SUITELOG',
        'vardefs' => 'LBL_DIAGNOSTIC_VARDEFS',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The tools of a page (title, description, confirmation, parameters), its title and intro.
     */
    public function getTools(string $page): array
    {
        return $this->inLegacy(function () use ($page): array {
            $tools = [];
            foreach ($this->getDefinitions($page) as $key => $definition) {
                $tool = [
                    'key' => $key,
                    'title' => $this->text($definition[0]),
                    'description' => $this->text($definition[1], false),
                ];
                if (!empty($definition[2])) {
                    // A label key of the Administration strings (confirmation modal of the page).
                    $tool['confirm'] = $definition[2];
                }
                if (!empty($definition[3])) {
                    $tool['params'] = $definition[3];
                }
                $tools[] = $tool;
            }

            return match ($page) {
                'backups' => [
                    'title' => $this->text('LBL_BACKUPS_TITLE'),
                    'intro' => $this->text('LBL_BACKUP_INSTRUCTIONS_1', false) . '<br>' . $this->text('LBL_BACKUP_INSTRUCTIONS_2', false),
                    'tools' => $tools,
                ],
                'diagnostic' => ['title' => $this->text('LBL_DIAGNOSTIC_TITLE'), 'tools' => $tools],
                default => ['title' => $this->text('LBL_UPGRADE_TITLE'), 'tools' => $tools],
            };
        });
    }

    /**
     * Runs a step of a tool of a page (run: its legacy link).
     *
     * @param array<string, mixed> $params
     * @return array{status: string, html: string, sql?: string, exportLabel?: string, links?: array, next?: array, progress?: array}
     */
    public function run(string $page, string $tool, string $step, array $params): array
    {
        return $this->inLegacy(function () use ($page, $tool, $step, $params): array {
            if (!array_key_exists($tool, $this->getDefinitions($page))) {
                throw new \InvalidArgumentException('Unknown tool ' . $tool);
            }

            return match ($tool) {
                'Backups' => $this->runBackups($step, $params),
                'Diagnostic' => $this->runDiagnostic($step, $params),
                'repair' => $this->runQuickRepair($step),
                'RebuildConfig' => $this->runRebuildConfig($step),
                'RebuildSchedulers' => $this->runRebuildSchedulers($step),
                'RebuildSprites' => $this->result($this->capture(static function () {
                    global $mod_strings;
                    if (function_exists('imagecreatetruecolor')) {
                        require_once 'modules/UpgradeWizard/uw_utils.php';
                        rebuildSprites(false);
                    } else {
                        echo $mod_strings['LBL_SPRITES_NOT_SUPPORTED'];
                    }
                })),
                // The root directory sent by the legacy page script (its current directory).
                'js_replace', 'js_concat', 'js_mini', 'js_repair' => $this->result($this->script(
                    'modules/Administration/callJSRepair.php',
                    ['js_admin_repair' => self::JS_TYPES[$tool], 'root_directory' => getcwd()]
                )),
                'RepairFieldCasing' => $this->result($this->script('modules/Administration/RepairFieldCasing.php', ['type' => 'repair'])),
                'install_actions' => $this->result($this->script('modules/ACL/install_actions.php', [])),
                'SyncInboundEmailAccounts' => $this->runSyncInboundEmails($params),
                'RepairXSS' => $this->runRepairXss($step, $params),
                'RepairSeedUsers' => $this->runRepairSeedUsers($step),
                'RepairUtfEncoding' => $this->runRepairUtfEncoding($step, $params),
                'UpgradeAccess' => $this->result($this->script('modules/Administration/UpgradeAccess.php', ['action' => 'UpgradeAccess'])),
                'fulltext' => $this->result($this->script('modules/Administration/RebuildFulltextIndices.php', [])),
                default => $this->result($this->script('modules/Administration/' . $tool . '.php', [])),
            };
        });
    }

    /**
     * The tools of a page: title, description, confirmation (label keys), parameters (fields of
     * the admin settings pages). Backups and Diagnostic hidden by the config are not available,
     * as the legacy pages.
     *
     * @return array<string, array>
     */
    protected function getDefinitions(string $page): array
    {
        global $sugar_config;

        if ($page === 'backups') {
            if (!empty($sugar_config['hide_admin_backup'])) {
                throw new \InvalidArgumentException('Unauthorized access to backups.');
            }

            return ['Backups' => ['LBL_BACKUP_CONFIRM', 'LBL_BACKUP_DIRECTORY_WRITABLE', null, [
                ['name' => 'backup_dir', 'label' => $this->text('LBL_BACKUP_DIRECTORY'), 'type' => 'text'],
                ['name' => 'backup_zip', 'label' => $this->text('LBL_BACKUP_FILENAME'), 'type' => 'text'],
            ]]];
        }
        if ($page === 'diagnostic') {
            if (!empty($sugar_config['hide_admin_diagnostics'])) {
                throw new \InvalidArgumentException('Unauthorized access to diagnostic tool.');
            }

            $database = \DBManagerFactory::getInstance()->dbName;
            $options = [];
            foreach (self::DIAGNOSTIC_OPTIONS as $name => $label) {
                $options[] = [
                    'name' => $name,
                    'label' => (str_starts_with($name, 'mysql_') ? $database . ' - ' : '') . $this->text($label),
                    'type' => 'bool',
                ];
            }

            return ['Diagnostic' => ['LBL_DIAG_EXECUTE_BUTTON', 'LBL_DIAGNOSTIC_DESC', null, $options]];
        }
        if ($page !== 'repair') {
            throw new \InvalidArgumentException('Unknown tools page ' . $page);
        }

        $tools = [];
        if (\DBManagerFactory::getInstance()->supports('fulltext')) {
            $tools['fulltext'] = ['LBL_REPAIR_ORACLE_FULLTEXT', 'LBL_REPAIR_ORACLE_FULLTEXT_DESC'];
        }
        $tools['repair'] = ['LBL_QUICK_REPAIR_AND_REBUILD', 'LBL_QUICK_REPAIR_AND_REBUILD_DESC'];
        $tools['expandDatabase'] = ['LBL_EXPAND_DATABASE_COLUMNS', 'LBL_EXPAND_DATABASE_COLUMNS_DESC'];
        $tools['RepairESIndex'] = ['LBL_REPAIR_ELASTICSEARCH_INDEX', 'LBL_REPAIR_ELASTICSEARCH_INDEX'];
        if (!str_contains((string)($_SERVER['SERVER_SOFTWARE'] ?? ''), 'Microsoft-IIS')) {
            $tools['UpgradeAccess'] = ['LBL_REBUILD_HTACCESS', 'LBL_REBUILD_HTACCESS_DESC'];
        } else {
            $tools['UpgradeIISAccess'] = ['LBL_REBUILD_WEBCONFIG', 'LBL_REBUILD_WEBCONFIG_DESC'];
        }
        $tools['RebuildConfig'] = ['LBL_REBUILD_CONFIG', 'LBL_REBUILD_CONFIG_DESC'];
        $tools['RebuildRelationship'] = ['LBL_REBUILD_REL_TITLE', 'LBL_REBUILD_REL_DESC'];
        $tools['RebuildSchedulers'] = ['LBL_REBUILD_SCHEDULERS_TITLE', 'LBL_REBUILD_SCHEDULERS_DESC_SHORT'];
        $tools['RebuildDashlets'] = ['LBL_REBUILD_DASHLETS_TITLE', 'LBL_REBUILD_DASHLETS_DESC_SHORT'];
        $tools['RebuildJSLang'] = ['LBL_REBUILD_JAVASCRIPT_LANG_TITLE', 'LBL_REBUILD_JAVASCRIPT_LANG_DESC_SHORT'];
        $tools['js_replace'] = ['LBL_REBUILD_JS_FILES_TITLE', 'LBL_REBUILD_JS_FILES_DESC_SHORT', 'WARN_POSSIBLE_JS_OVERWRITE'];
        $tools['js_concat'] = ['LBL_REBUILD_CONCAT_JS_FILES_TITLE', 'LBL_REBUILD_CONCAT_JS_FILES_DESC_SHORT'];
        $tools['js_mini'] = ['LBL_REBUILD_JS_MINI_FILES_TITLE', 'LBL_REBUILD_JS_MINI_FILES_DESC_SHORT', 'WARN_POSSIBLE_JS_OVERWRITE'];
        if (!empty($sugar_config['use_sprites'])) {
            $tools['RebuildSprites'] = ['LBL_REBUILD_SPRITES_TITLE', 'LBL_REBUILD_SPRITES_DESC_SHORT'];
        }
        $tools['js_repair'] = ['LBL_REPAIR_JS_FILES_TITLE', 'LBL_REPAIR_JS_FILES_DESC_SHORT'];
        $tools['RepairFieldCasing'] = ['LBL_REPAIR_FIELD_CASING_TITLE', 'LBL_REPAIR_FIELD_CASING_DESC_SHORT'];
        $tools['install_actions'] = ['LBL_REPAIR_ROLES', 'LBL_REPAIR_ROLES_DESC'];
        $tools['RepairIE'] = ['LBL_REPAIR_IE', 'LBL_REPAIR_IE_DESC'];
        $tools['SyncInboundEmailAccounts'] = ['LBL_SYNC_IE_EMAILS', 'LBL_SYNC_IE_EMAILS_DESC', null, [[
            'name' => 'accounts',
            'label' => $this->text('LBL_EMAIL_SETTINGS_NAME'),
            'type' => 'multiselect',
            'options' => $this->getInboundEmailOptions(),
        ]]];
        $tools['RepairXSS'] = ['LBL_REPAIR_XSS', 'LBL_REPAIRXSS_TITLE', null, [[
            'name' => 'module',
            'label' => strip_tags(str_replace('<br>', ' ', $this->text('LBL_REPAIRXSS_INSTRUCTIONS', false))),
            'type' => 'select',
            'options' => $this->getXssOptions(),
        ]]];
        $tools['RepairActivities'] = ['LBL_REPAIR_ACTIVITIES', 'LBL_REPAIR_ACTIVITIES_DESC'];
        $tools['RepairSeedUsers'] = ['LBL_REPAIR_SEED_USERS_TITLE', 'LBL_REPAIR_SEED_USERS_DESC'];
        $tools['RepairUploadFolder'] = ['LBL_REPAIR_UPLOAD_FOLDER', 'LBL_REPAIR_UPLOAD_FOLDER_DESC'];
        $tools['RepairUtfEncoding'] = ['LBL_REPAIR_UTF_ENCODING', 'LBL_REPAIR_UTF_ENCODING_DESC'];

        return $tools;
    }

    /**
     * Backups of the legacy page: the directory and file checked (the directory created), then
     * the application files zipped in it.
     *
     * @param array<string, mixed> $params
     */
    protected function runBackups(string $step, array $params): array
    {
        require_once 'include/utils/php_zip_utils.php';

        $directory = trim((string)($params['backup_dir'] ?? ''));
        $file = trim((string)($params['backup_zip'] ?? ''));
        $errors = [];
        if (str_contains($directory, 'phar://')) {
            $errors[] = 'LBL_BACKUP_DIRECTORY_WRITABLE';
        } else {
            if ($directory === '') {
                $errors[] = 'LBL_BACKUP_DIRECTORY_ERROR';
            }
            if ($file === '') {
                $errors[] = 'LBL_BACKUP_FILENAME_ERROR';
            }
            if (empty($errors)) {
                if (!is_dir($directory) && !mkdir_recursive($directory)) {
                    $errors[] = 'LBL_BACKUP_DIRECTORY_EXISTS';
                }
                if (!is_writable($directory)) {
                    $errors[] = 'LBL_BACKUP_DIRECTORY_NOT_WRITABLE';
                }
                if (is_file("$directory/$file")) {
                    $errors[] = 'LBL_BACKUP_FILE_EXISTS';
                }
                if (is_dir("$directory/$file")) {
                    $errors[] = 'LBL_BACKUP_FILE_AS_SUB';
                }
            }
        }
        if (!empty($errors)) {
            return ['status' => 'error', 'html' => implode('<br>', array_map(fn (string $error): string => htmlspecialchars($this->text($error, false)), $errors))];
        }

        if ($step !== 'backup') {
            return [
                'status' => 'success',
                'html' => htmlspecialchars($this->text('LBL_BACKUP_CONFIRMED', false)),
                'next' => [['step' => 'backup', 'label' => $this->text('LBL_BACKUP_RUN_BACKUP')]],
            ];
        }

        ini_set('memory_limit', '-1');
        set_time_limit(0);
        // The files that could not be added (as a broken link) are skipped, as the legacy page.
        $warnings = $this->tolerating(static fn () => zip_dir('.', "$directory/$file"));
        clearstatcache();

        return [
            'status' => 'success',
            'html' => htmlspecialchars($this->text('LBL_BACKUP_FILE_STORED', false) . " $directory/$file (" . filesize("$directory/$file") . ' bytes).')
                . implode('', array_map(static fn (string $warning): string => '<br>' . htmlspecialchars($warning), array_unique($warnings))),
        ];
    }

    /**
     * Diagnostic of the legacy page: the chosen information zipped (its download link), then
     * the deletion of the file.
     *
     * @param array<string, mixed> $params
     */
    protected function runDiagnostic(string $step, array $params): array
    {
        if ($step === 'delete') {
            return $this->result($this->script('modules/Administration/DiagnosticDelete.php', [
                'file' => (string)($params['file'] ?? ''),
                'guid' => (string)($params['guid'] ?? ''),
            ]));
        }

        $request = [];
        foreach (array_keys(self::DIAGNOSTIC_OPTIONS) as $name) {
            if (!empty($params[$name])) {
                $request[$name] = 'on';
            }
        }

        $output = $this->diagnosticScript($request);
        $result = $this->result($output);
        if (preg_match('#action=DiagnosticDownload&(?:amp;)?guid=([\w-]+)&(?:amp;)?time=([\w-]+)#', $output, $match)) {
            $result['links'] = [[
                'label' => $this->text('LBL_DIAGNOSTIC_DOWNLOADLINK'),
                'href' => 'legacy/index.php?module=Administration&action=DiagnosticDownload&guid=' . $match[1] . '&time=' . $match[2] . '&to_pdf=1',
            ]];
            $result['next'] = [[
                'step' => 'delete',
                'label' => $this->text('LBL_DIAGNOSTIC_DELETELINK'),
                'params' => ['guid' => $match[1], 'file' => 'diagnostic' . $match[2]],
            ]];
        }

        return $result;
    }

    /**
     * DiagnosticRun.php of the legacy page, its globals declared (its functions use them).
     *
     * @param array<string, string> $request
     */
    protected function diagnosticScript(array $request): string
    {
        $saved = [$_REQUEST, $_POST, $_GET];
        $_GET = $request + ['module' => 'Administration', 'action' => 'DiagnosticRun'];
        $_POST = [];
        $_REQUEST = $_GET;

        try {
            return $this->capture(static function () {
                global $mod_strings, $app_strings, $app_list_strings, $current_user, $sugar_config, $sugar_version,
                       $beanList, $beanFiles, $dictionary, $theme, $sugar_db_version, $sugar_flavor, $cacheDir,
                       $curdatetime, $currentitems, $getDumpsFrom, $progress_bar_percent, $skip_md5_diff, $sod_guid,
                       $totalitems, $totalweight, $current_language, $db;

                include 'modules/Administration/DiagnosticRun.php';
            });
        } finally {
            [$_REQUEST, $_POST, $_GET] = $saved;
        }
    }

    /**
     * Quick Repair and Rebuild of the legacy page (all the modules, the database differences
     * shown), then the execution of these differences: computed again and executed by the legacy
     * repair (not the SQL of the page).
     */
    protected function runQuickRepair(string $step): array
    {
        if ($step === 'execute') {
            $html = $this->script('modules/Administration/repairDatabase.php', ['execute' => true, 'repair_silent' => '1']);

            return $this->result($html . '<h3>' . htmlspecialchars($this->text('LBL_REPAIR_DATABASE_SYNCED')) . '</h3>');
        }

        require_once 'modules/Administration/QuickRepairAndRebuild.php';
        $output = $this->capture(static function () {
            // Its last step clears the Symfony cache, the container of this request: done once
            // its response is sent (shutdown), as the legacy page clears it outside Symfony.
            $repair = new class () extends \RepairAndClear {
                public function clearCoreCache(): void
                {
                    register_shutdown_function(fn () => parent::clearCoreCache());
                }
            };
            $repair->repairAndClearAll(['clearAll'], [translate('LBL_ALL_MODULES')], false, true);
        });

        $result = $this->result($output);
        if (preg_match('#<textarea[^>]*name="sql"[^>]*>(.*?)</textarea>#s', $output, $match) && trim($match[1]) !== '') {
            $result['sql'] = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $result['exportLabel'] = $this->text('LBL_REPAIR_DATABASE_EXPORT');
            $result['next'] = [['step' => 'execute', 'label' => $this->text('LBL_REPAIR_DATABASE_EXECUTE')]];
        }

        return $result;
    }

    protected function runRebuildConfig(string $step): array
    {
        $html = $this->script('modules/Administration/RebuildConfig.php', [], $step === 'perform' ? ['perform_rebuild' => '1'] : []);
        $result = $this->result($html);
        if ($step !== 'perform' && is_writable('config.php')) {
            $result['next'] = [['step' => 'perform', 'label' => $this->text('BTN_REBUILD_CONFIG')]];
        }

        return $result;
    }

    protected function runRebuildSchedulers(string $step): array
    {
        if ($step === 'perform') {
            return $this->result($this->script('modules/Administration/RebuildSchedulers.php', ['perform_rebuild' => 'true']));
        }

        return [
            'status' => 'success',
            'html' => '<p>' . $this->text('LBL_REBUILD_SCHEDULERS_DESC', false) . '</p>',
            'next' => [['step' => 'perform', 'label' => $this->text('LBL_REBUILD')]],
        ];
    }

    /**
     * Sync Inbound Email Accounts of the legacy page: the selected accounts (all when none), its
     * output (or the output file of a sync stopped by an error).
     *
     * @param array<string, mixed> $params
     */
    protected function runSyncInboundEmails(array $params): array
    {
        $accounts = array_values(array_intersect(
            is_array($params['accounts'] ?? null) ? array_map('strval', $params['accounts']) : [],
            array_column($this->getInboundEmailOptions(), 'value')
        ));

        $saved = [$_REQUEST, $_POST, $_GET];
        $_GET = ['module' => 'Administration', 'action' => 'SyncInboundEmailAccounts', 'method' => 'sync', 'ie-sel' => $accounts];
        $_POST = [];
        $_REQUEST = $_GET;

        try {
            $html = $this->capture(function () {
                global $app_strings;

                // The dependencies of the legacy page (SyncInboundEmailAccounts.php), whose sync
                // ends with die() once its output shown: its page stops there instead.
                foreach (['Empty', 'NoMethod', 'InvalidMethodType', 'InvalidSubActionArguments', 'IMapConnection', ''] as $exception) {
                    require_once "modules/Administration/SyncInboundEmailAccounts/SyncInboundEmailAccounts{$exception}Exception.php";
                }
                require_once 'modules/InboundEmail/InboundEmail.php';
                require_once 'modules/Emails/Email.php';
                require_once 'modules/Administration/SyncInboundEmailAccounts/SyncInboundEmailAccountsSubActionHandler.php';
                require_once 'modules/Administration/SyncInboundEmailAccounts/SyncInboundEmailAccountsPage.php';
                require_once 'include/Imap/ImapHandlerFactory.php';

                $done = new \RuntimeException('Sync done');
                try {
                    new class(['app_strings' => $app_strings], $done) extends \SyncInboundEmailAccountsPage {
                        public function __construct(array $includeData, private \RuntimeException $done)
                        {
                            parent::__construct($includeData);
                        }

                        public function showOutput($output)
                        {
                            echo $output;

                            throw $this->done;
                        }
                    };
                } catch (\RuntimeException $exception) {
                    if ($exception !== $done) {
                        throw $exception;
                    }
                }
            });
        } finally {
            [$_REQUEST, $_POST, $_GET] = $saved;
        }

        // The output of a sync stopped by an error (its file not cleaned up).
        $output = \SyncInboundEmailAccountsSubActionHandler::PROCESS_OUTPUT_FILE;
        if (is_file($output)) {
            $html = (string)file_get_contents($output) . $html;
            unlink($output);
        }

        return $this->result($html);
    }

    /**
     * Remove XSS of the legacy page: the records of the module (or all) counted, then saved by
     * batches (saving a record cleans it), as its asynchronous calls.
     *
     * @param array<string, mixed> $params
     */
    protected function runRepairXss(string $step, array $params): array
    {
        $tables = $this->getXssTables((string)($params['module'] ?? ''));
        $total = 0;
        foreach ($tables as $table) {
            $total += (int)\DBManagerFactory::getInstance()->getOne('SELECT count(*) FROM ' . $table['table']);
        }

        if ($step !== 'execute') {
            $result = [
                'status' => 'success',
                'html' => '<p>' . htmlspecialchars($this->text('LBL_REPAIRXSS_COUNT')) . ': ' . $total . '</p>',
            ];
            if ($total > 0) {
                $result['next'] = [['step' => 'execute', 'label' => $this->text('LBL_REPAIR_XSS')]];
            }

            return $result;
        }

        // The records of all the tables, by offset in their concatenation.
        $offset = max(0, (int)($params['offset'] ?? 0));
        $done = $offset;
        $saved = 0;
        $db = \DBManagerFactory::getInstance();
        $start = 0;
        foreach ($tables as $table) {
            $count = (int)$db->getOne('SELECT count(*) FROM ' . $table['table']);
            if ($offset < $start + $count && $saved < self::XSS_BATCH) {
                $from = max(0, $offset - $start);
                $rows = $db->limitQuery('SELECT id FROM ' . $table['table'] . ' ORDER BY id', $from, self::XSS_BATCH - $saved);
                while ($row = $db->fetchByAssoc($rows)) {
                    $bean = new $table['class']();
                    $bean->retrieve($row['id'], true, false);
                    $bean->new_with_id = false;
                    $bean->save();
                    $saved++;
                }
            }
            $start += $count;
        }
        $done += $saved;

        $result = [
            'status' => 'success',
            'html' => '<p>' . htmlspecialchars($this->text('LBL_REPAIRXSS_REPAIRED')) . ': ' . $done . ' / ' . $total . '</p>',
            'progress' => ['done' => $done, 'total' => $total],
        ];
        if ($saved > 0 && $done < $total) {
            $result['next'] = [['step' => 'execute', 'label' => $this->text('LBL_REPAIR_XSS'), 'auto' => true, 'params' => ['offset' => $done]]];
        }

        return $result;
    }

    protected function runRepairSeedUsers(string $step): array
    {
        $html = $this->script('modules/Administration/RepairSeedUsers.php', [], $step === 'toggle' ? ['activate' => $this->getSeedUsersActivate()] : []);
        $activate = $this->getSeedUsersActivate();
        // The form of the legacy page is its button: the step.
        $result = $this->result($html, $step === 'toggle' || $activate === null);
        if ($activate !== null) {
            $result['next'] = [[
                'step' => 'toggle',
                'label' => $this->text($activate === 'true' ? 'LBL_REPAIR_SEED_USERS_ACTIVATE' : 'LBL_REPAIR_SEED_USERS_DECACTIVATE'),
            ]];
        }

        return $result;
    }

    /**
     * Repair UTF encoding of the legacy page: its status, or its warnings and its form (run in
     * the background or at once, tracking tables kept, from a date), then the repair.
     *
     * @param array<string, mixed> $params
     */
    protected function runRepairUtfEncoding(string $step, array $params): array
    {
        $post = [];
        if ($step === 'perform') {
            $post = ['perform_rebuild_utf_encoding' => 'true', 'repairFrom' => (string)($params['repairFrom'] ?? '')];
            if (!empty($params['syncRun'])) {
                $post['syncRun'] = 'true';
            }
            if (!empty($params['keepTrackingTables'])) {
                $post['keepTrackingTables'] = 'true';
            }
        }

        $result = $this->result($this->script('modules/Administration/RepairUtfEncoding.php', [], $post));

        require_once 'include/Services/NormalizeRecords/NormalizeRecords.php';
        $status = \NormalizeRecords::getRepairStatus();
        // The form again while not repaired (as after a date not valid).
        if ($status !== \NormalizeRecords::REPAIR_STATUS_REPAIRED && $status !== \NormalizeRecords::REPAIR_STATUS_IN_PROGRESS) {
            $result['next'] = [[
                'step' => 'perform',
                'label' => $this->text('LBL_SUBMIT'),
                'confirm' => 'LBL_REPAIR_UTF_ENCODING_BACKUP_WARNING',
                'fields' => [
                    ['name' => 'syncRun', 'label' => $this->text('LBL_SYNC_RUN'), 'type' => 'bool'],
                    ['name' => 'keepTrackingTables', 'label' => $this->text('LBL_KEEP_TRACKING_TABLES'), 'type' => 'bool'],
                    ['name' => 'repairFrom', 'label' => $this->text('LBL_REPAIR_FROM_DATE'), 'type' => 'date', 'value' => '2021-04-27'],
                ],
            ]];
        }

        return $result;
    }

    /**
     * The value the seed users form of the legacy page posts (true: activate), null without seed
     * users.
     */
    protected function getSeedUsersActivate(): ?string
    {
        $status = \DBManagerFactory::getInstance()->getOne("SELECT status FROM users WHERE id LIKE 'seed%'");
        if (empty($status)) {
            return null;
        }

        return $status === 'Inactive' ? 'true' : 'false';
    }

    /**
     * The modules of Remove XSS (all, or each but the hidden ones), as the legacy page.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function getXssOptions(): array
    {
        global $app_strings, $moduleList;

        $options = [['value' => '', 'label' => (string)($app_strings['LBL_NONE'] ?? '')], ['value' => 'all', 'label' => '--' . ($app_strings['LBL_TABGROUP_ALL'] ?? 'All') . '--']];
        $modules = $moduleList ?? [];
        sort($modules);
        foreach ($modules as $module) {
            if (!in_array($module, self::XSS_HIDDEN, true)) {
                $options[] = ['value' => (string)$module, 'label' => (string)$module];
            }
        }

        return $options;
    }

    /**
     * The tables (and bean classes) of Remove XSS for a module or all.
     *
     * @return array<int, array{table: string, class: string}>
     */
    protected function getXssTables(string $target): array
    {
        global $moduleList, $beanList, $beanFiles;

        $modules = $target === 'all'
            ? array_values(array_diff($moduleList ?? [], self::XSS_HIDDEN))
            : (in_array($target, $moduleList ?? [], true) ? [$target] : []);
        sort($modules);

        $tables = [];
        foreach ($modules as $module) {
            $class = $beanList[$module] ?? null;
            if ($class === null || !isset($beanFiles[$class]) || !file_exists($beanFiles[$class])) {
                continue;
            }
            require_once $beanFiles[$class];
            $bean = new $class();
            if (!empty($bean->table_name)) {
                $tables[] = ['table' => $bean->table_name, 'class' => $class];
            }
        }

        return $tables;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function getInboundEmailOptions(): array
    {
        $db = \DBManagerFactory::getInstance();
        $result = $db->query("SELECT id, name FROM inbound_email WHERE status='Active' AND deleted = 0 ORDER BY name");
        $options = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $options[] = ['value' => (string)$row['id'], 'label' => html_entity_decode((string)$row['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')];
        }

        return $options;
    }

    /**
     * Runs a legacy script with the request (and post) of its link or form, its output captured.
     *
     * @param array<string, mixed> $request
     * @param array<string, mixed> $post
     */
    protected function script(string $file, array $request, array $post = []): string
    {
        $saved = [$_REQUEST, $_POST, $_GET];
        $_GET = $request + ['module' => 'Administration'];
        $_POST = $post;
        $_REQUEST = $_GET + $_POST;

        try {
            return $this->capture(static function () use ($file) {
                global $mod_strings, $app_strings, $app_list_strings, $current_user, $current_language, $sugar_config,
                       $sugar_version, $beanList, $beanFiles, $moduleList, $timedate, $locale, $dictionary;

                $db = \DBManagerFactory::getInstance();
                include $file;
            });
        } finally {
            [$_REQUEST, $_POST, $_GET] = $saved;
        }
    }

    /**
     * The output of a legacy run, with the Administration strings of its scripts (two buffers:
     * the scripts flush theirs). A script stopped by sugar_die() (as a tool not supported by the
     * database) keeps its output, with the message.
     */
    protected function capture(callable $callback): string
    {
        global $mod_strings, $current_language;

        $mod_strings = return_module_language($current_language, 'Administration');
        set_time_limit(0);
        $level = ob_get_level();
        ob_start();
        ob_start();

        $error = '';
        try {
            $this->tolerating($callback);
        } catch (\Throwable $throwable) {
            $error = $throwable->getMessage();
        } finally {
            $output = '';
            while (ob_get_level() > $level) {
                $output = (string)ob_get_clean() . $output;
            }
        }

        return $output . ($error !== '' ? '<p>' . htmlspecialchars($error) . '</p>' : '');
    }

    /**
     * Runs legacy code as the legacy pages run it: its warnings logged without stopping it (as
     * Symfony in debug mode would), its notices and deprecations ignored.
     *
     * @return string[] the warnings
     */
    protected function tolerating(callable $callback): array
    {
        $warnings = [];
        set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
            if (($level & (E_WARNING | E_USER_WARNING)) && (error_reporting() & $level)) {
                $warnings[] = $message;
                \LoggerManager::getLogger()->warn('Admin tool: ' . $message);
            }

            return true;
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $warnings;
    }

    /**
     * A result of a run: its output as plain HTML (no scripts, forms, styles, attributes), Done
     * without output (unless only its next step is shown).
     */
    protected function result(string $output, bool $done = true): array
    {
        $html = $this->sanitize($output);

        return ['status' => 'success', 'html' => $html !== '' || !$done ? $html : htmlspecialchars($this->text('LBL_DONE', false))];
    }

    protected function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|style|textarea|select|button|noscript)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        // The title of the legacy page, its links back to the administration and its links of
        // the next steps (the page has its own).
        $html = preg_replace('#<div class=[\'"]moduleTitle[\'"]>.*?<div class=[\'"]clear[\'"]>\s*</div>\s*</div>#is', '', $html) ?? '';
        $html = preg_replace('#<a\b[^>]*action=(index|Upgrade|DiagnosticDownload|DiagnosticDelete)\b[^>]*>.*?</a>#is', '', $html) ?? '';
        // The legacy markup made well-formed first (as a quote left open in an attribute of the
        // legacy progress bar), else strip_tags() drops the rest of the output.
        if (trim($html) !== '' && class_exists(\DOMDocument::class)) {
            $document = new \DOMDocument();
            $errors = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            libxml_use_internal_errors($errors);
            // The progress bars of the legacy scripts (updated by their scripts): the page shows
            // the output once done.
            $xpath = new \DOMXPath($document);
            foreach (iterator_to_array($xpath->query('//*[contains(@id, "_progress_bar")]')) as $bar) {
                $bar->parentNode?->removeChild($bar);
            }
            // The fields of the legacy forms with their labels (their row): the page has its own.
            foreach (iterator_to_array($xpath->query('//form//input[not(@type="hidden")]')) as $input) {
                $row = $input;
                while ($row instanceof \DOMElement && $row->nodeName !== 'tr' && $row->nodeName !== 'form'
                    && !preg_match('/(^|\s)row(\s|$)/', $row->getAttribute('class'))) {
                    $row = $row->parentNode;
                }
                if ($row instanceof \DOMElement && $row->nodeName !== 'form' && $row->parentNode !== null) {
                    $row->parentNode->removeChild($row);
                }
            }
            $body = $document->getElementsByTagName('body')->item(0);
            if ($body !== null) {
                $html = '';
                foreach ($body->childNodes as $node) {
                    $html .= $document->saveHTML($node);
                }
            }
        }
        $html = strip_tags($html, '<br><p><b><strong><i><em><ul><ol><li><pre><h1><h2><h3><h4><h5><table><tr><td><th><div><span>');
        $html = preg_replace('#<(/?)(\w+)\b[^>]*>#', '<$1$2>', $html) ?? '';
        $html = preg_replace('#<(h1|h2|h3|h4|h5|p|div|span|td|th)>\s*</\1>#', '', $html) ?? '';
        $html = preg_replace('#>\s+<#', '><', $html) ?? '';
        // The padding of the legacy progress bars (str_repeat(' ', 256)).
        $html = preg_replace('# {10,}#', ' ', $html) ?? '';

        return trim($html);
    }

    protected function text(string $key, bool $trim = true): string
    {
        $text = html_entity_decode((string)translate($key, 'Administration'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $trim ? rtrim($text, ': ') : $text;
    }

    protected function inLegacy(callable $callback): mixed
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
