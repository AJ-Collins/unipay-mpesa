<?php

namespace helpers;

use Yii;
use yii\httpclient\Client as HttpClient;
use yii\caching\CacheInterface;

/**
 * Simple standalone IP geolocation helper
 *
 * Usage:
 *   (new \helpers\IPinfo())->getInfo('41.89.128.3');
 *   (new \helpers\IPinfo())->getInfo(); // uses current visitor IP
 *
 * Features:
 * - Uses ip-api.com (free, no key needed)
 * - Caching (24 hours by default)
 * - refresh($ip) and invalidateCache($ip) methods
 */
class IPinfo
{
    /**
     * @var int Cache duration in seconds
     */
    public int $cacheDuration = 86400; // 24 hours

    /**
     * @var int HTTP timeout
     */
    public int $timeout = 5;

    private array $data = [];
    private bool $fromCache = false;
    private bool $success = false;

    /**
     * Get geolocation info for an IP
     *
     * @param string|null $ip If null, uses current client IP
     * @return array
     */
    public function getInfo(?string $ip = null): array
    {
        $ip = $ip ?: Yii::$app->request->userIP ?? '127.0.0.1';

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return [
                'success' => false,
                'message' => 'Invalid IP address',
                'ip'      => $ip,
            ];
        }

        $cacheKey = ['ipinfo', $ip];
        $cache = $this->getCache();

        // Try cache first
        if ($this->cacheDuration > 0 && $cache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== false) {
                $this->fromCache = true;
                $this->data = $cached;
                $this->success = ($cached['status'] ?? '') === 'success';
                return $this->formatResponse($ip);
            }
        }

        // Fetch from API
        $this->fetchFromApi($ip);

        // Cache result
        if ($cache && $this->cacheDuration > 0) {
            $duration = $this->success ? $this->cacheDuration : 300; // cache failures for 5 min
            $cache->set($cacheKey, $this->data, $duration);
        }

        return $this->formatResponse($ip);
    }

    /**
     * Force refresh from API (bypasses cache)
     */
    public function refresh(?string $ip = null): array
    {
        $this->invalidateCache($ip);
        return $this->getInfo($ip);
    }

    /**
     * Invalidate cache for specific IP
     */
    public function invalidateCache(?string $ip = null): bool
    {
        $ip = $ip ?: Yii::$app->request->userIP ?? '127.0.0.1';
        $cache = $this->getCache();
        if (!$cache) return false;

        $cacheKey = ['ipinfo', $ip];
        return $cache->delete($cacheKey);
    }

    /**
     * Clear all cached IP info (useful for console/admin)
     */
    public static function clearAllCache(): bool
    {
        $cache = Yii::$app->cache ?? null;
        return $cache ? $cache->flush() : false;
    }

    private function fetchFromApi(string $ip): void
    {
        $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query";

        try {
            $client = new HttpClient();
            $response = $client->createRequest()
                ->setUrl($url)
                ->setOptions(['timeout' => $this->timeout])
                ->send();

            if ($response->isOk) {
                $this->data = $response->data;
                $this->success = ($this->data['status'] ?? '') === 'success';
            } else {
                $this->data = ['status' => 'fail', 'message' => 'Service unavailable'];
                $this->success = false;
            }
        } catch (\Exception $e) {
            Yii::warning("IPinfo request failed for {$ip}: " . $e->getMessage());
            $this->data = ['status' => 'fail', 'message' => 'Request failed'];
            $this->success = false;
        }
    }

    private function formatResponse(string $ip): array
    {
        if (!$this->success) {
            return [
                'message' => $this->data['message'] ?? 'Unknown error',
                'ip_address'      => $ip,
            ];
        }

        return [
            // 'success'     => true,
            'ip_address'          => $this->data['query'] ?? $ip,
            'country'     => $this->data['country'] ?? null,
            'country_code' => $this->data['countryCode'] ?? null,
            'region_code'      => $this->data['region'] ?? null,
            'region_name'  => $this->data['regionName'] ?? null,
            'city'        => $this->data['city'] ?? null,
            'zip_code'         => $this->data['zip'] ?? null,
            'latitude'         => $this->data['lat'] ?? null,
            'longitude'         => $this->data['lon'] ?? null,
            'time_zone'    => $this->data['timezone'] ?? null,
            'isp'         => $this->data['isp'] ?? null,
            'organization'         => $this->data['org'] ?? null,
            'autonomous_system'          => $this->data['as'] ?? null,
        ];
    }

    private function getCache(): ?CacheInterface
    {
        return Yii::$app->cache ?? null;
    }
}