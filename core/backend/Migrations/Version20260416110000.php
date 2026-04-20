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

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class Version20260416110000 extends BaseMigration implements ContainerAwareInterface
{
    public function getDescription(): string
    {
        return 'Install CalendarSync logic hooks on Meetings module';
    }

    public function up(Schema $schema): void
    {
        $this->log('Version20260416110000: Installing CalendarSync logic hooks');

        $hooksFile = $this->getProjectDir() . '/public/legacy/custom/modules/Meetings/logic_hooks.php';

        if (
            file_exists($hooksFile)
            && strpos(file_get_contents($hooksFile), 'MeetingCalendarSyncLogicHook') !== false
        ) {
            $this->log('Version20260416110000: Hooks already present, recording marker');
            $this->recordHooksMarker();
            return;
        }

        $hook_version = 1;
        $hook_array = [];
        if (file_exists($hooksFile)) {
            include $hooksFile;
        }

        $hook_array['after_save'][] = [
            1,
            'CalendarSync after_save',
            'modules/Meetings/MeetingCalendarSyncLogicHook.php',
            'MeetingCalendarSyncLogicHook',
            'afterSave',
        ];
        $hook_array['after_delete'][] = [
            1,
            'CalendarSync after_delete',
            'modules/Meetings/MeetingCalendarSyncLogicHook.php',
            'MeetingCalendarSyncLogicHook',
            'afterDelete',
        ];

        $dir = dirname($hooksFile);
        if (!is_dir($dir)) {
            mkdir($dir, recursive: true);
        }

        file_put_contents($hooksFile, $this->renderHooksFile($hook_array));

        $this->recordHooksMarker();
        $this->log('Version20260416110000: CalendarSync hooks installed successfully');
    }

    protected function renderHooksFile(array $hookArray): string
    {
        $lines = [
            '<?php',
            '$hook_version = 1;',
            '$hook_array = [];',
        ];

        foreach ($hookArray as $event => $hooks) {
            $lines[] = "\$hook_array['{$event}'] = [];";
            foreach ($hooks as $hook) {
                $lines[] = "\$hook_array['{$event}'][] = [{$this->formatHookEntry($hook)}];";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    protected function formatHookEntry(array $hook): string
    {
        $parts = [];
        foreach ($hook as $value) {
            $parts[] = is_int($value) ? (string)$value : "'" . addslashes($value) . "'";
        }
        return implode(', ', $parts);
    }

    protected function recordHooksMarker(): void
    {
        $this->connection->executeStatement(
            "INSERT IGNORE INTO config (category, name, value) VALUES ('migrations', 'calendar_sync_hooks_installation', '1')"
        );
    }

    public function down(Schema $schema): void
    {
        $hooksFile = $this->getProjectDir() . '/public/legacy/custom/modules/Meetings/logic_hooks.php';

        if (!file_exists($hooksFile)) {
            return;
        }

        $hook_version = 1;
        $hook_array = [];
        include $hooksFile;

        foreach (['after_save', 'after_delete'] as $event) {
            if (!isset($hook_array[$event])) {
                continue;
            }
            $hook_array[$event] = array_values(array_filter(
                $hook_array[$event],
                static fn ($entry) => ($entry[3] ?? '') !== 'MeetingCalendarSyncLogicHook'
            ));
            if (empty($hook_array[$event])) {
                unset($hook_array[$event]);
            }
        }

        file_put_contents($hooksFile, $this->renderHooksFile($hook_array));

        $this->connection->executeStatement(
            "DELETE FROM config WHERE category = 'migrations' AND name = 'calendar_sync_hooks_installation'"
        );
    }
}
