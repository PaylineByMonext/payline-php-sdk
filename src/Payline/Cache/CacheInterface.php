<?php

namespace Payline\Cache;

interface CacheInterface
{
    const CACHE_KEY = 'payline_sdk_services_urls';

    /**
     * @return bool
     */
    public function isAvailable();


    /**
     * @return bool
     */
    public function hasServicesEndpoints();


    /**
     * @return array|bool
     */
    public function loadServicesEndpoints();

    /**
     * @param array $endpoints
     * @param int $ttl
     * @return bool
     */
    public function saveServicesEndpoints(array $endpoints, $ttl);
}