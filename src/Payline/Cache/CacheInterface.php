<?php

declare(strict_types=1);

namespace Payline\Cache;

interface CacheInterface
{
    public const CACHE_KEY = 'payline_sdk_services_urls';

    /**
     * @return bool
     */
    public function isAvailable(): bool;


    /**
     * @return bool
     */
    public function hasServicesEndpoints(): bool;


    /**
     * @return array|bool
     */
    public function loadServicesEndpoints();

    /**
     * @param array $endpoints
     * @param int $ttl
     * @return bool
     */
    public function saveServicesEndpoints(array $endpoints, $ttl): bool;
}
