<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Contract;

interface HttpCacheInterface
{
    /**
     * Delete all cache items, that have been recorded in the caching storage
     */
    public function clear(): void;
}
