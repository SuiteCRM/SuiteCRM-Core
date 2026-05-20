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
use Exception;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;

final class Version20230817141244 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription() : string
    {
        return 'Add defaultExt front end extension';
    }

    public function up(Schema $schema) : void
    {
        if (file_exists($this->getProjectDir() . '/extensions/default')) {
            $defaultExtensionReplacedMessage = "Default extension under 'extensions/default' has been replace with 'defaultExt'. If you have any customizations in 'extensions/default' please move them to 'defaultExt' and remove 'extensions/default'.";
            $this->log($defaultExtensionReplacedMessage);
            $output = new ConsoleOutput();
            $output->writeln("<comment>Warning! $defaultExtensionReplacedMessage</comment>") ;
        }

        $defaultExtensionPath = $this->getProjectDir() . '/extensions/defaultExt';
        if (file_exists($defaultExtensionPath)) {
            $this->log('DefaultExt extension already exists, skipping.');
            return;
        }

        $versionFile = $this->getProjectDir() . "/VERSION";

        if (!file_exists($versionFile)) {
            $this->log('Version file does not exist, skipping.');
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
            $this->log('Default extension does not exist on the extracted upgrade package, skipping.');
            return;
        }

        $filesystem = new Filesystem();

        try {
            $filesystem->mirror($defaultExtensionPackage, $defaultExtensionPath);
            $this->log('Default extension package copied successfully.');
        } catch (Exception $e) {
            $this->log('Error while trying to copy the default extension package: ' . $e->getMessage());
        }

    }

    public function down(Schema $schema) : void
    {
    }
}
