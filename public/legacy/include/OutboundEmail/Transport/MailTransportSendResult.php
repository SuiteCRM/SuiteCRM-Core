<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class MailTransportSendResult
{
    /** @var bool */
    public $success = false;
    /** @var string */
    public $errorCode = '';
    /** @var string */
    public $errorMessage = '';
    /** @var string */
    public $providerRequestId = '';

    public static function success(string $providerRequestId = ''): self
    {
        $result = new self();
        $result->success = true;
        $result->providerRequestId = $providerRequestId;

        return $result;
    }

    public static function failure(string $errorCode, string $errorMessage): self
    {
        $result = new self();
        $result->success = false;
        $result->errorCode = $errorCode;
        $result->errorMessage = $errorMessage;

        return $result;
    }
}
