<?php

namespace OpenProvider\WhmcsRegistrar\src;

class RestClient
{
    private string $baseUrl;
    private bool $isSandbox;
    private ?string $bearer = null;

    public function __construct()
    {
        $this->isSandbox = ((string) Configuration::get('test_mode') === 'on');
        $this->baseUrl = $this->isSandbox
            ? (string) Configuration::get('restapi_url_sandbox')
            : (string) Configuration::get('api_url');
    }

    public function login(string $username, string $password): void
    {
        $url = rtrim($this->baseUrl, '/') . '/v1beta/auth/login';
        $payload = [
            'username' => $username,
            'password' => $password,
            'ip'       => '0.0.0.0',
        ];

        [$http, $json, $raw] = $this->curl('POST', $url, $payload);


        if ($http !== 200 || empty($json->data->token)) {
            \logModuleCall(
                'openprovider',
                'rest:/v1beta/auth/login:fail',
                $this->safeLogBody($payload),
                null,
                ['httpcode' => $http, 'json' => $json],
                []
            );
            throw new \RuntimeException($json->desc ?? 'Login failed', $http);
        }

        $this->bearer = $json->data->token;
    }

    public function createDomainToken(string $fqdn, string $zoneProvider = 'openprovider'): string
    {
        if (!$this->bearer) {
            throw new \LogicException('Not authenticated');
        }

        $url = rtrim($this->baseUrl, '/') . '/v1beta/dns/domain-token';

        [$http, $json] = $this->curl('POST', $url, [
            'domain'        => $fqdn,
            'zone_provider' => $zoneProvider,
        ], [
            'Authorization: Bearer ' . $this->bearer,
        ]);

        if ($http !== 200 || empty($json->data->token)) {
            throw new \RuntimeException($json->desc ?? 'domain-token failed', $http);
        }

        return $json->data->url ?? null;
    }

    /** ------------ Helpers ------------ */
    private function curl(string $method, string $url, array $body = [], array $headers = []): array
    {
        $ch = curl_init();
        $headers = array_merge(['Content-Type: application/json'], $headers);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (in_array($method, ['POST','PUT','DELETE'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($err);
        }

        curl_close($ch);
        $json = json_decode((string) $response);

        \logModuleCall(
            'openprovider',
            'rest:' . parse_url($url, PHP_URL_PATH),
            $this->safeLogBody($body),
            (string) $response,
            ['httpcode' => $httpCode, 'json' => $json],
            []
        );

        return [$httpCode, $json, (string) $response];
    }

    private function safeLogBody(array $body): array
    {
        if (isset($body['password'])) {
            $body['password'] = '********';
        }
        return $body;
    }
}
