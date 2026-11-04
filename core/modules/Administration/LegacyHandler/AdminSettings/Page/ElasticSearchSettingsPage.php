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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

use App\Module\Administration\LegacyHandler\AdminSettings\AdminSettingsPage;
use Elasticsearch\ClientBuilder;
use SuiteCRM\Search\ElasticSearch\ElasticSearchClientBuilder;
use SuiteCRM\Search\ElasticSearch\ElasticSearchIndexer;

/**
 * The Elasticsearch settings (legacy Administration&action=ElasticSearchSettings):
 * enabled, server (host, user, password: never sent to the page, kept when left empty), its
 * schedulers; the connection test and the full or partial indexing scheduled, as the legacy
 * page and its controller.
 */
class ElasticSearchSettingsPage extends AdminSettingsPage
{
    protected const INDEXER_JOB = 'function::runElasticSearchIndexerScheduler';

    public function getPageKey(): string
    {
        return 'elasticsearch';
    }

    protected function getPage(): array
    {
        $config = $this->getConfig();

        return [
            'title' => $this->text('LBL_ELASTIC_SEARCH_SETTINGS'),
            'sections' => [
                $this->section('LBL_ELASTIC_SEARCH_GENERAL', [
                    $this->field('enabled', 'LBL_ELASTIC_SEARCH_ENABLE', 'bool'),
                ], 'Administration', 'LBL_ELASTIC_SEARCH_SETTINGS_HELP', ['test' => 'LBL_ELASTIC_SEARCH_TEST_CONNECTION']),
                $this->section('LBL_ELASTIC_SEARCH_SERVER', [
                    $this->field('host', 'LBL_ELASTIC_SEARCH_HOST', 'text', ['wide' => true]),
                    $this->field('user', 'LBL_ELASTIC_SEARCH_USER', 'text'),
                    $this->field('pass', 'LBL_ELASTIC_SEARCH_PASS', 'password', ['set' => (string)($config['pass'] ?? '') !== '']),
                ]),
                $this->section('LBL_ELASTIC_SEARCH_SCHEDULERS', [
                    ['name' => 'schedulers', 'label' => '', 'type' => 'html', 'help' => $this->getSchedulersHtml(), 'wide' => true],
                ]),
                $this->section('LBL_ELASTIC_SEARCH_INDEX', [], 'Administration', 'LBL_ELASTIC_SEARCH_INDEX_SCHEDULE_HELP', [
                    'full' => 'LBL_ELASTIC_SEARCH_INDEX_SCHEDULE_FULL',
                    'partial' => 'LBL_ELASTIC_SEARCH_INDEX_SCHEDULE_PART',
                ]),
            ],
            'values' => [
                'enabled' => !empty($config['enabled']),
                'host' => (string)($config['host'] ?? ''),
                'user' => (string)($config['user'] ?? ''),
                'pass' => '',
            ],
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $config = $this->getConfig();
        // Empty keeps the saved password, false removes it.
        $pass = ($values['pass'] ?? '') === false ? '' : (string)($values['pass'] ?? '');
        $kept = ($values['pass'] ?? '') !== false && $pass === '';

        $configurator = new \Configurator();
        $configurator->config['search']['ElasticSearch']['enabled'] = !empty($values['enabled']);
        $configurator->config['search']['ElasticSearch']['host'] = trim((string)($values['host'] ?? ''));
        $configurator->config['search']['ElasticSearch']['user'] = trim((string)($values['user'] ?? ''));
        $configurator->config['search']['ElasticSearch']['pass'] = $kept ? (string)($config['pass'] ?? '') : $pass;
        $this->withPost([], static fn () => $configurator->saveConfig());

        return null;
    }

    protected function runAction(string $action, array $values): array
    {
        return match ($action) {
            'test' => $this->testConnection($values),
            'full' => $this->scheduleIndex(false),
            'partial' => $this->scheduleIndex(true),
            default => parent::runAction($action, $values),
        };
    }

    /**
     * Pings the server of the page (the saved password when none is given), as the legacy page.
     *
     * @param array<string, mixed> $values
     */
    protected function testConnection(array $values): array
    {
        $pass = ($values['pass'] ?? '') === false ? '' : (string)($values['pass'] ?? '');
        $kept = ($values['pass'] ?? '') !== false && $pass === '';

        try {
            $client = ClientBuilder::create()->setHosts([
                ElasticSearchClientBuilder::sanitizeHost([
                    'host' => trim((string)($values['host'] ?? '')),
                    'user' => trim((string)($values['user'] ?? '')),
                    'pass' => $kept ? (string)($this->getConfig()['pass'] ?? '') : $pass,
                ]),
            ])->build();
            $info = $client->info();
            $ping = (new ElasticSearchIndexer($client))->ping();
        } catch (\Throwable $throwable) {
            return [
                'status' => 'error',
                'message' => $this->text('LBL_ELASTIC_SEARCH_TEST_CONNECTION_FAIL', 'Administration', false) . ' ' . $throwable->getMessage(),
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->text('LBL_ELASTIC_SEARCH_TEST_CONNECTION_SUCCESS', 'Administration', false)
                . ' Ping: ' . ($ping / 1000) . ' ms, Elasticsearch v' . ($info['version']['number'] ?? '?'),
        ];
    }

    /**
     * Queues an indexing job, run by the schedulers, as the legacy page.
     */
    protected function scheduleIndex(bool $partial): array
    {
        require_once 'modules/SchedulersJobs/SchedulersJob.php';
        require_once 'include/SugarQueue/SugarJobQueue.php';

        $job = new \SchedulersJob();
        $job->name = 'Index requested by an administrator';
        $job->target = self::INDEXER_JOB;
        $job->data = json_encode(['partial' => $partial]);
        $job->assigned_user_id = 1;
        (new \SugarJobQueue())->submitJob($job);

        return [
            'status' => 'success',
            'message' => $this->text($partial ? 'LBL_ELASTIC_SEARCH_INDEX_SCHEDULE_PART_SUCCESS' : 'LBL_ELASTIC_SEARCH_INDEX_SCHEDULE_FULL_SUCCESS', 'Administration', false),
        ];
    }

    /**
     * The Elasticsearch schedulers (link to their record), with their status and last run.
     */
    protected function getSchedulersHtml(): string
    {
        /** @var \Scheduler[] $schedulers */
        $schedulers = \BeanFactory::getBean('Schedulers')->get_full_list(null, "schedulers.job='" . self::INDEXER_JOB . "'") ?: [];
        if (empty($schedulers)) {
            return '<p>' . htmlspecialchars($this->text('LBL_ELASTIC_SEARCH_SCHEDULERS_NOT_FOUND', 'Administration', false)) . '</p>';
        }

        $html = '<p>' . htmlspecialchars($this->text('LBL_ELASTIC_SEARCH_SCHEDULERS_HELP', 'Administration', false)) . '</p><ul>';
        foreach ($schedulers as $scheduler) {
            // Its dates in the format of the user, as the legacy page.
            $scheduler->check_date_relationships_load();
            $lastRun = !empty($scheduler->last_run)
                ? $this->text('LBL_ELASTIC_SEARCH_SCHEDULERS_LAST_RUN', 'Administration', false) . ' ' . $scheduler->last_run
                : $this->text('LBL_ELASTIC_SEARCH_SCHEDULERS_NEVER_RUN', 'Administration', false);
            $html .= '<li><a href="#/schedulers/record/' . rawurlencode((string)$scheduler->id) . '">'
                . htmlspecialchars($this->decode((string)$scheduler->name)) . '</a> &mdash; <b>'
                . htmlspecialchars((string)$scheduler->status) . '</b> &mdash; ' . htmlspecialchars($lastRun) . '</li>';
        }

        return $html . '</ul><p>' . htmlspecialchars($this->text('LBL_ELASTIC_SEARCH_SCHEDULERS_DESC', 'Administration', false)) . '</p>';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfig(): array
    {
        global $sugar_config;

        return is_array($sugar_config['search']['ElasticSearch'] ?? null) ? $sugar_config['search']['ElasticSearch'] : [];
    }
}
