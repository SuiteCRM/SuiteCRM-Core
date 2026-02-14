<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 */

namespace App\EmailTemplates\LegacyHandler;

use App\Authentication\LegacyHandler\UserHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailTemplatesToolsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'email-templates-tools';

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected UserHandler $userHandler,
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $requestStack);
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Legacy-compatible template variable definitions.
     */
    public function getVariableDefs(): array
    {
        $this->init();
        $this->startLegacyApp('EmailTemplates');

        try {
            /* @noinspection PhpIncludeInspection */
            require_once 'modules/EmailTemplates/templateFields.php';

            if (!function_exists('generateFieldDefsJS2')) {
                throw new \RuntimeException('Legacy generateFieldDefsJS2 not found');
            }

            $js = generateFieldDefsJS2();
            $fieldDefs = $this->extractJsonVarAssignment($js, 'field_defs');

            $modules = $this->getVariableModules();

            return [
                'modules' => $modules,
                'fieldDefs' => $fieldDefs,
            ];
        } finally {
            $this->close();
        }
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    protected function getVariableModules(): array
    {
        global $app_list_strings, $beanList, $beanFiles;

        $moduleList = $app_list_strings['moduleList'] ?? [];
        $moduleListSingular = $app_list_strings['moduleListSingular'] ?? [];

        // Keep labels consistent with legacy (Contacts is a composite label).
        $contactsLabel = implode('/', [
            $moduleListSingular['Contacts'] ?? 'Contact',
            $moduleListSingular['Leads'] ?? 'Lead',
            $moduleListSingular['Prospects'] ?? 'Target',
        ]);

        $modules = [];
        foreach ($moduleList as $key => $name) {
            if (!isset($beanList[$key], $beanFiles[$beanList[$key]])) {
                continue;
            }
            if (str_starts_with($key, 'AOW_') || str_starts_with($key, 'zr2_')) {
                continue;
            }

            $label = $moduleListSingular[$key] ?? ($moduleList[$key] ?? (string)$key);
            if ($key === 'Contacts') {
                $label = $contactsLabel;
            }

            $modules[] = [
                'key' => (string)$key,
                'label' => (string)$label,
            ];
        }

        usort($modules, static fn(array $a, array $b): int => strnatcasecmp($a['label'] ?? '', $b['label'] ?? ''));

        return $modules;
    }

    /**
     * @return array<int, array{id: string, filename: string, mimeType: string, downloadUrl: string}>
     */
    public function listAttachments(string $templateId): array
    {
        $template = $this->getTemplateOr404($templateId, 'view');

        try {
            $note = \BeanFactory::newBean('Notes');
            $db = $note->db;
            $safeId = $db->quote($template->id);

            $where = "notes.parent_id='{$safeId}' AND notes.filename IS NOT NULL";
            $notes = $note->get_full_list('notes.name', $where, true) ?? [];

            $items = [];
            foreach ($notes as $n) {
                if (empty($n->id) || empty($n->filename)) {
                    continue;
                }

                $items[] = [
                    'id' => (string)$n->id,
                    'filename' => (string)$n->filename,
                    'mimeType' => (string)($n->file_mime_type ?? ''),
                    // Explicitly point at legacy download entrypoint.
                    'downloadUrl' => '/legacy/index.php?entryPoint=download&id=' . rawurlencode((string)$n->id) . '&type=Notes',
                ];
            }

            return $items;
        } finally {
            $this->close();
        }
    }

    public function uploadAttachment(string $templateId, UploadedFile $file): array
    {
        $template = $this->getTemplateOr404($templateId, 'edit');

        if (!$file->isValid()) {
            throw new BadRequestHttpException('Upload failed');
        }

        try {
            /** @var \Note $note */
            $note = \BeanFactory::newBean('Notes');

            global $current_user;

            $originalName = $file->getClientOriginalName() ?: $file->getFilename();
            $note->name = $originalName;
            $note->filename = $originalName;
            $note->parent_id = $template->id;
            $note->parent_type = 'Emails';
            $note->assigned_user_id = $current_user->id;
            $note->file_mime_type = $file->getMimeType() ?? '';

            $note->safeAttachmentName();

            $noteId = $note->save();
            if (empty($noteId)) {
                throw new BadRequestHttpException('Failed to save attachment');
            }

            $target = "upload://{$noteId}";
            if (!@copy($file->getPathname(), $target)) {
                throw new BadRequestHttpException('Failed to store attachment');
            }

            return [
                'id' => (string)$noteId,
                'filename' => (string)$note->filename,
                'mimeType' => (string)($note->file_mime_type ?? ''),
                'downloadUrl' => '/legacy/index.php?entryPoint=download&id=' . rawurlencode((string)$noteId) . '&type=Notes',
            ];
        } finally {
            $this->close();
        }
    }

    public function attachDocument(string $templateId, string $documentId): array
    {
        $template = $this->getTemplateOr404($templateId, 'edit');

        if ($documentId === '') {
            throw new BadRequestHttpException('documentId is required');
        }

        try {
            $doc = \BeanFactory::newBean('Documents');
            $docRev = \BeanFactory::newBean('DocumentRevisions');

            $doc->retrieve($documentId);
            if (empty($doc->id)) {
                throw new NotFoundHttpException('Document not found');
            }

            $docRev->retrieve($doc->document_revision_id);
            if (empty($docRev->id)) {
                throw new NotFoundHttpException('Document revision not found');
            }

            /** @var \Note $note */
            $note = \BeanFactory::newBean('Notes');

            global $current_user;

            $note->name = (string)($doc->document_name ?? $docRev->filename ?? 'Document');
            $note->filename = (string)($docRev->filename ?? '');
            $note->description = (string)($doc->description ?? '');
            $note->parent_id = $template->id;
            $note->parent_type = 'Emails';
            $note->assigned_user_id = $current_user->id;
            $note->file_mime_type = (string)($docRev->file_mime_type ?? '');

            $note->safeAttachmentName();

            $noteId = $note->save();
            if (empty($noteId)) {
                throw new BadRequestHttpException('Failed to save attachment');
            }

            /* @noinspection PhpIncludeInspection */
            require_once 'include/upload_file.php';

            \UploadFile::duplicate_file($docRev->id, $noteId, $note->filename);

            return [
                'id' => (string)$noteId,
                'filename' => (string)$note->filename,
                'mimeType' => (string)($note->file_mime_type ?? ''),
                'downloadUrl' => '/legacy/index.php?entryPoint=download&id=' . rawurlencode((string)$noteId) . '&type=Notes',
            ];
        } finally {
            $this->close();
        }
    }

    public function deleteAttachment(string $templateId, string $noteId): void
    {
        $template = $this->getTemplateOr404($templateId, 'edit');

        if ($noteId === '') {
            throw new BadRequestHttpException('noteId is required');
        }

        try {
            /** @var \Note $note */
            $note = \BeanFactory::getBean('Notes', $noteId, ['encode' => false]);
            if (empty($note) || empty($note->id)) {
                throw new NotFoundHttpException('Attachment not found');
            }

            if ((string)($note->parent_id ?? '') !== (string)$template->id) {
                throw new AccessDeniedHttpException();
            }

            // Legacy UI only marks notes deleted.
            $note->mark_deleted($note->id);
        } finally {
            $this->close();
        }
    }

    protected function getTemplateOr404(string $templateId, string $aclAction): \SugarBean
    {
        $this->init();
        $this->startLegacyApp('EmailTemplates');

        /** @var \EmailTemplate $template */
        $template = \BeanFactory::getBean('EmailTemplates', $templateId, ['encode' => false]);
        if (empty($template) || empty($template->id)) {
            $this->close();
            throw new NotFoundHttpException('Email template not found');
        }

        if (!$template->ACLAccess($aclAction)) {
            $this->close();
            throw new AccessDeniedHttpException();
        }

        // Mirror legacy restriction: system templates are admin-only for edits.
        if ($aclAction === 'edit' && ($template->type ?? '') === 'system' && !$this->userHandler->isCurrentUserAdmin()) {
            $this->close();
            throw new AccessDeniedHttpException();
        }

        return $template;
    }

    /**
     * Extract JSON from a legacy JS assignment like: var field_defs = {...};
     */
    protected function extractJsonVarAssignment(string $js, string $varName): array
    {
        $prefix = "var {$varName} =";
        $pos = strpos($js, $prefix);
        if ($pos === false) {
            throw new \RuntimeException("Variable {$varName} not found in JS output");
        }

        $jsonPart = trim(substr($js, $pos + strlen($prefix)));
        if (str_ends_with($jsonPart, ';')) {
            $jsonPart = substr($jsonPart, 0, -1);
        }

        $decoded = json_decode($jsonPart, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException("Failed to decode {$varName} JSON");
        }

        return $decoded;
    }
}
