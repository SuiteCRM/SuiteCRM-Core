<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20251013072701 extends BaseMigration implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function getDescription(): string
    {
        return 'Set allowed cron users if not set';
    }

    public function up(Schema $schema): void
    {
        $systemConfigsHandler = $this->container->get('app.system-configs');
        $cronHandler = $this->container->get('app.cron.handler');
        $systemConfigs = $systemConfigsHandler->getConfigs();

        if (isset($systemConfigs['cron']['allowed_cron_users'])) {
            $this->log('Allowed Cron Users already set, skipping...');
            return;
        }

        if (!isset($systemConfigs['cron']) || !is_array($systemConfigs['cron'])) {
            $this->log('No cron system config found. Initializing...');
            $this->log('Please update other cron configuration options as needed.');
            $systemConfigs['cron'] = [];
        }

        $runningUser = $cronHandler->getRunningUser();

        if ($runningUser === null) {
            $this->log("Could not determine the running user. Please set 'allowed_cron_users' manually in `config.php`.");
            return;
        }

        $this->log("Setting Allowed Cron Users to current running user.");

        if ($runningUser === 'root') {
            $runningUser = 'root_REMOVE_THIS_NOTICE_IF_YOU_REALLY_WANT_TO_ALLOW_ROOT';
        }

        $systemConfigs['cron']['allowed_cron_users'] = [$runningUser];
        $systemConfigsHandler->updateSystemConfig($systemConfigs);
    }

    public function down(Schema $schema): void
    {
    }
}
