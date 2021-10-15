<?php

namespace Payline\Cache;

use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Apc extends ApcuAdapter implements CacheInterface
{
    use CacheTrait;

    public function isAvailable()
    {
        return $this::isSupported();
    }
}