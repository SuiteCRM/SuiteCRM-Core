<?php

namespace App\Module\Documents\LegacyHandler;

interface DocumentsManagerInterface
{
    public function getLatestRevision(string $documentId): string;

    public function getLatestRevisionId(string $documentId): string;
}
