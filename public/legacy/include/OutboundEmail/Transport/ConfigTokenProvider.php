<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class ConfigTokenProvider implements TokenProviderInterface
{
    public function getAccessToken(MailTransportAccountConfig $config): string
    {
        $explicitToken = trim((string)($config->accessToken ?? ''));
        if ($explicitToken !== '') {
            return $explicitToken;
        }

        $configuredToken = trim((string)$this->getFirstConfigValue([
            'graph_mailer_access_token',
            'graph_mail_access_token',
        ], ''));
        if ($configuredToken !== '') {
            return $configuredToken;
        }

        $connectionId = trim((string)($config->oauthConnectionId ?? ''));
        if ($connectionId !== '') {
            $token = $this->extractTokenFromOAuthConnection($connectionId);
            if ($token !== '') {
                return $token;
            }
        }

        return '';
    }

    /**
     * PR1 scope: best-effort read from existing OAuth connection.
     * Refresh/rotation logic is intentionally deferred to PR2.
     */
    private function extractTokenFromOAuthConnection(string $connectionId): string
    {
        try {
            if (!class_exists('BeanFactory')) {
                require_once 'data/BeanFactory.php';
            }
            /** @var object|null $connection */
            $connection = BeanFactory::getBean('ExternalOAuthConnection', $connectionId);
            if (empty($connection)) {
                return '';
            }

            $raw = trim((string)($connection->access_token ?? ''));
            return $this->extractAccessToken($raw);
        } catch (\Throwable $e) {
            $GLOBALS['log']->warn('ConfigTokenProvider: failed to read OAuth access token: ' . $e->getMessage());
        }

        return '';
    }

    private function getFirstConfigValue(array $keys, $default = null)
    {
        $config = $GLOBALS['sugar_config'] ?? [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $config)) {
                return $config[$key];
            }
        }

        return $default;
    }

    private function extractAccessToken(string $value): string
    {
        $value = trim(str_replace(["\r", "\n"], '', $value));
        if ($value === '') {
            return '';
        }

        // Plain JWT or opaque token.
        if (strpos($value, '.') !== false || preg_match('/^[A-Za-z0-9_\-~\+\/=]{20,}$/', $value)) {
            if (substr_count($value, '.') === 2 || strpos($value, '{') === false) {
                return $value;
            }
        }

        // JSON object
        if ($value[0] === '{' || $value[0] === '[') {
            $json = json_decode($value, true);
            if (is_array($json)) {
                foreach (['access_token', 'token', 'AccessToken', 'accessToken'] as $key) {
                    if (!empty($json[$key])) {
                        return trim((string)$json[$key]);
                    }
                }
            }
        }

        // Serialized payload
        if (preg_match('/^(a|s):\d+:/', $value)) {
            $decoded = @unserialize($value);
            if (is_array($decoded)) {
                foreach (['access_token', 'token', 'AccessToken', 'accessToken'] as $key) {
                    if (!empty($decoded[$key])) {
                        return trim((string)$decoded[$key]);
                    }
                }
            } elseif (is_string($decoded) && $decoded !== '') {
                return trim($decoded);
            }
        }

        // base64/base64url wrapper
        $decoded = $this->tryBase64Decode($value);
        if ($decoded !== '') {
            return $this->extractAccessToken($decoded);
        }

        return '';
    }

    private function tryBase64Decode(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $normalized = strtr($value, '-_', '+/');
        $remainder = strlen($normalized) % 4;
        if ($remainder > 0) {
            $normalized .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($normalized, true);
        if (!is_string($decoded) || $decoded === '') {
            return '';
        }

        if (preg_match('/[^\x09\x0A\x0D\x20-\x7E]/', $decoded)) {
            return '';
        }

        return trim($decoded);
    }
}
