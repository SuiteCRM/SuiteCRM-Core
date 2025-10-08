<?php
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

namespace App\Install\Service\Upgrade\Steps;

use App\Engine\Model\Feedback;
use App\Engine\Model\ProcessStepTrait;
use App\Engine\Service\FolderSync\FolderComparatorInterface;
use App\Engine\Service\ProcessSteps\ProcessStepAlert;
use App\Install\Service\Upgrade\UpgradePackageHandler;
use App\Install\Service\Upgrade\UpgradeStepInterface;

/**
 * Class FilesToDeleteCheck
 * @package App\Install\Service\LegacyMigration\Steps;
 */
class FilesToDeleteCheck implements UpgradeStepInterface
{
    use ProcessStepTrait;

    public const HANDLER_KEY = 'custom-files-deletion-warning';
    public const POSITION = 550;
    public const STAGE = 'upgrade-install';

    /**
     * @var UpgradePackageHandler
     */
    private $handler;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $upgradePackageDir;

    /**
     * @var FolderComparatorInterface
     */
    protected $compare;

    /**
     * ExternalFilesCheck constructor.
     * @param UpgradePackageHandler $handler
     * @param string $projectDir
     * @param string $upgradePackageDir
     * @param FolderComparatorInterface $compare
     * @param array $upgradeConfig
     */
    public function __construct(
        UpgradePackageHandler $handler,
        string $projectDir,
        string $upgradePackageDir,
        FolderComparatorInterface $compare,
        array $upgradeConfig
    ) {
        $this->handler = $handler;
        $this->projectDir = $projectDir;
        $this->upgradePackageDir = $upgradePackageDir;
        $this->compare = $compare;

        $this->compare->setToKeep($upgradeConfig['toKeep']);
        $this->compare->setToKeepIgnore($upgradeConfig['toKeepIgnore']);
        $this->compare->setPathsToExpand($upgradeConfig['toExpand']);
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return self::POSITION;
    }

    /**
     * @inheritDoc
     */
    public function getStage(): string
    {
        return self::STAGE;
    }

    /**
     * Get Alert
     * @param array $context
     * @return ProcessStepAlert
     */
    public function getAlert(array &$context): ProcessStepAlert
    {
        $version = $context['version'];
        $extractPath = $this->getPackageExtractPath($version);
        $manifest = $this->runCompare($extractPath);

        $deletionList = [];

        if (!empty($manifest)) {
            foreach ($manifest as $path => $entry) {
                if ($entry->action === 'delete') {
                    $deletionList[] = $path;
                }
            }
        }

        $alert = new ProcessStepAlert();

        if (!empty($deletionList)) {
            $alert->setTile("Warning: Potential Custom Files");

            $messages = [
                "*********************************************************************",
                "**     \e[31m                     !!! WARNING !!!                        \e[0m**",
                "*********************************************************************",
                "** The following files/directories will be permanently DELETED:    **",
            ];

            foreach ($deletionList as $item) {
                $messages[] = "** - $item";
            }

            $messages[] = "**                                                                 **";
            $messages[] = "** Move any important files to a safe location BEFORE proceeding.  **";
            $messages[] = "*********************************************************************";

            $alert->setMessages($messages);
        } else {
            $alert->setMessages([]);
        }

        return $alert;
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
     * @param string $extractPath
     * @return array
     */
    public function runCompare(string $extractPath): array
    {
        return $this->compare->run($extractPath, $this->projectDir);
    }

    /**
     * @inheritDoc
     */
    public function execute(array &$context): Feedback
    {
        $targetVersion = $context['version'] ?? '';

        return $this->handler->checkFilesToDelete($targetVersion);
    }
}
