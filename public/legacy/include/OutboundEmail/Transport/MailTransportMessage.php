<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class MailTransportMessage
{
    /** @var string */
    public $from = '';
    /** @var string */
    public $fromName = '';
    /** @var string[] */
    public $to = [];
    /** @var string[] */
    public $cc = [];
    /** @var string[] */
    public $bcc = [];
    /** @var string */
    public $subject = '';
    /** @var string */
    public $htmlBody = '';
    /** @var string */
    public $textBody = '';
    /** @var array<int, array<string, mixed>> */
    public $attachments = [];
}
