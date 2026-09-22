<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

interface TokenProviderInterface
{
    public function getAccessToken(MailTransportAccountConfig $config): string;
}
