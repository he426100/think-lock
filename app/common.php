<?php

declare(strict_types=1);

use think\facade\Cache;

/**
 * 获取redis实例
 *
 * @return \Redis
 */
function get_redis(): \Redis
{
    return Cache::store()->handler();
}
