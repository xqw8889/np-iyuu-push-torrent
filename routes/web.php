<?php


use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'plugin',
    'middleware' => ['auth.nexus:nexus', 'locale', 'throttle:60,0.2']
], function () {
    Route::get('IyuuPushTorrent', [\NexusPlugin\IyuuPushTorrent\Http\Controllers\IyuuPushTorrentController::class, 'index'])->middleware('web');
    Route::post('IyuuPushTorrent', [\NexusPlugin\IyuuPushTorrent\Http\Controllers\IyuuPushTorrentController::class, 'application'])->middleware('web');
    Route::get('IyuuPushTorrent/top100', [\NexusPlugin\IyuuPushTorrent\Http\Controllers\IyuuPushTorrentController::class, 'top100_data'])->middleware('web');
    Route::get('IyuuPushTorrent/new-data', [\NexusPlugin\IyuuPushTorrent\Http\Controllers\IyuuPushTorrentController::class, 'new_data'])->middleware('web');
    Route::get('IyuuPushTorrent/new-top', [\NexusPlugin\IyuuPushTorrent\Http\Controllers\IyuuPushTorrentController::class, 'new_top'])->middleware('web');
    Route::get('IyuuPushTorrent/chart-data', [\NexusPlugin\IyuuPushTorrent\Http\Controllers\IyuuPushTorrentController::class, 'chart_data'])->middleware('web');
});
