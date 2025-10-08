<?php

namespace App\Module\Campaigns\Service\Email\Targets;

use App\Data\Entity\Record;

interface EmailOptInManagerInterface
{
    public function isOptedIn(Record $targetRecord, Record $marketingRecord, string $campaignId, string $prospectListId): bool;

    public function addUnsubscribeLink(string $trackerId, string $emailBody, array $context): string;

    public function containsUnsubscribeLinkVariable(string $value): bool;
}
