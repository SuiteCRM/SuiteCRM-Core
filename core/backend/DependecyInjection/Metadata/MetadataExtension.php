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
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\DependecyInjection\Metadata;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\AbstractExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;

class MetadataExtension extends AbstractExtension
{

    public function getAlias(): string
    {
        return 'metadata_extension';
    }

    public function configure(DefinitionConfigurator $definition): void
    {
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('language', []);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $locator = new FileLocator();
        $languageLoader = $this->getLanguageLoader($builder, $locator);
        $this->loadParameters('language', $languageLoader);

        $language = $builder->getParameter('language');
        $container->parameters()->set('language', $language);
    }

    /**
     * @param string $folder
     * @param DelegatingLoader $delegatingLoader
     * @return void
     * @throws \Exception
     */
    protected function loadParameters(string $folder, DelegatingLoader $delegatingLoader): void
    {
        [$coreConfigDir, $extensionsDir, $modules] = $this->getBasePaths();

        $fileExtensions = '.{php,xml,yaml,yml}';

        // YamlUserLoader is used to load this resource because it supports
        // files with the '.yaml' extension

        $delegatingLoader->load($coreConfigDir . '/' . $folder . '/*' . $fileExtensions, 'glob');
        $delegatingLoader->load($coreConfigDir . '/' . $folder . '/**/*' . $fileExtensions, 'glob');
        $delegatingLoader->load($modules . '/*/' . $folder . '/*' . $fileExtensions, 'glob');
        $delegatingLoader->load($modules . '/*/' . $folder . '/**/*' . $fileExtensions, 'glob');
        $delegatingLoader->load($extensionsDir . '/*/' . $folder . '/*' . $fileExtensions, 'glob');
        $delegatingLoader->load($extensionsDir . '/*/' . $folder . '/**/*' . $fileExtensions, 'glob');
        $delegatingLoader->load($extensionsDir . '/*/modules/*/' . $folder . '/*' . $fileExtensions, 'glob');
        $delegatingLoader->load($extensionsDir . '/*/modules/*/' . $folder . '/**/*' . $fileExtensions, 'glob');

    }

    /**
     * @param ContainerBuilder $builder
     * @param FileLocator $locator
     * @return DelegatingLoader
     */
    protected function getLanguageLoader(ContainerBuilder $builder, FileLocator $locator): DelegatingLoader
    {
        $resolver = new LoaderResolver(
            [
                new LanguageYamlFileLoader($builder, $locator),
                new GlobFileLoader($builder, $locator)
            ]
        );

        return new DelegatingLoader($resolver);
    }

    /**
     * @return string[]
     */
    protected function getBasePaths(): array
    {
        $rootDir = dirname(__DIR__, 4);
        $coreConfigDir = $rootDir . '/config';
        $extensionsDir = $rootDir . '/extensions';
        $modules = $rootDir . '/core/modules';
        return [$coreConfigDir, $extensionsDir, $modules];
    }

}
