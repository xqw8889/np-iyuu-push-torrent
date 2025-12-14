<?php

namespace NexusPlugin\IyuuPushTorrent\Commands;

use App\Http\Middleware\Locale;
use App\Models\User;
use App\Models\Torrent;
use NexusPlugin\IyuuPushTorrent\Repositories\SearchhashRespones;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class UpdateInfoHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iyuu:update {--id=} {--all=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '首次主动汇报种子特征 --id --all';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $rep = new SearchhashRespones();
        $all= $this->option('all');
        $id= $this->option('id');
        if ($all && $id) {
            $this->error('不能同时使用 --all 和 --id 参数。');
            return Command::FAILURE;
        }
        if ($id){
            try {
                $this->info("正在处理种子ID：{$id}");
                $rep->CreateTorrent($id);
                $this->info("处理种子ID：{$id} 成功！");
            } catch (\Exception $e) {
                $this->warn("处理种子 ID：{$id} 时出现异常：" . $e->getMessage());
            }
        }elseif ($all) {
            $this->info('开始处理所有种子...');
            $totalTorrents = Torrent::count();
            $perPage = 100;
            $pages = ceil($totalTorrents / $perPage);

            // 创建总的进度条
            $totalBar = $this->output->createProgressBar($totalTorrents);

            for ($page = 1; $page <= $pages; $page++) {
                $torrents = Torrent::skip(($page - 1) * $perPage)->take($perPage)->pluck('id');
                $startId = ($page - 1) * $perPage + 1;
                $endId = min($page * $perPage, $totalTorrents);
                $this->info("处理种子ID范围：{$startId} - {$endId}");

                foreach ($torrents as $torrentId) {
                    if (Redis::get("torrent:processed:$torrentId")) {
                        $this->info("跳过处理种子 ID：{$torrentId}");
                        continue; // 如果已处理，则跳过处理
                    }
                    try {
                        // 显示当前种子的处理信息和进度条
                        $this->output->write("正在处理种子ID：{$torrentId}");
                        $rep->CreateTorrent($torrentId);
                        Redis::setex("torrent:processed:$torrentId", 86400, true);
                        $this->output->write(" - <info>成功！</info>\n");
                    } catch (\Exception $e) {
                        // 如果出现异常，显示警告信息
                        $this->warn("处理种子 ID：{$torrentId} 时出现异常：" . $e->getMessage());
                    } finally {
                        // 更新总的进度条
                        $totalBar->advance();
                    }
                }
            }

            // 完成总的进度条
            $totalBar->finish();

            $this->info('所有种子处理完毕！');
            return Command::SUCCESS;
        }else {
            $this->error('请提供 --id 或 --all 参数。');
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}