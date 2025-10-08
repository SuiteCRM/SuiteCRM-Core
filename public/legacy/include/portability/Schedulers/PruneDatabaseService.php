<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

class PruneDatabaseService
{
    protected $db;

    public function __construct()
    {
        $this->db = DBManagerFactory::getInstance();
    }

    /**
     * Returns an array of tables that can be pruned.
     * @return array
     */
    public function getTablesToPrune(): array
    {
        global $beanList;

        $exclusions = [
            'archived_documents_media_objects' => 'ArchivedDocumentMediaObject',
            'private_documents_media_objects' => 'PrivateDocumentMediaObject',
            'private_images_media_objects' => 'PrivateImageMediaObject',
            'public_documents_media_objects' => 'PublicDocumentMediaObject',
            'public_images_media_objects' => 'PublicImageMediaObject',
            'media_objects' => 'MediaObject',
        ];


        $tables = [];


        foreach ($beanList as $module => $beanName) {
            $bean = BeanFactory::newBean($module);

            $table = $bean->table_name ?? '';

            if (!empty($table) && empty($exclusions[$table]) ) {
                $tables[$table] = $module;
            }
        }

        $tablesArray = $this->db->getTablesArray();

        foreach ($tablesArray as $table) {
            if (!empty($tables[$table])) {
                continue;
            }

            if (!empty($exclusions[$table])) {
                continue;
            }

            $tables[$table] = '';
        }

        return $tables;
    }

    /**
     * Returns an array of columns for a given table.
     * @param string $table
     * @return array
     */
    protected function getTableColumns(string $table): array
    {
        return $this->db->get_columns($table);
    }

    /**
     * Returns an array of custom columns for a given table if it has a corresponding _cstm table.
     * @param string $table
     * @param array $tables
     * @return array
     */
    protected function getCustomColumns(string $table, array $tables): array
    {
        $customColumns = array();
        if (array_search($table . '_cstm', $tables, true)) {
            $customColumns = $this->getTableColumns($table . '_cstm');
            if (empty($customColumns['id_c'])) {
                $customColumns = array();
            }
        }

        return $customColumns;
    }

    public function prune(callable $onRecordDelete = null): bool
    {
        global $log;

        $log->info('----->Scheduler fired job of type pruneDatabase()');

        $backupDir = sugar_cached('backups');
        $backupFile = 'backup-pruneDatabase-GMT0_' . gmdate('Y_m_d-H_i_s', time()) . '.php';

        if (empty($this->db)) {
            return false;
        }

        $tables = $this->getTablesToPrune();

        $queryString = array();

        if (!empty($tables)) {
            foreach ($tables as $table => $module) {
                $columns = $this->getTableColumns($table);
                // no deleted - won't delete
                if (empty($columns['deleted'])) {
                    continue;
                }

                $custom_columns = $this->getCustomColumns($table, $tables);

                $qDel = "SELECT * FROM $table WHERE deleted = 1";
                $rDel = $this->db->query($qDel);

                // make a backup INSERT query if we are deleting.
                while ($aDel = $this->db->fetchByAssoc($rDel, false)) {
                    // build column names

                    $queryString[] = $this->db->insertParams($table, $columns, $aDel, null, false);


                    if (!empty($aDel['id']) && $onRecordDelete !== null && is_callable($onRecordDelete)) {
                        $onRecordDelete($module, $aDel['id']);
                    }

                    if (!empty($custom_columns) && !empty($aDel['id'])) {
                        $qDelCstm = 'SELECT * FROM ' . $table . '_cstm WHERE id_c = ' . $this->db->quoted($aDel['id']);
                        $rDelCstm = $this->db->query($qDelCstm);

                        // make a backup INSERT query if we are deleting.
                        while ($aDelCstm = $this->db->fetchByAssoc($rDelCstm)) {
                            $queryString[] = $this->db->insertParams($table, $custom_columns, $aDelCstm, null, false);
                        } // end aDel while()

                        $this->db->query('DELETE FROM ' . $table . '_cstm WHERE id_c = ' . $this->db->quoted($aDel['id']));
                    }
                } // end aDel while()
                // now do the actual delete
                $this->db->query('DELETE FROM ' . $table . ' WHERE deleted = 1');
            } // foreach() tables

            if (!file_exists($backupDir) || !file_exists($backupDir . '/' . $backupFile)) {
                // create directory if not existent
                mkdir_recursive($backupDir, false);
            }
            // write cache file

            write_array_to_file('pruneDatabase', $queryString, $backupDir . '/' . $backupFile);
            return true;
        }
        return false;
    }
}
