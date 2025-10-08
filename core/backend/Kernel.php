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


namespace App;

use App\DependecyInjection\BackwardsCompatibility\LegacySAMLExtension;
use App\DependecyInjection\Metadata\MetadataExtension;
use Exception;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use function dirname;

/**
 * Class Kernel
 * @package App
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @return string
     */
    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return dirname(__DIR__, 2) . '/cache/' . $this->environment;
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return dirname(__DIR__, 2) . '/logs/' . $this->environment;
    }

    /**
     * @return iterable
     */
    public function registerBundles(): iterable
    {
        $contents = require dirname(__DIR__, 2) . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface $loader
     * @throws Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource(dirname(__DIR__, 2) . '/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = dirname(__DIR__, 2) . '/config';
        $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');
    }

    /**
     * Init bundles and container
     * @return void
     */
    public function init(): void
    {
        $this->setSiteURLEnv();
        $this->initializeBundles();
        $this->initializeContainer();
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->registerExtension(new LegacySAMLExtension());
        $container->registerExtension(new MetadataExtension());
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getLegacyRoute(Request $request): array
    {
        if ($this->container->has('legacy.route.handler')) {
            $legacyRouteHandler = $this->container->get('legacy.route.handler');
            $legacyRouteHandler->setCurrentDir(getcwd());

            return $legacyRouteHandler->getLegacyRoute($request);
        }

        return [];
    }

    /**
     * Enable/disable graphql introspection
     * @return void
     */
    public function configureGraphqlIntrospection(): void
    {
        if ($this->container->has('graphql.introspection_manager')) {
            $this->container->get('graphql.introspection_manager')->configure();
        }
    }

    public function setSiteURLEnv(): void
    {
        $config = $this->getConfigValues();

        $env = $_ENV ?? [];
        if (!empty($env['SITE_URL'])) {
            return;
        }

        if (!empty($config['site_url'])) {
            $_ENV['SITE_URL'] = rtrim($config['site_url'], '/');
            return;
        }

        $_ENV['SITE_URL'] = 'http://localhost';
    }

    public function getConfigValues(): array
    {
        $sugar_config = [];

        $legacyPath = __DIR__ . '/../../public/legacy/';

        $configFile = $legacyPath . 'config.php';

        if (file_exists($configFile)) {
            include($configFile);
        }

        $configOverrideFile = $legacyPath . 'config_override.php';

        if (file_exists($configOverrideFile)) {
            include($configOverrideFile);
        }

        return $sugar_config;
    }
}
