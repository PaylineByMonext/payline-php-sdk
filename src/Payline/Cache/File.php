<?php

namespace Payline\Cache;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class File extends FilesystemAdapter implements CacheInterface
{
    use CacheTrait;

    public function isAvailable()
    {
        return true;
    }
}