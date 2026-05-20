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

namespace App\Install\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'scrm:copy-legacy-assets')]
class CopyLegacyAssets extends Command
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var array
     */
    private $copyLegacyAssetPaths;
    /**
     * @var string
     */
    private $legacyDir;

    /**
     * CopyLegacyAssets constructor.
     * @param string|null $name
     * @param string $projectDir
     * @param string $legacyDir
     * @param array $copyLegacyAssetPaths
     */
    public function __construct(
        string $name = null,
        string $projectDir = '',
        string $legacyDir = '',
        array  $copyLegacyAssetPaths = []
    )
    {
        parent::__construct($name);
        $this->projectDir = $projectDir;
        $this->copyLegacyAssetPaths = $copyLegacyAssetPaths;
        $this->legacyDir = $legacyDir;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Copy legacy assets');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->copyAssets();

        return Command::SUCCESS;
    }

    /**
     * Copy assets
     */
    protected function copyAssets(): void
    {
        $filesystem = new Filesystem();

        if (empty($this->copyLegacyAssetPaths)) {
            return;
        }


        foreach ($this->copyLegacyAssetPaths as $path => $legacyPath) {
            $copyPath = $this->legacyDir . '/' . $legacyPath;
            $originPath = $this->getProjectDir() . '/' . $path;

            if (is_dir($originPath)) {
                $filesystem->mirror($originPath, $copyPath, null, ['override' => true, 'delete' => true]);
            } elseif (is_file($originPath)) {
                $filesystem->copy($originPath, $copyPath);
            }
        }
    }

    /**
     * @return string
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }
}
