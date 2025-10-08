<?php
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SuiteCRM Ltd.
 * Copyright (C) 2011 - 2024 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */

namespace App\Install\Service;

use Monolog\Logger;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

#[AllowDynamicProperties]
class InstallPreChecks
{
    public const STREAM_NAME = "upload";

    /**
     * @var array
     */
    public array $systemChecks = [];

    /**
     * @var bool
     */
    public bool $errorsFound = false;

    /**
     * @var bool
     */
    public bool $warningsFound = false;

    /**
     * @var string
     */
    public string $xsrfToken = '';

    /**
     * @var array
     */
    public array $cookies = [];
    /**
     * @var Logger
     */
    public $log;

    public function __construct($log)
    {
        $this->log = $log;
    }

    public function showMissingPermissionsPage($path): void
    {
        $sugar_config = $this->getConfigValues();
        $this->loadModStrings();

        $cssFile = $this->getCssFile();

        $loader = new FilesystemLoader(__DIR__ . '/../Resources');
        $twig = new Environment($loader);
        $template = $twig->load('installer_missing_permissions.html.twig');
        echo $template->render([
            'cssFile' => $cssFile,
            'path' => $path,
            'mod_strings' => $this->modStrings
        ]);
        return;
    }

    public function setupTwigTemplate(): void
    {

        $sugar_config = $this->getConfigValues();
        $this->loadModStrings();

        $cssFile = $this->getCssFile();

        if (file_exists('legacy/config.php') && ($sugar_config['installer_locked'] ?? false) === true) {
            $loader = new FilesystemLoader(__DIR__ . '/../Resources');
            $twig = new Environment($loader);
            $template = $twig->load('installer_locked.html.twig');
            echo $template->render([
                'cssFile' => $cssFile,
                'mod_strings' => $this->modStrings
            ]);
            return;
        }

        $path = realpath('./');
        $loader = new FilesystemLoader(__DIR__ . '/../Resources');
        $twig = new Environment($loader);
        $template = $twig->load('install-prechecks.html.twig');

        $this->requiredInstallChecks();

        $this->optionalInstallChecks();

        echo $template->render([
            'path' => $path,
            'systemChecks' => $this->systemChecks,
            'errorsFound' => $this->errorsFound,
            'warningsFound' => $this->warningsFound,
            'mod_strings' => $this->modStrings,
            'cssFile' => $cssFile
        ]);
    }


    private function requiredInstallChecks(): void
    {

        $labels = [];
        $results = [];

        $this->getLanguageStrings();

        $this->runServerConfigurationCheck($labels, $results);

        $this->runPHPChecks($labels, $results);

        $this->runPermissionChecks($labels, $results);
    }

    /**
     * @param string $sys_php_version
     * @param string $min_php_version
     * @param string $rec_php_version
     * @return int
     */
    function checkPhpVersion(string $sys_php_version = '', string $min_php_version = '', string $rec_php_version = ''): int
    {
        require __DIR__ . '/../../../../public/legacy/php_version.php';

        $this->log->info('Checking PHP Version');

        if ($sys_php_version === '') {
            $sys_php_version = constant('PHP_VERSION');
        }
        if ($min_php_version === '') {
            $min_php_version = constant('SUITECRM_PHP_MIN_VERSION');
        }
        if ($rec_php_version === '') {
            $rec_php_version = constant('SUITECRM_PHP_REC_VERSION');
        }

        if (version_compare($sys_php_version, $min_php_version, '<') === true) {
            $this->log->error('PHP Version Incompatible, Minimum Version Required:' . $min_php_version . 'Your Version: ' . $sys_php_version);
            return -1;
        }

        if (version_compare($sys_php_version, $rec_php_version, '<') === true) {
            $this->log->info('PHP Version Compatible:' . $sys_php_version);
            return 0;
        }
        $this->log->info('PHP Version Compatible:' . $sys_php_version);
        return 1;
    }

