<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache;

interface HttpCacheInterface
{
    public function clear(): void;
}
