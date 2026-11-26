<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class GraphMailerAdapter implements MailerInterface
{
    /** @var TokenProviderInterface */
    private $tokenProvider;
    /** @var GraphHttpClient */
    private $httpClient;

    public function __construct(TokenProviderInterface $tokenProvider, ?GraphHttpClient $httpClient = null)
    {
        $this->tokenProvider = $tokenProvider;
        $this->httpClient = $httpClient ?: new GraphHttpClient();
    }

    public function send(MailTransportMessage $message, MailTransportAccountConfig $config): MailTransportSendResult
    {
        if (!empty($message->attachments)) {
            return MailTransportSendResult::failure(
                'graph_skeleton_attachments_not_supported',
                'Graph skeleton adapter supports sendMail without attachments only.'
            );
        }

        $accessToken = trim($this->tokenProvider->getAccessToken($config));
        if ($accessToken === '') {
            return MailTransportSendResult::failure(
                'graph_missing_access_token',
                'Graph access token is missing. Configure token provider before enabling Graph skeleton send.'
            );
        }

        $payload = [
            'message' => [
                'subject' => (string)$message->subject,
                'body' => [
                    'contentType' => $message->htmlBody !== '' ? 'HTML' : 'Text',
                    'content' => $message->htmlBody !== '' ? (string)$message->htmlBody : (string)$message->textBody,
                ],
                'toRecipients' => $this->mapRecipients((array)$message->to),
            ],
            'saveToSentItems' => true,
        ];

        if (!empty($message->cc)) {
            $payload['message']['ccRecipients'] = $this->mapRecipients((array)$message->cc);
        }
        if (!empty($message->bcc)) {
            $payload['message']['bccRecipients'] = $this->mapRecipients((array)$message->bcc);
        }
        if (!empty($config->replyToAddress)) {
            $payload['message']['replyTo'] = [[
                'emailAddress' => [
                    'address' => (string)$config->replyToAddress,
                    'name' => (string)$config->replyToName,
                ],
            ]];
        }
        if (!empty($message->fromName) || !empty($message->from)) {
            $payload['message']['from'] = [
                'emailAddress' => [
                    'address' => (string)$message->from,
                    'name' => (string)$message->fromName,
                ],
            ];
        }

        $endpoint = $this->resolveEndpoint((string)($config->senderMailbox ?? ''), (string)($message->from ?? ''));
        $url = 'https://graph.microsoft.com' . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $response = $this->httpClient->postJson($url, $headers, $payload);
        if (empty($response['ok'])) {
            return MailTransportSendResult::failure(
                'graph_http_client_error',
                'Graph HTTP client error: ' . (string)($response['error'] ?? 'unknown')
            );
        }

        $status = (int)($response['status'] ?? 0);
        if ($status !== 202 && $status !== 200) {
            $bodyPreview = substr((string)($response['body'] ?? ''), 0, 500);

            return MailTransportSendResult::failure(
                'graph_http_' . $status,
                'Graph sendMail failed with HTTP ' . $status . '. Response: ' . $bodyPreview
            );
        }

        $headerMap = (array)($response['headers'] ?? []);
        $requestId = (string)($headerMap['request-id'] ?? ($headerMap['client-request-id'] ?? ''));

        return MailTransportSendResult::success($requestId);
    }

    private function resolveEndpoint(string $senderMailbox, string $messageFrom): string
    {
        $senderMailbox = trim($senderMailbox);
        if ($senderMailbox === '') {
            $senderMailbox = trim($messageFrom);
        }

        if ($senderMailbox !== '' && strpos($senderMailbox, '@') !== false) {
            return '/v1.0/users/' . rawurlencode($senderMailbox) . '/sendMail';
        }

        return '/v1.0/me/sendMail';
    }

    /**
     * @param string[] $emails
     * @return array<int, array<string, array<string, string>>>
     */
    private function mapRecipients(array $emails): array
    {
        $out = [];
        foreach ($emails as $email) {
            $email = trim((string)$email);
            if ($email === '') {
                continue;
            }
            $out[] = [
                'emailAddress' => [
                    'address' => $email,
                ],
            ];
        }

        return $out;
    }
}