    function checkMainPage(string $baseUrl = ''): array
    {
        $this->loadModStrings();
        $this->log->info('Running curl for SuiteCRM Main Page');
        $ch = curl_init();
        $timeout = 5;
        $logFile = __DIR__ . '/../../../../logs/install.log';
        $checkFile = __DIR__ . '/../../../../.curl_check_main_page';

        file_put_contents($checkFile, 'running');

        $output = [
            'result' => '',
            'errors' => [],
        ];

        if (empty($baseUrl)) {

            $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
            $https = $_SERVER['HTTPS'] ?? '';

            if (!empty($https) && ($https == 'on' || $https['HTTPS'] == 1) ||
                !empty($proto) && $proto == 'https') {
                $protocol = 'https://';
            } elseif (($_SERVER['REQUEST_SCHEME'] ?? 'https') === 'https') {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }

            $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . ($_SERVER['BASE'] ?? '');
        }

        $baseUrl = rtrim($baseUrl, '/');
        $baseUrl .= '/';
        curl_setopt($ch, CURLOPT_URL, $baseUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        ob_start();
        $path = 'php://temp';
        $streamVerboseHandle = fopen($path, 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);

        $headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        $this->log->info('Running curl to get SuiteCRM Instance main page.');

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = 'cURL error (' . curl_errno($ch) . '): ' . curl_error($ch);

            return $this->outputError($streamVerboseHandle, $error, $logFile, $checkFile, $baseUrl, $result);
        }

        if (!str_contains($result, '<title>SuiteCRM</title>')) {
            $error = $this->modStrings['LBL_NOT_A_VALID_SUITECRM_PAGE'] ?? '';

            return $this->outputError($streamVerboseHandle, $error, $logFile, $checkFile,  $baseUrl, $result);
        }

        if (empty($headers['set-cookie'])) {
            $error = $this->modStrings['LBL_NOT_COOKIE_OR_TOKEN'] ?? '';

            return $this->outputError($streamVerboseHandle, $error, $logFile, $checkFile,  $baseUrl, $result);
        }

        foreach ($headers['set-cookie'] as $cookie) {
            if (str_contains($cookie, 'XSRF-TOKEN')) {
                $tokenCol = (explode(';', $cookie) ?? [])[0] ?? '';
                $this->xsrfToken = str_replace('XSRF-TOKEN=', '', $tokenCol);
            }
        }

        $this->cookies = $headers['set-cookie'];

        curl_close($ch);

        $this->log->info('Successfully got SuiteCRM instance main page');

        file_put_contents($logFile, stream_get_contents($streamVerboseHandle), FILE_APPEND);

        $output['result'] = $this->modStrings['LBL_CHECKSYS_OK'] ?? 'OK';
        fclose($streamVerboseHandle);

        $debug = ob_get_clean();
        $this->log->info($debug);
        return $output;
    }

    function outputError($streamVerboseHandle, $error, $logFile, $checkFile, $baseUrl, $result): array
    {

        $modStrings = $this->getLanguageStrings();

        $output = [];


        file_put_contents($logFile, stream_get_contents($streamVerboseHandle), FILE_APPEND);
        rewind($streamVerboseHandle);
        if (stream_get_contents($streamVerboseHandle) !== false && !empty(stream_get_contents($streamVerboseHandle))) {
            $this->log->error(stream_get_contents($streamVerboseHandle));
            $output['errors'][] = stream_get_contents($streamVerboseHandle);
        }
        fclose($streamVerboseHandle);
        $debug = ob_get_clean();
        $this->log->error($debug);
        $output['errors'][] = $error;
        $this->log->error($error);
        $output['errors'][] = 'The url used for the call was: ' . $baseUrl;
        $this->log->error('The url used for the call was: ' . $baseUrl);
        $output['errors'][] = 'The result of the call was: ';
        $this->log->error('The result of the call was: ');

        if (!empty($result)) {
            $output['errors'][] = $result;
            $this->log->error($result);
            return $output;
        }

        if (file_exists($checkFile)) {
            unlink($checkFile);
        }

        $output['errors'][] = $modStrings['LBL_EMPTY'];
        $this->log->error($modStrings['LBL_EMPTY']);
        return $output;
    }

    /**
     * @return array
     */
    function checkGraphQlAPI(string $baseUrl = ''): array
    {
        $this->loadModStrings();

        $this->log->info('Running curl for Api');
        $ch = curl_init();
        $timeout = 5;
        $logFile = __DIR__ . '/../../../../logs/install.log';
        $checkFile = __DIR__ . '/../../../../.curl_check_main_page';

        if (!file_exists($checkFile)){
            file_put_contents($checkFile, 'running');
        }

        $output = [
            'result' => '',
            'errors' => []
        ];

        if (empty($baseUrl)) {

            $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
            $https = $_SERVER['HTTPS'] ?? '';

            if (!empty($https) && ($https == 'on' || $https['HTTPS'] == 1) ||
                !empty($proto) && $proto == 'https') {
                $protocol = 'https://';
            } elseif (($_SERVER['REQUEST_SCHEME'] ?? 'https') === 'https') {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }

            $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . ($_SERVER['BASE'] ?? '');
        }

        $baseUrl = rtrim($baseUrl, '/');
        $baseUrl .= '/';
        $apiUrl = $baseUrl . 'api/graphql';
        $systemConfigApiQuery = '{"operationName":"systemConfigs","variables":{},"query":"query systemConfigs {\n  systemConfigs {\n    edges {\n      node {\n        id\n        _id\n        value\n        items\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}';

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $systemConfigApiQuery);
        curl_setopt($ch, CURLOPT_VERBOSE, true);


        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Xsrf-Token: ' . $this->xsrfToken;
        $cookieHeader = 'Cookie: ';

