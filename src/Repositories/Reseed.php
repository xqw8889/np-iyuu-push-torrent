<?php

namespace NexusPlugin\IyuuPushTorrent\Repositories;

/**
 * 辅种计算种子特征码
 */
interface Reseed
{
    /**
     * 契约方法
     * @param int $torrent_id
     * @return array|null
     */
    public static function reseed(int $torrent_id): ?array;
}
