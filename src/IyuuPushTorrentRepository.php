<?php

namespace NexusPlugin\IyuuPushTorrent;

use App\Filament\OptionsTrait;
use App\Models\Setting;
use App\Models\User;
use App\Http\Middleware\Locale;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Nexus\Nexus;
use Nexus\Plugin\BasePlugin;
use Filament\Forms;
use Nexus\Database\NexusDB;
use NexusPlugin\IyuuPushTorrent\Http\Controllers\IyuuPushTorrentController;
use NexusPlugin\IyuuPushTorrent\Repositories\SearchhashRespones;


class IyuuPushTorrentRepository extends BasePlugin
{
    use OptionsTrait;

    const ID = "IyuuPushTorren";
    const COMPATIBLE_NP_VERSION = '1.9.0';

    const CACHE_KEY = 'IyuuPushTorren';
    const VERSION = '2.0.0';

    public function install()
    {
        $this->runMigrations($this->getMigrationFilePath());

    }

    public function uninstall()
    {
        $this->runMigrations($this->getMigrationFilePath(), true);
    }

    private function getMigrationFilePath(): string
    {
        return dirname(__DIR__) . '/database/migrations';
    }

    public function boot()
    {
        $self = new self;
        $basePath = dirname(__DIR__);
        Nexus::addTranslationNamespace($basePath . '/resources/lang', 'IyuuPushTorren');
        add_filter('nexus_setting_tabs', [$self, 'filterAddSettingTab'], 10, 1);
        add_action('IyuuPushTorren_torrent', [$self, 'actionRenderOnDetailPage'], 10, 1);
        add_action('IyuuPushTorren_addhash', [$self, 'addhash'], 10, 2);
        add_action('IyuuPushTorren_Deletehhash', [$self, 'deletehash'], 10, 1);
//        add_filter('sticky_icon', [$self, 'handleStickyIcon'], 10, 2);


    }

    public function getIsEnabled(): bool
    {
        return Setting::get('IyuuPushTorren.enabled') == 'yes';
    }

    public function filterAddSettingTab(array $tabs): array
    {
        if (get_user_class() >= User::CLASS_SYSOP) {
            $tabs[] = Forms\Components\Tabs\Tab::make(__('IyuuPushTorren::IyuuPushTorren.text_preservation'))
                ->id('IyuuPushTorren')
                ->schema([
                    Forms\Components\Radio::make('IyuuPushTorren.enabled')->options(self::$yesOrNo)->required()->inline(true)->label(__('label.enabled')),
//                    Forms\Components\Radio::make('IyuuPushTorren.torrent_Deletehhash')->required()->options(self::$yesOrNo)->reactive()->inline(true)->label(__('IyuuPushTorren::IyuuPushTorren.torrent_Deletehhash')),
//                    Forms\Components\Radio::make('IyuuPushTorren.addhash')->required()->options(self::$yesOrNo)->reactive()->inline(true)->label(__('IyuuPushTorren::IyuuPushTorren.addhash')),
//                    Forms\Components\Radio::make('IyuuPushTorren.tagid_enabled')->required()->hidden(fn($get) => $get('IyuuPushTorren.addhash') != 'yes')->options(self::$yesOrNo)->reactive()->inline(true)->label(__('IyuuPushTorren::IyuuPushTorren.tagid_enabled')),
//                    Forms\Components\TextInput::make('IyuuPushTorren.tagid')->required()->hidden(fn($get) => $get('IyuuPushTorren.tagid_enabled') != 'yes')->required()->label(__('IyuuPushTorren::IyuuPushTorren.tagid'))->helperText(__('IyuuPushTorren::IyuuPushTorren.setting.tagid_help')),
                    Forms\Components\TextInput::make('IyuuPushTorren.sign_key')->required()->label(__('IyuuPushTorren::IyuuPushTorren.sign_key')),
                    Forms\Components\TextInput::make('IyuuPushTorren.site_key')->required()->label(__('IyuuPushTorren::IyuuPushTorren.site_name')),
                ])->columns(2);
        }
        return $tabs;
    }

    public function actionRenderOnDetailPage($id)
    {
        if ($this->getIsEnabled()) {
            $input = <<<HTML
            <tr>
                <td class="rowhead nowrap" valign="top" align="right">其他站点</td>
                <td id="ajaxResult" class="rowfollow" valign="top" align="left"></td>
              </tr>
              <script>
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '/plugin/IyuuPushTorrent?torrent_id=$id', true);
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        var response = JSON.parse(xhr.responseText);
                        document.getElementById('ajaxResult').innerHTML = response.data;
                    } else {
                        console.error('请求失败: ' + xhr.statusText);
                    }
                };
                xhr.onerror = function() {
                  console.error('Request error');
                };
                xhr.send();
            </script>
HTML;
            echo $input;
        }
    }

    public function deletehash($id)
    {
        if ($this->getIsEnabled() && Setting::get('IyuuPushTorren.torrent_Deletehhash') == 'yes') {
            $rep = new SearchhashRespones();
            if (is_array($id)) {
                foreach ($id as $tid) {
                    $rep->deletehash((int)$tid);
                }
            } else {
                $rep->deletehash((int)$id);
            }
            return true;
        }
        return null;
    }

    public  function addhash($tagIdArr,$id)
    {
        if ($this->getIsEnabled() && Setting::get('IyuuPushTorren.addhash') == 'yes'){
            $rep = new SearchhashRespones();
            if (Setting::get('IyuuPushTorren.tagid_enabled') == 'yes'){
                do_log('开启排除tag上报种子:'.$id,'error');
                $tagid = Setting::get('IyuuPushTorren.tagid');
                $tagidArr = explode(' ', $tagid);
                $allTagsMatch = false;
                foreach ($tagidArr as $tag) {
                    if (!in_array($tag, $tagIdArr)) {
                        $allTagsMatch = true;
                        break;
                    }
                }
                if ($allTagsMatch) {
                    do_log('符合tag条件上报种子:'.$id,'error');
                    return $rep->CreateTorrent($id);
                }
                do_log('不符合tag条件种子:'.$id,'error');
            } else {
                do_log('未开启排除tag上报种子:'.$id,'error');
                return $rep->CreateTorrent($id);
            }
        }
        return null;
    }

    private function hasPromotion(array $torrent)
    {
        if (!$this->getIsEnabled()) {
            return false;
        }
        if (!isset($torrent['__sticky_promotion'])) {
            return false;
        }
        return true;
    }
}