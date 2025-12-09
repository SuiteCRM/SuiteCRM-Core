<?php
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

namespace App\Install\Service\Upgrade;

use App\Engine\Model\Feedback;
use App\Engine\Service\FolderSync\FolderComparatorInterface;
use App\Engine\Service\FolderSync\FolderSync;
use App\Install\Service\Package\PackageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class UpgradePackageHandler extends PackageHandler
{
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $upgradePackageDir;

    /**
     * @var string
     */
    private $legacyDir;

    /**
     * @var FolderSync
     */
    protected $sync;

    /**
     * @var FolderComparatorInterface
     */
    protected $compare;

    /**
     * @var LoggerInterface
     */
    protected $upgradeLogger;

    /**
     * UpgradeHandler constructor.
     * @param string $projectDir
     * @param string $upgradePackageDir
     * @param string $legacyDir
     * @param FolderSync $sync
     * @param FolderComparatorInterface $compare
     * @param LoggerInterface $upgradeLogger
     * @param array $upgradeConfig
     */
    public function __construct(
        string $projectDir,
        string $upgradePackageDir,
        string $legacyDir,
        FolderSync $sync,
        FolderComparatorInterface $compare,
        LoggerInterface $upgradeLogger,
        array $upgradeConfig
    ) {
        parent::__construct();
        $this->projectDir = $projectDir;
        $this->upgradePackageDir = $upgradePackageDir;
        $this->legacyDir = $legacyDir;
        $this->sync = $sync;
        $this->compare = $compare;

        $this->compare->setToKeep($upgradeConfig['toKeep']);
        $this->compare->setToKeepIgnore($upgradeConfig['toKeepIgnore']);
        $this->compare->setPathsToExpand($upgradeConfig['toExpand']);
        $this->compare->setToReAdd($upgradeConfig['toReAdd']);
        $this->upgradeLogger = $upgradeLogger;
    }

    /**
     * Check if package exists
     * @param string $version
     * @return Feedback
     */
    public function checkPackage(string $version): Feedback
    {
        $packagePath = $this->getPackagePath($version);

        $feedback = new Feedback();
        $feedback->setSuccess(true)->setMessages(['Package found in path']);
        $feedback->setDebug(['Check package existence in: ' . $packagePath]);

        if (!$this->exists($packagePath)) {
            $feedback->setSuccess(false)->setMessages(['Package not found in path: ' . $packagePath]);
        }

        return $feedback;
    }

    /**
     * Extract package
     * @param string $version
     * @return Feedback
     */
    public function extractPackage(string $version): Feedback
    {
        $packagePath = $this->getPackagePath($version);
        $extractPath = $this->getPackageExtractPath($version);

        $feedback = new Feedback();
        $feedback->setSuccess(true)->setMessages(['Package extracted']);
        $feedback->setDebug(['Trying to extract to: ' . $extractPath]);

        $extracted = $this->extract($packagePath, $extractPath);

        if ($extracted === false) {
            $feedback = new Feedback();
            $feedback->setSuccess(false)->setMessages(['Error while trying to extract package to: ' . $extractPath]);
        }

        return $feedback;
    }

    /**
     * Run compare and check files to delete
     * @param string $version
     * @return Feedback
     */
    public function checkFilesToDelete(string $version): Feedback
    {
        $feedback = new Feedback();
        $feedback->setSuccess(true);
        $messages = ['Files to delete checked'];
        $feedback->setMessages($messages);

        return $feedback;
    }

    /**
     * Run compare and check permissions
     * @param string $version
     * @return Feedback
     */
    public function checkPermissions(string $version): Feedback
    {
        $extractPath = $this->getPackageExtractPath($version);

        $manifest = $this->runCompare($extractPath);

        $messages = [];
        $success = true;

        foreach ($manifest as $path => $entry) {

            $originAllowed = true;
            if ($entry->action !== 'delete') {
                $originResult = $this->sync->checkOriginPermissions($extractPath, $path);
                $originAllowed = $originResult['allowed'] ?? false;
                $originPath = $originResult['path'] ?? '';

                if ($originAllowed === false) {
                    $messages[] = 'Insufficient permissions to read from: ' . $originPath;
                }
            }

            $destinationResult = $this->sync->checkDestinationPermissions($this->projectDir, $path);
            $destinationAllowed = $destinationResult['allowed'] ?? false;
            $destinationPath = $destinationResult['path'] ?? '';

            if ($destinationAllowed === false) {
                $messages[] = 'Insufficient permissions to read from: ' . $destinationPath;
            }

            $success = $success && $originAllowed && $destinationAllowed;
        }

        $feedback = new Feedback();
        $feedback->setSuccess($success);

        if ($success) {
            $messages = ['Permissions checked'];
        }

        $feedback->setMessages($messages);

        return $feedback;
    }

    /**
     * Run compare and install package
     * @param string $version
     * @return Feedback
     */
    public function install(string $version): Feedback
    {
        $extractPath = $this->getPackageExtractPath($version);

        $manifest = $this->runCompare($extractPath);

        $feedback = new Feedback();

        if (empty($manifest)) {
            $feedback->setSuccess(true)->setMessages(['Sync diff did not return results']);

            return $feedback;
        }

        $feedback->setDebug([
            'sync manifest generated: ' . json_encode($manifest, JSON_THROW_ON_ERROR)
        ]);

        $tmpFolder = $this->projectDir . '/tmp/toReAdd';
        $toReAddItems = $this->compare->getToReAdd();

        $this->copyFilesToReAddToTmpFolder($tmpFolder, $toReAddItems);

        $this->sync->run($extractPath, $this->projectDir, $manifest);

        $this->restoreFilesFromTmpFolder($tmpFolder, $toReAddItems);

        $feedback->setSuccess(true)->setMessages(['Successfully installed package']);

        return $feedback;
    }

    /**
     * Copy files to be re-added to a temporary folder
     *
     * @param string $tmpFolder
     * @param array $toReAddItems
     * @return void
     */
    protected function copyFilesToReAddToTmpFolder(string $tmpFolder, array $toReAddItems): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists($tmpFolder)) {
            $filesystem->mkdir($tmpFolder);
        }

        foreach ($toReAddItems as $item) {
            $sourcePath = $this->projectDir . '/' . $item;
            $destinationPath = $tmpFolder . '/' . $item;

            if (is_dir($sourcePath)) {
                $this->copyToReAddFolderContents($sourcePath, $destinationPath, $filesystem);
                continue;
            }

            if (file_exists($sourcePath)) {
                $filesystem->mirror($sourcePath, $destinationPath);
            }
        }
    }

    /**
     * Restore files from the temporary folder to their original location
     *
     * @param string $tmpFolder
     * @param array $toReAddItems
     * @return void
     */
    protected function restoreFilesFromTmpFolder(string $tmpFolder, array $toReAddItems): void
    {
        $filesystem = new Filesystem();

        foreach ($toReAddItems as $item) {
            $tmpPath = $tmpFolder . '/' . $item;
            $finalDestinationPath = $this->projectDir . '/' . $item;

            if (file_exists($tmpPath)) {
                if (is_dir($tmpPath)) {
                    $this->restoreToReAddTmpFolderContents($tmpPath, $finalDestinationPath, $filesystem);
                } else {
                    if (basename($item) !== 'en_us.lang.php' || !file_exists($finalDestinationPath)) {
                        $filesystem->copy($tmpPath, $finalDestinationPath);
                    }
                }
            }
        }

        $filesystem->remove($tmpFolder);
    }

    /**
     * Run compare and install package
     * @param string $version
     * @return Feedback
     */
    public function backup(string $version): Feedback
    {
        $backupPath = $this->getBackupPath($version);

        $feedback = new Feedback();

        $filesystem = new Filesystem();
        $filesystem->remove($backupPath);
        $filesystem->mkdir($backupPath);

        $moduleBackupPath = $backupPath . '/modules';
        $originalModulesPath = $this->legacyDir . '/modules';

        $filesystem->mirror($originalModulesPath, $moduleBackupPath, null,
            ['override' => true, 'delete' => true, 'copy_on_windows' => true]);


        $feedback->setSuccess(true)->setMessages(['Successfully backed up files']);

        return $feedback;
    }

    /**
     * @param string $extractPath
     * @return array
     */
    public function runCompare(string $extractPath): array
    {
        return $this->compare->run($extractPath, $this->projectDir);
    }

    /**
     * Get package path
     * @param string $version
     * @return string
     */
    protected function getPackagePath(string $version): string
    {
        return $this->upgradePackageDir . '/' . $version . '.zip';
    }

    /**
     * Get package extract output path
     * @param string $version
     * @return string
     */
    public function getPackageExtractPath(string $version): string
    {
        return $this->upgradePackageDir . '/' . $version . '-extracted';
    }


    /**
     * Get package extract output path
     * @param string $version
     * @return string
     */
    public function getBackupPath(string $version): string
    {
        return $this->upgradePackageDir . '/' . $version . '-backup';
    }

    /**
     * @param string $sourcePath
     * @param string $destinationPath
     * @param Filesystem $filesystem
     * @return void
     */
    public function copyToReAddFolderContents(string $sourcePath, string $destinationPath, Filesystem $filesystem): void
    {
        $dirContents = scandir($sourcePath);

        foreach ($dirContents as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if ($file !== 'en_us.lang.php') {
                $fileSourcePath = $sourcePath . '/' . $file;
                $fileDestinationPath = $destinationPath . '/' . $file;

                if (file_exists($fileSourcePath)) {
                    if (!is_dir($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    if (is_dir($fileSourcePath)) {
                        $this->copyToReAddFolderContents($fileSourcePath, $fileDestinationPath, $filesystem);
                        continue;
                    }

                    $filesystem->copy($fileSourcePath, $fileDestinationPath);
                }
            }
        }
    }

    /**
     * @param string $tmpPath
     * @param string $finalDestinationPath
     * @param Filesystem $filesystem
     * @return void
     */
    public function restoreToReAddTmpFolderContents(string $tmpPath, string $finalDestinationPath, Filesystem $filesystem): void
    {
        $dirContents = scandir($tmpPath);

        foreach ($dirContents as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fileTmpPath = $tmpPath . '/' . $file;
            $fileFinalPath = $finalDestinationPath . '/' . $file;

            if ($file === 'en_us.lang.php' && file_exists($fileFinalPath)) {
                continue;
            }

            if (is_dir($fileTmpPath)) {
                $this->restoreToReAddTmpFolderContents($fileTmpPath, $fileFinalPath, $filesystem);
            } else {
                $filesystem->copy($fileTmpPath, $fileFinalPath);
            }
        }
    }
}
