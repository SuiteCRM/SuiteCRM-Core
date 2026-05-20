<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Filesystem\Filesystem;

final class Version20231103101649 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return 'Update defaultExt with Angular 16 module federation adjustments';
    }

    public function up(Schema $schema): void
    {
        $defaultExtensionPath = $this->getProjectDir() . '/extensions/defaultExt';
        if (!file_exists($defaultExtensionPath)) {
            $this->log('DefaultExt extension path "' . $defaultExtensionPath . '" does not exist, skipping.');
            return;
        }

        $versionFile = $this->getProjectDir() . "/VERSION";

        if (!file_exists($versionFile)) {
            $this->log('Version file "' . $versionFile . '" does not exist, skipping.');
            return;
        }

        $version = trim(file_get_contents($versionFile) ?? '');
        if (empty($version)) {
            $this->log('Empty version, skipping.');
            return;
        }

        $upgradePackageVersion = "SuiteCRM-$version";

        $baseUpgradePackagePath = $this->container->getParameter('packages.upgrade.dir');
        $upgradePackagePath = $baseUpgradePackagePath . '/' . $upgradePackageVersion . '-extracted';
        $defaultExtensionPackage = $upgradePackagePath . '/extensions/defaultExt';

        if (!file_exists($defaultExtensionPackage)) {
            $this->log('"defaultExt" extension does not exist on the extracted upgrade package (path: "' . $defaultExtensionPackage . '") , skipping.');
            return;
        }

        $filesystem = new Filesystem();

        try {

            $output = new ConsoleOutput();
            $angularMessage = "SuiteCRM is now using angular 18.";
            $this->log($angularMessage);
            $output->writeln("<comment>Warning! $angularMessage</comment>");
            $extensionsNeedToBeRebuiltMessage = "'defaultExt' and any extension that contains frontend changes will need to be rebuilt. For defaultExt you can build using `yarn run build:extension defaultExt`";
            $this->log($extensionsNeedToBeRebuiltMessage);
            $output->writeln("<comment>Warning! $extensionsNeedToBeRebuiltMessage</comment>");


            $filesystem->copy($defaultExtensionPackage . '/app/tsconfig.app.json', $defaultExtensionPath . '/app/tsconfig.app.json', true);
            $filesystem->copy($defaultExtensionPackage . '/app/webpack.config.js', $defaultExtensionPath . '/app/webpack.config.js', true);
            $filesystem->copy($defaultExtensionPackage . '/config/extension.php', $defaultExtensionPath . '/config/extension.php', true);
            $this->log('File: extensions/defaultExt/app/tsconfig.app.json updated using migration.');
            $this->log('- Copied: ' . $defaultExtensionPackage . '/app/tsconfig.app.json' . ' to ' . $defaultExtensionPath . '/app/tsconfig.app.json');
            $this->log('File: extensions/defaultExt/app/webpack.config.js updated using migration.');
            $this->log('- Copied: ' . $defaultExtensionPackage . '/app/webpack.config.js' . ' to ' . $defaultExtensionPath . '/app/webpack.config.js',);
            $this->log('File: extensions/defaultExt/config/extension.php updated using migration.');
            $this->log('- Copied: ' . $defaultExtensionPackage . '/config/extension.php' . ' to ' . $defaultExtensionPath . '/config/extension.php');

            $defaultExtDisabledMessage = "'defaultExt' has been disabled. It may need to be rebuilt.";
            $this->log($defaultExtDisabledMessage);
            $output->writeln("<comment>Warning! $defaultExtDisabledMessage</comment>");

            $customExtensionMessage = [
                "Warning! Extensions other than 'defaultExt' will need the following files manually updated:",
                "- extensions/your-extension/app/tsconfig.app.json",
                "- extensions/your-extension/app/webpack.config.js",
                "- extensions/your-extension/config/extension.php",
            ];

            foreach ($customExtensionMessage as $message) {
                $this->log($message);
                $output->writeln("<comment>$message</comment>");
            }

        } catch (\Exception $e) {
            $this->log('Error while trying to update the "defaultExt" extension package: ' . $e->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
    }
}
