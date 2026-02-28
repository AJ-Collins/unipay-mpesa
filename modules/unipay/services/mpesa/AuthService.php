<?php

namespace unipay\services\mpesa;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Yii;

class AuthService
{
    private const CACHE_KEY = 'mpesa_access_token';
    private const CACHE_TTL = 3500; // Safaricom tokens live ~3600 s;

    /**
     * Retrieve a valid Safaricom OAuth access token.
     * The token is cached to avoid unnecessary round-trips.
     *
     * @return string Access token
     * @throws \RuntimeException When authentication fails
     */
    public static function getToken(): string
    {
        if (Yii::$app->has('cache')) {
            $cached = Yii::$app->cache->get(self::CACHE_KEY);
            if ($cached !== false) {
                return $cached;
            }
        }

        $consumerKey    = $_SERVER['MPESA_CONSUMER_KEY']    ?? null;
        $consumerSecret = $_SERVER['MPESA_CONSUMER_SECRET'] ?? null;
        $baseUrl        = $_SERVER['MPESA_BASE_URL']        ?? null;

        if (!$consumerKey || !$consumerSecret || !$baseUrl) {
            throw new \RuntimeException(
                '[unipay] M-Pesa credentials (MPESA_CONSUMER_KEY / MPESA_CONSUMER_SECRET / MPESA_BASE_URL) are not set.'
            );
        }

        try {
            $client      = new Client(['base_uri' => $baseUrl, 'timeout' => 15]);
            $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

            $response = $client->get('/oauth/v1/generate', [
                'query'   => ['grant_type' => 'client_credentials'],
                'headers' => ['Authorization' => 'Basic ' . $credentials],
            ]);

            $data  = json_decode((string)$response->getBody(), true);
            $token = $data['access_token'] ?? null;

            if (empty($token)) {
                throw new \RuntimeException('[unipay] Empty access_token in Safaricom response.');
            }

            // Cache the token
            if (Yii::$app->has('cache')) {
                Yii::$app->cache->set(self::CACHE_KEY, $token, self::CACHE_TTL);
            }

            return $token;

        } catch (RequestException $e) {
            $body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : 'No response';
            Yii::error('[unipay] AuthService::getToken HTTP error: ' . $e->getMessage() . ' | Body: ' . $body, __METHOD__);
            throw new \RuntimeException('[unipay] Failed to obtain M-Pesa token: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            Yii::error('[unipay] AuthService::getToken error: ' . $e->getMessage(), __METHOD__);
            throw new \RuntimeException('[unipay] Failed to obtain M-Pesa token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Invalidate the cached token (useful after auth errors).
     */
    public static function flushToken(): void
    {
        if (Yii::$app->has('cache')) {
            Yii::$app->cache->delete(self::CACHE_KEY);
        }
    }
}