        foreach ($this->cookies as $cookie) {
            $cookie = (explode(';', $cookie) ?? [])[0] ?? '';
            $cookieHeader .= $cookie . ';';
        }

        $headers[] = $cookieHeader;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        ob_start();
        $path = 'php://temp';
        $streamVerboseHandle = fopen($path, 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);


        $this->log->info('Calling Graphql api');

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = 'cURL error (' . curl_errno($ch) . '): ' . curl_error($ch);

            return $this->outputError($streamVerboseHandle, $error, $logFile, $checkFile,  $apiUrl, $result);
        }

        $resultJson = json_decode($result, true);

        if (empty($resultJson)) {
            $error = $this->modStrings['LBL_CURL_JSON_ERROR'] ?? '';

            return $this->outputError($streamVerboseHandle, $error, $logFile, $checkFile,  $apiUrl, $result);
        }

        if (empty($resultJson['data']['systemConfigs'])) {
            $error = $this->modStrings['LBL_UNABLE_TO_FIND_SYSTEM_CONFIGS'] ?? '';

            return $this->outputError($streamVerboseHandle, $error, $logFile, $checkFile,  $apiUrl, $result);
        }

        if (file_exists($checkFile)) {
            unlink($checkFile);
        }

        curl_close($ch);

        file_put_contents($logFile, stream_get_contents($streamVerboseHandle, -1, 0), FILE_APPEND);
        $output['result'] = $this->modStrings['LBL_CHECKSYS_OK'] ?? 'OK';
        fclose($streamVerboseHandle);
        $debug = ob_get_clean();
        $this->log->info($debug);
        return $output;
    }

    public function getLanguageStrings(): array
    {

        $mod_strings = [];
        $enUsStrings = [];
        $lang = 'en_us';

        $sugar_config = $this->getConfigValues();
        $configOverride = $this->getConfigOverrideValues();

        $enUsLangPack = __DIR__ . '/../../../../public/legacy/install/language/' . $lang . '.lang.php';

        if (is_file($enUsLangPack)) {
            include($enUsLangPack);
            $enUsStrings = $mod_strings;
        }

        if (!empty($sugarConfig['default_language'])) {
            $lang = $sugarConfig['default_language'];
        }

        if (!empty($configOverride['default_language'])) {
            $lang = $configOverride['default_language'];
        }

        $langPack = __DIR__ . '/../../../../public/legacy/install/language/' . $lang . '.lang.php';

        if (($langPack !== $enUsLangPack) && file_exists($langPack)) {
            include($langPack);
            $mod_strings = array_merge($enUsStrings, $mod_strings);
        }


        return $mod_strings;
    }

    public function getConfigValues(): array
    {
        $sugar_config = [];

        $configFile = __DIR__ . '/../../../../public/legacy/config.php';

        if (file_exists($configFile)) {
            include($configFile);
        }

        return $sugar_config;
    }

    public function getConfigOverrideValues(): array
    {
        $sugar_config = [];

        $configOverrideFile = __DIR__ . '/../../../../public/legacy/config_override.php';

        if (file_exists($configOverrideFile)) {
            include($configOverrideFile);
        }

        return $sugar_config;
    }

    private function runPHPChecks($labels, $results): void
    {
        $this->loadModStrings();

        $key = $this->modStrings['LBL_PHP_CHECKS'];

        $this->log->info('Starting PHP Checks');

        $results[] = $this->checkSystemPhpVersion($labels);

        $results[] = $this->checkMemoryLimit($labels);

        $results[] = $this->checkAllowsStream($labels);

        $this->addChecks($key, $labels, $results);
    }

    private function checkMemoryLimit(&$labels): array
    {
        $this->loadModStrings();

        $labels[] = $this->modStrings['LBL_CHECKSYS_MEM'];

        $results = [
            'result' => '',
            'errors' => []
        ];

        $this->log->info('Checking PHP Memory Limit');

        $memoryLimit = ini_get('memory_limit');

        if (empty($memoryLimit)) {
            $memoryLimit = '-1';
        }

        if (!defined('SUGARCRM_MIN_MEM')) {
            define('SUGARCRM_MIN_MEM', 256 * 1024 * 1024);
        }

        $sugarMinMemory = constant('SUGARCRM_MIN_MEM');

        if ($memoryLimit === '-1') {
            $this->log->info('Memory is set to Unlimited');
            $results['result'] = $this->modStrings['LBL_CHECKSYS_MEM_UNLIMITED'];
            return $results;
        }

        preg_match('/^\s*([0-9.]+)\s*([KMGTPE])B?\s*$/i', $memoryLimit, $matches);
        $num = (float)$matches[1];

        switch (strtoupper($matches[2])) {
            case 'G':
                $num = $num * 1024;
            // no break
            case 'M':
                $num = $num * 1024;
            // no break
            case 'K':
                $num = $num * 1024;
        }

        if ((int)$num < (int)$sugarMinMemory) {
            $minMemoryInMegs = constant('SUGARCRM_MIN_MEM') / (1024 * 1024);
            $error = $this->modStrings['LBL_PHP_MEM_1'] . $memoryLimit . $this->modStrings['LBL_PHP_MEM_2'] . $minMemoryInMegs . $this->modStrings['LBL_PHP_MEM_3'];
            $results['errors'][] = $this->modStrings['LBL_CHECK_FAILED'] . $error;
            return $results;
        }

        $this->log->info('PHP Memory Limit is above minimum.');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function checkAllowsStream(&$labels): array
    {
        $this->loadModStrings();

        $labels[] = $this->modStrings['LBL_STREAM'];

        $results = [
            'result' => '',
            'errors' => []
        ];

        $this->log->info('Checking does Sushosin allow to use upload');

        if ($this->getSuhosinStatus() === true || (str_contains(ini_get('suhosin.perdir'), 'e')
                && !str_contains((string)$_SERVER["SERVER_SOFTWARE"], 'Microsoft-IIS'))) {
            $this->log->info('Sushosin allows use of Upload');
            $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
            return $results;
        }

        $this->log->error($this->modStrings['ERR_SUHOSIN']);
        $results['errors'][] = $this->modStrings['ERR_SUHOSIN'];
        return $results;
    }

    public function getSuhosinStatus(): bool
    {

        if (!extension_loaded('suhosin')) {
            return true;
        }

        $configuration = ini_get_all('suhosin', false);

        if ($configuration['suhosin.simulation']) {
            return true;
        }

        $streams = $configuration['suhosin.executor.include.whitelist'];

        if ($streams != '') {
            $streams = explode(',', $streams);
            foreach ($streams as $stream) {
                $stream = explode('://', $stream, 2);
                if (count($stream) == 1) {
                    if ($stream[0] == self::STREAM_NAME) {
                        return true;
                    }
                } elseif ($stream[1] == '' && $stream[0] == self::STREAM_NAME) {
                    return true;
                }
            }
            $this->log->error('Stream ' . self::STREAM_NAME . ' is not listed in suhosin.executor.include.whitelist and blocked because of it');

            return false;
        }

        $streams = $configuration['suhosin.executor.include.blacklist'];
        if ($streams != '') {
            $streams = explode(',', $streams);
            foreach ($streams as $stream) {
                $stream = explode('://', $stream, 2);
                if ($stream[0] == self::STREAM_NAME) {
                    $this->log->error('Stream ' . self::STREAM_NAME . 'is listed in suhosin.executor.include.blacklist and blocked because of it');

                    return false;
                }
            }

            return true;
        }

        $this->log->error('Suhosin blocks all streams, please define ' . self::STREAM_NAME . ' stream in suhosin.executor.include.whitelist');

        return false;
    }

    private function runPermissionChecks($labels, $results): void
    {
        $this->loadModStrings();

        $key = $this->modStrings['LBL_PERMISSION_CHECKS'];

        $this->log->info('Running Permission Checks');

        $results[] = $this->isRootWritable($labels);

        $results[] = $this->isWritableCustomDir($labels);

        $results[] = $this->isWritableUploadDir($labels);

        $results[] = $this->isWritableLegacyCacheSubDir($labels);

        $results[] = $this->isWritableConfigFile($labels);

        $results[] = $this->checkMbStringsModule($labels);

        $results[] = $this->isWritableSubDirFiles($labels);


        $results[] = $this->isWritableLogsFolder($labels);

        $results[] = $this->isWritableCacheFolder($labels);

        $results[] = $this->isExtensionsWritable($labels);

        $results[] = $this->canTouchEnv($labels);

        $results[] = $this->isSecretsWritable($labels);

        $this->addChecks($key, $labels, $results);

    }

    private function isWritableCustomDir(&$labels): array
    {
        $this->loadModStrings();

        $this->log->info('Checking if Custom Dir is writable');

        $labels[] = $this->modStrings['LBL_CHECKSYS_CUSTOM'];

        $results = [
            'result' => '',
            'errors' => [],
        ];

        if (!is_writable('legacy/custom')) {
            $this->log->error($this->modStrings['ERR_CHECKSYS_CUSTOM_NOT_WRITABLE'] . ' Path Checked: legacy/custom');
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_CUSTOM_NOT_WRITABLE'] . ' Path Checked: legacy/custom';
            return $results;
        }

        $this->log->info('Custom Dir is writable');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function isWritableUploadDir(&$labels): array
    {
        $this->loadModStrings();

        $this->log->info('Checking if Upload Dir is Writable');

        $labels[] = $this->modStrings['LBL_CHECKSYS_UPLOAD'];

        $results = [
            'result' => '',
            'errors' => [],
        ];

        if (!is_writable('legacy/upload')) {
            $results['errors'][] = $this->modStrings['LBL_CHECK_FAILED'] . 'legacy/upload not writable. Please run appropriate file permissions to resolve.';
            $this->log->error($this->modStrings['ERR_CHECKSYS_NOT_WRITABLE']);
            $this->log->error($this->modStrings['LBL_CHECK_FAILED'] . 'legacy/upload not writable.');
            return $results;
        }

        $this->log->info('Upload Dir is writable');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function isWritableLegacyCacheSubDir(&$labels): array
    {
        $this->loadModStrings();

        $this->log->info('Checking Legacy Cache Sub Dirs are writable');

        $labels[] = $this->modStrings['LBL_CHECKSYS_LEGACY_CACHE'];

        $results = [
            'result' => '',
            'errors' => [],
        ];

        $cacheFiles = [
            '',
            'images',
            'layout',
            'pdf',
            'xml',
            'include/javascript'
        ];

        $fileList = '';

        foreach ($cacheFiles as $cacheFile) {

            $this->log->info('Checking if ' . $cacheFile . ' is writable');

            $dirname = "legacy/cache/$cacheFile";
            $isWritable = true;

            if ((is_dir($dirname))) {
                $isWritable = is_writable($dirname);
            }
            if (!$isWritable) {
                $fileList .= '<br>' . getcwd() . "/$dirname";
                $results['errors'][] = 'legacy/cache/' . $cacheFile . ' is Not Writeable.';
                $this->log->error($cacheFile . ' is Not Writeable');
            }
        }

        if (strlen($fileList) > 0) {
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_FILES_NOT_WRITABLE'];
            return $results;
        }
        $this->log->info('All Cache Sub Dirs writable');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function checkMbStringsModule(&$labels): array
    {
        $this->loadModStrings();
        $labels[] = $this->modStrings['LBL_CHECKSYS_MBSTRING'];

        $results = [
            'result' => '',
            'errors' => [],
        ];

        $this->log->info('Checking MB Module Strings');

        if (!function_exists('mb_strlen')) {
            $this->log->error($this->modStrings['ERR_CHECKSYS_MBSTRING']);
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_MBSTRING'];
            return $results;
        }

        $this->log->info('mbstrings found');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function isWritableConfigFile(&$labels): array
    {
        $this->loadModStrings();
        $labels[] = $this->modStrings['LBL_CHECKSYS_CONFIG'];

        $results = [
            'result' => '',
            'errors' => [],
        ];

        $this->log->info('Checking if config.php is writable');

        if (!is_file('legacy/config.php')) {
            $this->log->error($this->modStrings['ERR_CHECKSYS_CONFIG_NOT_FOUND'] . 'Path Checked: legacy/config.php');
            $results['result'] = $this->modStrings['ERR_CHECKSYS_CONFIG_NOT_FOUND'];
            return $results;
        }

        if (!is_writable('legacy/config.php')) {
            $this->log->error($this->modStrings['ERR_CHECKSYS_CONFIG_NOT_WRITABLE'] . 'Path Checked: legacy/config.php');
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_CONFIG_NOT_WRITABLE'] . ' Path Checked: legacy/config.php';
            return $results;
        }

        $this->log->info('config.php is writable');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function isWritableSubDirFiles(&$labels): array
    {
        $this->loadModStrings();

        $labels[] = $this->modStrings['LBL_CHECKSYS_MODULE'];

        $results = [
            'result' => '',
            'errors' => [],
        ];

        $this->log->info('Checking if Sub Dir files are writable');

        $_SESSION['unwriteable_module_files'] = [];

        $passedWrite = is_writable('legacy/modules');
        if (isset($_SESSION['unwriteable_module_files']['failed']) && $_SESSION['unwriteable_module_files']['failed']) {
            $passedWrite = false;
        }

        if (!$passedWrite) {
            $results['errors'][] = $this->modStrings['LBL_UNWRITABLE_SUB_DIR'];
            if (isset($_SESSION['unwriteable_module_files']['failed'])) {
                $results['errors'][] = $this->modStrings['ERR_CHECKSYS_NOT_WRITABLE'];
                $results['errors'][] .= '' . $this->modStrings['LBL_CHECKSYS_FIX_MODULE_FILES'];
                foreach ($_SESSION['unwriteable_module_files'] as $key => $file) {
                    if ($key !== '.' && $key !== 'failed') {
                        $results['errors'][] .= '<br>' . $file;
                    }

                    $this->log->error($file . ' is not writable');
                }
            }
        } else {
            $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        }

        return $results;

    }

    private function checkXMLParsing(&$labels): array
    {
        $this->loadModStrings();

        $results = [
            'result' => '',
            'errors' => []
        ];

        $labels[] = $this->modStrings['LBL_CHECKSYS_XML'];

        $this->log->info('Checking XML Parsing');

        if (!function_exists('xml_parser_create')) {
            $this->log->error($this->modStrings['LBL_CHECKSYS_XML_NOT_AVAILABLE']);
            $results['errors'][] = $this->modStrings['LBL_CHECKSYS_XML_NOT_AVAILABLE'];
            return $results;
        }

        $this->log->info('XML Parser Libraries Found');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function checkRequiredModulesInExtensions(&$labels, &$results): void
    {
        $this->loadModStrings();

        $this->log->info('Checking required loaded extensions');

        $modules = [
            'intl',
            'curl',
            'json',
            'gd',
            'mbstring',
            'mysqli',
            'pdo_mysql',
            'openssl',
            'soap',
            'xml',
            'zip',
        ];

        $loadedExtensions = get_loaded_extensions();

        foreach ($modules as $module) {

            $result = [
                'result' => '',
                'errors' => []
            ];

            $labels[] = $this->modStrings['LBL_CHECKSYS_' . strtoupper($module) . '_EXTENSIONS'];
            $this->log->info('Checking if ' . $module . ' exists in loaded extensions');
            if (!in_array($module, $loadedExtensions)) {
                $this->log->error($module . 'not found in extensions.');
                $result['errors'][] = $this->modStrings['ERR_CHECKSYS_' . strtoupper($module)] ?? $this->modStrings['LBL_CHECKSYS_' . strtoupper($module) . '_NOT_AVAILABLE'];
                $results[] = $result;
                continue;
            }
            $this->log->info($module . ' found in loaded extensions');
            $result['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
            $results[] = $result;
        }

    }

    private function checkPCRELibrary(&$labels): array
    {
        $this->loadModStrings();

        $results = [
            'result' => '',
            'errors' => []
        ];

        $labels[] = $this->modStrings['LBL_CHECKSYS_PCRE'];

        $this->log->info('Checking PCRE Library');

        if (!defined('PCRE_VERSION')) {
            $this->log->error($this->modStrings['ERR_CHECKSYS_PCRE']);
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_PCRE'];
            return $results;
        }

        if (version_compare(PCRE_VERSION, '7.0') < 0) {
            $this->log->error($this->modStrings['ERR_CHECKSYS_PCRE_VER']);
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_PCRE_VER'];
            return $results;
        }

        $this->log->info('PCRE Library Exists');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];

        return $results;
    }

    private function checkSpriteSupport(&$labels): array
    {
        $this->loadModStrings();

        $results = [
            'result' => '',
            'errors' => []
        ];

        $labels[] = $this->modStrings['LBL_SPRITE_SUPPORT'];
        $this->log->info('Checking for GD Library');

        if (!function_exists('imagecreatetruecolor')) {
            $this->log->error($this->modStrings['ERROR_SPRITE_SUPPORT']);
            $results['errors'][] = $this->modStrings['ERROR_SPRITE_SUPPORT'];
            return $results;
        }

        $this->log->info('GD Library Found');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];

        return $results;
    }

    private function checkUploadFileSize(&$labels): array
    {
        $this->loadModStrings();

        $results = [
            'result' => '',
            'errors' => []
        ];

        $labels[] = $this->modStrings['LBL_UPLOAD_MAX_FILESIZE_TITLE'];

        $this->log->info('Checking Upload File Size');

        $uploadMaxFileSize = ini_get('upload_max_filesize');
        $this->log->info('Upload File Size:' . $uploadMaxFileSize);
        $uploadMaxFileSizeBytes = $this->returnBytes($uploadMaxFileSize);

        if (!defined('SUGARCRM_MIN_UPLOAD_MAX_FILESIZE_BYTES')) {
            define('SUGARCRM_MIN_UPLOAD_MAX_FILESIZE_BYTES', 6 * 1024 * 1024);
        }

        if (!($uploadMaxFileSizeBytes >= constant('SUGARCRM_MIN_UPLOAD_MAX_FILESIZE_BYTES'))) {
            $this->log->error($this->modStrings['ERR_UPLOAD_MAX_FILESIZE']);
            $results['errors'][] = $this->modStrings['LBL_CHECK_FAILED'] . $this->modStrings['ERR_UPLOAD_MAX_FILESIZE'] . '. Currently yours is: ' . $uploadMaxFileSize;
            return $results;
        }

        $this->log->info('Upload File Size more than ' . constant('SUGARCRM_MIN_UPLOAD_MAX_FILESIZE_BYTES'));
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function returnBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = preg_replace("/[^0-9,.]/", "", $val);

        switch ($last) {
            case 'g':
                $val *= 1024;
            // no break
            case 'm':
                $val *= 1024;
            // no break
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    private function runServerConfigurationCheck(array $labels, array $results): void
    {
        $this->loadModStrings();

        $key = $this->modStrings['LBL_SERVER_CHECKS'];

        $this->log->info('Starting Server Checks');

        $results[] = $this->checkXMLParsing($labels);

        $results[] = $this->checkUploadFileSize($labels);

        $results[] = $this->checkPCRELibrary($labels);

        $results[] = $this->checkSpriteSupport($labels);

        $this->checkRequiredModulesInExtensions($labels, $results);

        $this->addChecks($key, $labels, $results);
    }

    private function checkSystemPhpVersion(&$labels): array
    {
        $this->loadModStrings();

        $labels[] = $this->modStrings['LBL_CHECKSYS_PHPVER'];

        $results = [
            'result' => '',
            'errors' => []
        ];

        if ($this->checkPhpVersion() === -1) {
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_PHP_INVALID_VER'] . constant('PHP_VERSION');
            return $results;
        }

        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
        return $results;
    }

    private function addChecks(string $key, $labels, $results, $optional = false): void
    {
        $this->loadModStrings();

        $this->systemChecks[$key] = [
            'label' => '',
            'checks' => [
            ]
        ];

        foreach ($labels as $i => $label) {

            $result = $results[$i] ?? '';
            $this->systemChecks[$key]['label'] = $key;
            $this->systemChecks[$key]['checks'][$label]['label'] = $label;
            $this->systemChecks[$key]['checks'][$label]['result'] = $result['result'] ?? '';
            if ($optional) {
                $this->systemChecks[$key]['checks'][$label]['warnings'] = $result['errors'];
                continue;
            }
            $this->systemChecks[$key]['checks'][$label]['errors'] = $result['errors'] ?? [];

        }

        foreach ($results as $result) {

            if (isset($result['result'])
                && $result['result'] !== $this->modStrings['LBL_CHECKSYS_OK']
                && $result['result'] !== $this->modStrings['LBL_CHECKSYS_MEM_UNLIMITED']
                && $result['result'] !== $this->modStrings['ERR_CHECKSYS_CONFIG_NOT_FOUND']
                && $optional !== true
            ) {
                $this->errorsFound = true;
            }

            if ($optional) {
                if ($result['errors']) {
                    $this->warningsFound = true;
                }
            }
        }

    }

    private function optionalInstallChecks(): void
    {
        $this->loadModStrings();

        $this->checkOptionalModulesInExtensions();

        $labels = [
            $this->modStrings['LBL_CURL_REQUEST_MAIN_PAGE'],
            $this->modStrings['LBL_CURL_REQUEST_API_PAGE']
        ];

        $result[] = $this->checkMainPage();
        $result[] = $this->checkGraphQlAPI();
        $this->addChecks($this->modStrings['LBL_ROUTE_ACCESS_CHECK'], $labels, $result, true);
    }

    private function checkOptionalModulesInExtensions(): void
    {
        $this->loadModStrings();;

        $this->log->info('Checking optional loaded extensions');

        $modules = [
            'imap',
            'ldap',
        ];


        $key = 'SERVER CHECKS';

        $loadedExtensions = get_loaded_extensions();

        foreach ($modules as $module) {
            $result['warnings'] = [];
            $label = $this->modStrings['LBL_CHECKSYS_' . strtoupper($module) . '_EXTENSIONS'];
            $this->systemChecks[$key]['checks'][$label]['label'] = $label;
            $this->log->info('Checking if ' . $module . ' exists in loaded extensions');
            if (!in_array($module, $loadedExtensions)) {
                $result['result'] = '';
                $result['warnings'][] = $this->modStrings['ERR_CHECKSYS_' . strtoupper($module)] ?? strtoupper($module) . ' not found in extensions.';
                $this->systemChecks[$key]['checks'][$label]['warnings'] = $result['warnings'];
                $this->warningsFound = true;
            } else {
                $this->log->info($module . ' found in loaded extensions');
                $result['result'] = $this->modStrings['LBL_CHECKSYS_OK'];
            }

            $this->systemChecks[$key]['checks'][$label]['result'] = $result['result'];
        }
    }

    /**
     * @param $labels
     * @return array
     */
    private function isWritableLogsFolder(&$labels): array
    {
        return $this->checkFolderIsWritable('logs', $labels);
    }

    /**
     * @param $labels
     * @return array
     */
    private function isWritableCacheFolder(&$labels): array
    {
        return $this->checkFolderIsWritable('cache', $labels);

    }

    protected function isRootWritable(&$labels): array
    {
        $this->loadModStrings();

        $this->log->info('Checking if root is writable');

        $labels[] = $this->modStrings['LBL_CHECKSYS_ROOT'];

        $rootFolder = __DIR__ . '/../../../../';

        $results = [
            'result' => '',
            'errors' => []
        ];

        if (!is_writable($rootFolder)){
            $results['errors'][] = $this->modStrings['ERR_CHECKSYS_ROOT_NOT_WRITABLE'];
            return $results;
        }

        $this->log->info('Root exists and is writable');
        $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];

        return $results;

    }

    public function checkFolderIsWritable(string $folderName, array &$labels, string $parentDir = ''): array
    {

        $this->loadModStrings();

        $this->log->info('Checking ' . $folderName . ' is writable');

        $labels[] = $this->modStrings['LBL_CHECKSYS_' . strtoupper($folderName)];

        $results = [
            'result' => '',
            'errors' => []
        ];

        $folder = __DIR__ . '/../../../../' . $folderName;

        if (!empty($parentDir)) {
            $folder = __DIR__ . '/../../../../' . $parentDir . '/' . $folderName;
        }

        if (is_dir($folder) && is_writable($folder)) {
            $this->log->info($folderName . ' exists and is writable');
            $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];

            return $results;
        }

        $results['errors'][] = $this->modStrings['ERR_CHECKSYS_' . strtoupper($folderName) . '_NOT_WRITABLE'];
        return $results;
    }

    private function isExtensionsWritable(&$labels): array
    {
        return $this->checkFolderIsWritable('extensions', $labels);
    }

    private function isSecretsWritable($labels): array
    {
        return $this->checkFolderIsWritable('secrets', $labels, 'config');
    }

    private function canTouchEnv(&$labels): array
    {
        $this->loadModStrings();

        $labels[] = $this->modStrings['LBL_CHECKSYS_ENV'];

        $env = __DIR__ . '/../../../../.env';

        $results = [
            'result' => '',
            'errors' => []
        ];

        if ((file_exists($env) && is_writable($env)) || (!file_exists($env) && touch($env))) {
            $this->log->info('.env exists or is writable');
            $results['result'] = $this->modStrings['LBL_CHECKSYS_OK'];

            return $results;
        }

        $results['errors'][] = $this->modStrings['ERR_CHECKSYS_ENV_NOT_WRITABLE'];
        return $results;
    }

    /**
     * @return mixed
     */
    protected function getCssFile(): mixed
    {
        $files = scandir('dist');

        foreach ($files as $file) {

            if (preg_match("/styles\.[^.]+\.css/", $file)) {
                $cssFile = $file;
            }
        }
        return $cssFile;
    }

    /**
     * @return void
     */
    protected function loadModStrings(): void
    {
        $this->modStrings = $this->getLanguageStrings();
    }
}
