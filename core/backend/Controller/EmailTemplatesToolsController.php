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

namespace App\Controller;

use App\EmailTemplates\LegacyHandler\EmailTemplatesToolsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailTemplatesToolsController extends AbstractController
{
    public function __construct(
        protected EmailTemplatesToolsHandler $tools,
    ) {
    }

    #[Route('/api/email-templates/template-field-defs', name: 'email_templates_template_field_defs', methods: ['GET'])]
    public function templateFieldDefs(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->json($this->tools->getVariableDefs());
    }

    #[Route('/api/email-templates/{id}/attachments', name: 'email_templates_attachments_list', methods: ['GET'])]
    public function listAttachments(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->json([
            'items' => $this->tools->listAttachments($id),
        ]);
    }

    #[Route('/api/email-templates/{id}/attachments/upload', name: 'email_templates_attachments_upload', methods: ['POST'])]
    public function uploadAttachment(Request $request, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'file is required'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->tools->uploadAttachment($id, $file));
    }

    #[Route('/api/email-templates/{id}/attachments/document', name: 'email_templates_attachments_document', methods: ['POST'])]
    public function attachDocument(Request $request, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $payload = json_decode($request->getContent() ?: '{}', true);
        $documentId = (string)($payload['documentId'] ?? '');

        return $this->json($this->tools->attachDocument($id, $documentId));
    }

    #[Route('/api/email-templates/{id}/attachments/{noteId}', name: 'email_templates_attachments_delete', methods: ['DELETE'])]
    public function deleteAttachment(string $id, string $noteId): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->tools->deleteAttachment($id, $noteId);

        return $this->json(['ok' => true]);
    }
}
