<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class GraphHttpClient
{
    /**
     * @param string $url
     * @param array<int, string> $headers
     * @param array<string, mixed> $payload
     * @return array{ok: bool, status: int, headers: array<string, string>, body: string, error: string}
     */
    public function postJson(string $url, array $headers, array $payload): array
    {
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'status' => 0,
                'headers' => [],
                'body' => '',
                'error' => 'cURL extension is not available',
            ];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'ok' => false,
                'status' => 0,
                'headers' => [],
                'body' => '',
                'error' => 'Unable to initialize cURL',
            ];
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return [
                'ok' => false,
                'status' => 0,
                'headers' => [],
                'body' => '',
                'error' => 'JSON encode failed: ' . json_last_error_msg(),
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'ok' => false,
                'status' => $status,
                'headers' => [],
                'body' => '',
                'error' => $error !== '' ? $error : 'Unknown cURL error',
            ];
        }

        $rawHeaders = (string)substr($response, 0, $headerSize);
        $rawBody = (string)substr($response, $headerSize);

        return [
            'ok' => true,
            'status' => $status,
            'headers' => $this->parseHeaders($rawHeaders),
            'body' => $rawBody,
            'error' => '',
        ];
    }

    /**
     * @param string $rawHeaders
     * @return array<string, string>
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $headerBlocks = preg_split("/\r\n\r\n|\n\n/", trim($rawHeaders)) ?: [];
        $lastBlock = end($headerBlocks);
        if (!is_string($lastBlock)) {
            return $headers;
        }

        $lines = preg_split("/\r\n|\n/", $lastBlock) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, ':') === false) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $name = strtolower(trim($name));
            $value = trim($value);
            if ($name !== '') {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
