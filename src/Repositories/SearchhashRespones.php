<?php

namespace NexusPlugin\IyuuPushTorrent\Repositories;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use Nexus\Nexus;
use Nexus\Plugin\BasePlugin;
use Filament\Forms;
use Nexus\Database\NexusDB;

class SearchhashRespones
{
    protected array $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    ];
    protected string $signConfigKey;
    protected string $siteConfigKey;
    protected string $signConfigUrl;

    public function __construct()
    {
        $this->signConfigKey = Setting::get('IyuuPushTorren.sign_key');
        $this->siteConfigKey = Setting::get('IyuuPushTorren.site_key');
        $this->signConfigUrl = 'https://find.iyuu.cn/api/';
    }
    public function searchhash($hash): string
    {
        if (Cache::get("iyuu_hahs:$hash")){
            return Cache::get("iyuu_hahs:$hash");
        }
        $data = [
            'hash' => json_encode([$hash]),
            'sha1' => sha1(json_encode([$hash])),
            'sign' => sha1(time().$this->signConfigKey),
            'timestamp' => time(),
            'site' => $this->siteConfigKey,
        ];
        $text = '';
        try {
            $response = Http::withHeaders($this->headers)
                ->post($this->signConfigUrl . 'searchhash', $data);
            $statusCode = $response->status();
            $responseData = $response->json();
        } catch (\Exception $e) {
            do_log('请求异常：' . $e->getMessage(),'error');
            return '请求异常';
        }
        if ($responseData['msg'] != 200) {
            return $responseData['msg'] ?? '该种子暂无IYUU数据';
        }else{
            $data = $responseData['data'] ?? [];
            foreach ($data as $item) {
                // dd($item);
                $nickname = $item['nickname'];
                $torrent_id = $item['torrent_id'];
                $details_url = $item['details_url'];
                $tips = $item['tips'];
                $base_url = $item['base_url'];
                $timestamp = $item['timestamp'];
                if ($details_url !== null) {
                    $link = '<a href="http://' . $base_url . '/' . $details_url . '" title="' . $tips . '" target="_blank">' . $nickname . '</a>';
                } else {
                    $link = '<a href="javascript:;" title="' . $tips . '">' . $nickname . '</a>';
                }
                $icon = '<img src="http://' . $base_url . '/favicon.ico" onerror="this.onerror=null; this.src=\'http://' . $base_url . '/favicon.ico\'" class="round_icon" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;">';
                $content = $icon . $link;
                $text .= $content . ' | ';
            }
            $text = rtrim($text, ' | ');
            $text.= " || 数据更新于(" . $this->gettime($timestamp).")";
            Cache::put("iyuu_hahs:$hash", $text, 1800);
            return $text;
        }
    }
    /**
     * 删除辅种
     */
    public function deletehash(int $torrent_id):bool
    {
        $info_hash = new Bencode();
        try {
            $info_hash = $info_hash->reseed($torrent_id)['info_hash'];
        } catch (\Exception $e) {
            throw new \Exception('种子元数据解码错误');
        }
        $hashArray = [
            'hash' => json_encode([$info_hash]),
            'torrent_id' => $torrent_id,
            'sign' => sha1(time().$this->signConfigKey),
            'timestamp' => time(),
            'site' => $this->siteConfigKey,
        ];
        $http = new Client();
        $response = $http->post($this->signConfigUrl.'deletehhash', [
            'headers' => $this->headers,
            'form_params' => $hashArray,
        ]);
        $responseData = json_decode((string)$response->getBody(), true);
        if ($response->getStatusCode() != 200) {
            \App\Models\StaffMessage::query()->insert([
                'sender' => 0,
                'subject' => 'IYUU_deletehash_error',
                'msg' => $responseData['msg'].$responseData['data'] ?? '未知错误',
                'added' => now(),
                'permission' => '',
            ]);
        }
        do_log("种子id：$torrent_id del成功",'error');
        return true;
        // }
    }
    /**
     * 上报辅种特征
     */
    public function CreateTorrent(int $torrent_id): ?bool
    {
        try{
            $info_hash = new Bencode();
            $data = $info_hash->reseed($torrent_id);
            $hashArray = [
                'data' => json_encode($data),
                'sign' => sha1(time().$this->signConfigKey),
                'timestamp' => time(),
                'site' => $this->siteConfigKey,
            ];
            $http = new Client();
            $response = $http->post($this->signConfigUrl.'createTorrent', [
                'headers' => $this->headers,
                'form_params' => $hashArray,
            ]);
            $responseData = json_decode((string)$response->getBody(), true);
            if ($response->getStatusCode() != 200){
                \App\Models\StaffMessage::query()->insert([
                    'sender' => 0,
                    'subject' => 'IYUU_CreateTorrent_error',
                    'msg' => "种子：$torrent_id 上报错误 $responseData[msg] $responseData[data] 可选手动执行上报" ?? '上报错误种子：'.$torrent_id.'未知错误',
                    'added' => now(),
                    'permission' => '',
                ]);
                throw new \Exception($responseData['msg']);
            }
            do_log("种子id：$torrent_id 上报成功",'error');
            return true;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function gettime($timestamp, $showSeconds = true, $showMinutes = true, $showHours = true): string
    {
        $now = time();
        $givenTime = strtotime($timestamp);
        $timeDiff = $now - $givenTime;
        $secondsInMinute = 60;
        $secondsInHour = 60 * $secondsInMinute;
        $secondsInDay = 24 * $secondsInHour;
        if ($timeDiff < $secondsInMinute && $showSeconds) {
            return $timeDiff . " 秒前";
        } elseif ($timeDiff < $secondsInHour && $showMinutes) {
            $minutes = floor($timeDiff / $secondsInMinute);
            return $minutes . " 分钟前";
        } elseif ($timeDiff < $secondsInDay && $showHours) {
            $hours = floor($timeDiff / $secondsInHour);
            return $hours . " 小时前";
        } else {
            $days = floor($timeDiff / $secondsInDay);
            return $days . " 天前";
        }
    }
}