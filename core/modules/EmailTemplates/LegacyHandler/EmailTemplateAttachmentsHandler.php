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

namespace App\Module\EmailTemplates\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Attachments of an email template for the record view, kept where the
 * legacy template editor and the email senders look for them: notes of the template
 * (parent_id, parent_type Emails) with their file in the legacy upload directory.
 */
class EmailTemplateAttachmentsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'email-template-attachments';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The attachments of a template, or null when the user cannot see it.
     *
     * @return array<int, array{id: string, name: string, mimeType: string, size: int}>|null
     */
    public function list(string $templateId): ?array
    {
        return $this->inLegacy(function () use ($templateId) {
            $template = $this->getTemplate($templateId, 'DetailView');
            if ($template === null) {
                return null;
            }

            $attachments = [];
            foreach ($this->getNotes($template->id) as $note) {
                $file = 'upload://' . $note->id;
                $attachments[] = [
                    'id' => $note->id,
                    'name' => html_entity_decode((string)($note->filename ?: $note->name), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'mimeType' => (string)$note->file_mime_type,
                    'size' => file_exists($file) ? (int)filesize($file) : 0,
                ];
            }

            return $attachments;
        });
    }

    /**
     * Maximum size of an uploaded file (bytes, upload_maxsize config), 0 without limit.
     */
    public function getMaxSize(): int
    {
        return $this->inLegacy(static function () {
            global $sugar_config;

            return (int)($sugar_config['upload_maxsize'] ?? 0);
        });
    }

    /**
     * Attaches a file (base64 content) to a template, as the legacy editor: a note of the
     * template with the file. Files of a forbidden extension get .txt, as legacy uploads.
     */
    public function add(string $templateId, string $filename, string $content): bool
    {
        return $this->inLegacy(function () use ($templateId, $filename, $content) {
            global $sugar_config;

            $template = $this->getTemplate($templateId, 'Save');
            $data = base64_decode($content, true);
            $filename = trim(basename(str_replace('\\', '/', $filename)));
            $maxSize = (int)($sugar_config['upload_maxsize'] ?? 0);
            if ($template === null || $data === false || $data === '' || $filename === ''
                || ($maxSize > 0 && strlen($data) > $maxSize)) {
                return false;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($extension, $sugar_config['upload_badext'] ?? [], true)) {
                $filename .= '.txt';
            }
            $filename = mb_substr($filename, 0, 255);

            $note = \BeanFactory::newBean('Notes');
            $note->id = create_guid();
            $note->new_with_id = true;
            $note->name = $filename;
            $note->filename = $filename;
            $note->file_mime_type = (new \finfo(FILEINFO_MIME_TYPE))->buffer($data) ?: 'application/octet-stream';
            $note->parent_type = 'Emails';
            $note->parent_id = $template->id;

            if (file_put_contents('upload://' . $note->id, $data) === false) {
                return false;
            }
            $note->save();

            return true;
        });
    }

    /**
     * Removes an attachment of a template (deletes its note, as the legacy editor).
     */
    public function remove(string $templateId, string $noteId): bool
    {
        return $this->inLegacy(function () use ($templateId, $noteId) {
            $template = $this->getTemplate($templateId, 'Save');
            if ($template === null) {
                return false;
            }

            foreach ($this->getNotes($template->id) as $note) {
                if ($note->id === $noteId) {
                    $note->mark_deleted($note->id);

                    return true;
                }
            }

            return false;
        });
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }

    protected function getTemplate(string $id, string $access): ?\SugarBean
    {
        $template = $id !== '' ? \BeanFactory::getBean('EmailTemplates', $id) : null;

        return $template && !empty($template->id) && $template->ACLAccess($access) ? $template : null;
    }

    /**
     * @return \SugarBean[]
     */
    protected function getNotes(string $templateId): array
    {
        $db = \DBManagerFactory::getInstance();
        $notes = \BeanFactory::newBean('Notes')->get_full_list(
            'notes.date_entered',
            "notes.parent_id = '" . $db->quote($templateId) . "'"
        );

        return is_array($notes) ? $notes : [];
    }
}
