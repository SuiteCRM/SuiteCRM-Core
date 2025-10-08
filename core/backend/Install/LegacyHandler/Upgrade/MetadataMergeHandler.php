<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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

namespace App\Install\LegacyHandler\Upgrade;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Model\Feedback;
use App\Install\Service\Upgrade\UpgradePackageHandler;
use SugarMerge;
use Symfony\Component\HttpFoundation\RequestStack;

class MetadataMergeHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'metadata-merge';

    /**
     * @var string
     */
    protected $upgradePackageDir;

    /**
     * @var UpgradePackageHandler
     */
    protected $packageHandler;

    /**
     * LegacyHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param string $upgradePackageDir
     * @param UpgradePackageHandler $packageHandler
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        string $upgradePackageDir,
        UpgradePackageHandler $packageHandler
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );

        $this->upgradePackageDir = $upgradePackageDir;
        $this->packageHandler = $packageHandler;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @param string $targetVersion
     * @param string $mergeStrategy
     * @return Feedback
     */
    public function run(string $targetVersion, string $mergeStrategy): Feedback
    {
        $this->init();

        /* @noinspection PhpIncludeInspection */
        require_once 'modules/UpgradeWizard/SugarMerge/SugarMerge.php';

        $feedback = new Feedback();

        $mergeMode = 'keep';
        if (!empty($mergeStrategy)) {
            $mergeMode = $mergeStrategy;
        }

        $extractPath = $this->packageHandler->getPackageExtractPath($targetVersion);
        $path = $extractPath . '/public/legacy';
        $originalPath = $this->packageHandler->getBackupPath($targetVersion);
        $customPath = $this->legacyDir . '/custom';
        $merger = new SugarMerge($path, $originalPath, $customPath);
        $merger->mergeAll(true, true, true, $mergeMode);

        $feedback->setSuccess(true);
        $feedback->setMessages(['Metadata successfully merged']);

        $this->close();

        return $feedback;
    }
}
