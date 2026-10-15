<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class MailTransportAccountConfig
{
    /** @var string */
    public $transport = 'smtp';
    /** @var string */
    public $oauthConnectionId = '';
    /** @var string */
    public $senderMailbox = '';
    /** @var string */
    public $accessToken = '';
    /** @var string */
    public $replyToAddress = '';
    /** @var string */
    public $replyToName = '';
}
