<?php

namespace Payline\Cache;

trait CacheTrait
{
    public function loadServicesEndpoints()
    {
        return $this->hasItem(self::CACHE_KEY) ? $this->getItem(self::CACHE_KEY)->get() : false;
    }

    public function saveServicesEndpoints(array $endpoints, $ttl)
    {
        $cachedItem = $this->getItem(self::CACHE_KEY);
        if (!$cachedItem->isHit())
        {
            $cachedItem->set($endpoints);
            $cachedItem->expiresAfter($ttl);
            $this->save($cachedItem);
        }
        return true;
    }

    public function hasServicesEndpoints()
    {
        return $this->hasItem(self::CACHE_KEY);
    }
}