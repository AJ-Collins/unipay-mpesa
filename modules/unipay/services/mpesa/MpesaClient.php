<?php

namespace unipay\services\mpesa;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Yii;

class MpesaClient
{
    protected Client $client;

    public function __construct()
    {
        $this->client = $this->buildClient();
    }

    /**
     * Guzzle client authenticated with a fresh token.
     */
    protected function buildClient(): Client
    {
        return new Client([
            'base_uri' => $_SERVER['MPESA_BASE_URL'] ?? '',
            'timeout'  => 30,
            'headers'  => [
                'Authorization' => 'Bearer ' . AuthService::getToken(),
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * POST a JSON payload to a Safaricom endpoint.
     *
     * Retries once with a fresh token on 401 Unauthorized.
     *
     * @param  string $endpoint  e.g. '/mpesa/b2c/v3/paymentrequest'
     * @param  array  $data      Associative array (will be JSON-encoded)
     * @return array             Decoded response body
     * @throws \RuntimeException On HTTP or network errors
     */
    protected function request(string $endpoint, array $data): array
    {
        try {
            return $this->doRequest($endpoint, $data);

        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                Yii::warning('[unipay] 401 on ' . $endpoint . ' — refreshing token and retrying.', __METHOD__);
                AuthService::flushToken();
                $this->client = $this->buildClient();
                return $this->doRequest($endpoint, $data);
            }

            $body = (string)$e->getResponse()->getBody();
            Yii::error('[unipay] HTTP ' . $e->getResponse()->getStatusCode() . ' on ' . $endpoint . ': ' . $body, __METHOD__);
            throw new \RuntimeException('[unipay] M-Pesa API error (' . $e->getResponse()->getStatusCode() . '): ' . $body, 0, $e);

        } catch (RequestException $e) {
            $body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : 'No response';
            Yii::error('[unipay] RequestException on ' . $endpoint . ': ' . $e->getMessage() . ' | ' . $body, __METHOD__);
            throw new \RuntimeException('[unipay] M-Pesa request failed: ' . $e->getMessage(), 0, $e);

        } catch (\Throwable $e) {
            Yii::error('[unipay] Unexpected error on ' . $endpoint . ': ' . $e->getMessage(), __METHOD__);
            throw new \RuntimeException('[unipay] Unexpected M-Pesa error: ' . $e->getMessage(), 0, $e);
        }
    }

    private function doRequest(string $endpoint, array $data): array
    {
        $response = $this->client->post($endpoint, ['json' => $data]);
        $decoded  = json_decode((string)$response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('[unipay] Invalid JSON from Safaricom on ' . $endpoint);
        }

        return $decoded ?? [];
    }

    /**
     * Callback/result URL from environment + path.
     */
    protected function callbackUrl(string $path): string
    {
        $base = rtrim($_SERVER['MPESA_CALLBACK_BASE'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}