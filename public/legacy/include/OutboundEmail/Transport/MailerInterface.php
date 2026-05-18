<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

interface MailerInterface
{
    public function send(
        MailTransportMessage $message,
        MailTransportAccountConfig $config
    ): MailTransportSendResult;
}
