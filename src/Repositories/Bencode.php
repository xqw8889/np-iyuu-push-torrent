<?php

namespace NexusPlugin\IyuuPushTorrent\Repositories;


use Exception;
use RuntimeException;
use Nexus\Database\NexusDB;

/**
 * 种子元数据编码与解码
 * - 自研的种子特征码提取算法 https://www.iyuu.cn
 * - 种子文件编码与解码来自 https://github.com/Rhilip/Bencode
 */
class Bencode implements Reseed
{
    /**
     * Decodes a BEncoded string to the following values:
     * - Dictionary (starts with d, ends with e)
     * - List (starts with l, ends with e
     * - Integer (starts with i, ends with e
     * - String (starts with number denoting number of characters followed by : and then the string)
     *
     * @see https://wiki.theory.org/index.php/BitTorrentSpecification
     *
     * @param mixed $data
     * @param int $pos
     * @return mixed
     */
    public static function decode(mixed $data, int &$pos = 0): mixed
    {
        $start_decode = ($pos === 0);   // If it is the root call ?
        if ($start_decode && (!is_string($data) || strlen($data) == 0)) {
            throw new \RuntimeException('Decode Input is not valid String.');
        }

        if ($pos >= strlen($data)) {
            throw new \RuntimeException('Unterminated bencode string literal');
        }

        if ($data[$pos] === 'd') {
            $pos++;
            $return = [];
            while ($data[$pos] !== 'e') {
                $key = self::decode($data, $pos);
                $value = self::decode($data, $pos);
                if ($key === null || $value === null) {
                    break;
                }
                if (!is_string($key)) {
                    throw new \RuntimeException('Non string key found in the dictionary.');
                } elseif (array_key_exists($key, $return)) {
                    throw new \RuntimeException('Duplicate Dictionary key exist before: ' . $key);
                }
                $return[$key] = $value;
            }
            ksort($return, SORT_STRING);
            $pos++;
        } elseif ($data[$pos] === 'l') {
            $pos++;
            $return = [];
            while ($data[$pos] !== 'e') {
                $value = self::decode($data, $pos);
                $return[] = $value;
            }
            $pos++;
        } elseif ($data[$pos] === 'i') {
            $pos++;
            $digits = strpos($data, 'e', $pos) - $pos;
            $value = substr($data, $pos, $digits);
            $return = self::checkInteger($value);
            $pos += $digits + 1;
        } else {
            $digits = strpos($data, ':', $pos) - $pos;
            $len = self::checkInteger(substr($data, $pos, $digits));
            if ($len < 0) {
                throw new \RuntimeException('Cannot have non-digit values for String length');
            }

            $pos += ($digits + 1);
            $return = substr($data, $pos, $len);

            if (strlen($return) != $len) {  // Check for String length is match or not
                throw new \RuntimeException('String length is not match for: ' . $return . ', want ' . $len);
            }

            $pos += $len;
        }

        if ($start_decode && $pos !== strlen($data)) {
            throw new \RuntimeException('Could not fully decode bencode string');
        }
        return $return;
    }

    /**
     * 将任意数据编码为bencode字符串
     * @param mixed $data 数据
     * @return string
     */
    public static function encode($data): string
    {
        if (is_array($data)) {
            $return = '';

            // PHP 8.0 兼容的数组类型判断
            if (self::isList($data)) {
                $return .= 'l';
                foreach ($data as $value) {
                    $return .= self::encode($value);
                }
            } else {
                $return .= 'd';
                ksort($data, SORT_STRING);
                foreach ($data as $key => $value) {
                    $return .= self::encode((string)$key);
                    $return .= self::encode($value);
                }
            }
            $return .= 'e';
        } elseif (is_integer($data)) {
            $return = 'i' . $data . 'e';
        } else {
            $return = strlen($data) . ':' . $data;
        }
        return $return;
    }

    /**
     * 判断数组是否为列表（PHP 8.0兼容版本）
     * @param array $array
     * @return bool
     */
    private static function isList(array $array): bool
    {
        if (function_exists('array_is_list')) {
            // PHP 8.1+ 使用原生函数
            return array_is_list($array);
        }

        // PHP 8.0 及以下的实现
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i) {
                return false;
            }
            $i++;
        }
        return true;
    }

    /**
     * This private function help us filter value like `-13` `13` will pass the filter and return it's int value
     * Other value like ``,`-0`, `013`, `-013`, `2.127`, `six` will throw A \RuntimeException
     * @param string $value
     * @return int
     * @throws RuntimeException
     */
    private static function checkInteger(string $value): int
    {
        $int = (int)$value;
        if ((string)$int !== $value) {
            throw new \RuntimeException('Invalid integer format or integer overflow: ' . $value);
        }
        return $int;
    }

    /**
     * 计算种子文件的特征码
     * - 核心算法，不可更改
     * @param int $torrent_id 种子id
     * @return array|null $torrentInfo
     * @throws Exception
     */
    final public static function reseed(int $torrent_id): ?array
    {
        $torrentInfo = array();     // 返回值
        try {
            // 从数据库中查找指定 ID 的 torrent 记录
            $torrent = NexusDB::table('torrents')
                ->where('id', $torrent_id)
                ->select('id', 'small_descr', 'name')
                ->first();

            // 如果找不到指定的 torrent 记录，则抛出异常
            if (!$torrent) {
                throw new Exception('找不到种子。');
            }
            // 构建种子文件路径
            $torrentDir = sprintf("%s/%s/",rtrim(ROOT_PATH, '/'),rtrim(get_setting("main.torrent_dir"), '/'));
            $torrentFilePath = $torrentDir . $torrent->id . ".torrent";

            // 检查种子文件是否存在且可读
            if (!file_exists($torrentFilePath) || !is_readable($torrentFilePath)) {
                do_log("种子id: $torrent_id 不存在或不可访问。",'error');
                throw new Exception("种子id: $torrent_id 不存在或不可访问。");
            }

            // 读取种子文件内容
            $torrentData = file_get_contents($torrentFilePath);
            $torrentArray = self::decode($torrentData);
            $info_hash = sha1(self::encode($torrentArray['info']));
            $torrentInfo['info_hash'] = $info_hash;
            return $torrentInfo;
        } catch (Exception $e) {
            throw new Exception('[Bencode::decode Error] ' . $e->getMessage() . PHP_EOL);
        }
    }
}
