<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20250912141250 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return 'Add new schedulers to the database';
    }

    public function up(Schema $schema): void
    {

        $this->log('Migration Version20250912141250: Adding new schedulers to the database');

        $interval = '*::*::*::*::*';
        $date = date('Y-m-d H:i:s');
        $status = 'Active';
        $dateStart = '2015-01-01 00:00:00';
        $defaultId = '1';

        $schedulerMap = [
            'scheduler::send-from-queue' => 'Send Campaign Emails',
            'scheduler::email-to-queue' => 'Queue Campaign Emails',
            'scheduler::clean-up-temporary-files' => 'Clean Up Temporary Files',
        ];

        $existingSchedulers = $this->getExistingSchedulers($schedulerMap);

        $connection = $this->connection;
        $connection->beginTransaction();
        try {
            foreach ($schedulerMap as $job => $name) {

                if (isset($existingSchedulers[$job])) {
                    continue;
                }

                $connection->insert('schedulers', [
                    'id' => create_guid(),
                    'deleted' => 0,
                    'date_entered' => $date,
                    'date_modified' => $date,
                    'created_by' => $defaultId,
                    'modified_user_id' => $defaultId,
                    'name' => $name,
                    'job' => $job,
                    'date_time_start' => $dateStart,
                    'job_interval' => $interval,
                    'status' => $status,
                    'catch_up' => 0
                ]);
            }

            $connection->commit();
            $this->log('Migration Version20250912141250: New schedulers added to the database successfully');
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->log('Migration Version20250912141250: Unable to add new schedulers to the database. Error: ' . $e->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * @param array $schedulerMap
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getExistingSchedulers(array $schedulerMap): array
    {
        $existingSchedulers = [];
        foreach ($schedulerMap as $job => $name) {
            $result = $this->connection->fetchOne('SELECT COUNT(*) FROM schedulers WHERE job = ? AND deleted = 0', [$job]);
            if ((int)$result > 0) {
                $existingSchedulers[$job] = true;
                $this->log("Migration Version20250912141250: Scheduler '{$job}' already exists, skipping");
            }
        }
        return $existingSchedulers;
    }
}
