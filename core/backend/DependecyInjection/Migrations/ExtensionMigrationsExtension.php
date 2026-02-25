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

namespace App\DependecyInjection\Migrations;

use DirectoryIterator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\AbstractExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Discovers Migrations directories inside each extension and registers their
 * namespace/path pairs with doctrine_migrations via prepend, so that extension
 * migrations are picked up automatically without manual configuration.
 *
 * Namespace convention: App\Extension\<extensionDirName>\Migrations
 */
class ExtensionMigrationsExtension extends AbstractExtension
{
    public function getAlias(): string
    {
        return 'extension_migrations';
    }

    public function configure(DefinitionConfigurator $definition): void
    {
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $extensionsDir = dirname(__DIR__, 4) . '/extensions';

        if (!is_dir($extensionsDir)) {
            return;
        }

        $migrationsPaths = [];

        foreach (new DirectoryIterator($extensionsDir) as $entry) {
            if (!$entry->isDir() || $entry->isDot()) {
                continue;
            }

            $migrationsDir = $entry->getPathname() . '/Migrations';

            if (!is_dir($migrationsDir)) {
                continue;
            }

            $namespace = 'App\\Extension\\' . $entry->getFilename() . '\\Migrations';
            $migrationsPaths[$namespace] = $migrationsDir;
        }

        if (empty($migrationsPaths)) {
            return;
        }

        $builder->prependExtensionConfig(
            'doctrine_migrations', [
            'migrations_paths' => $migrationsPaths,
        ]
        );
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
    }
}
