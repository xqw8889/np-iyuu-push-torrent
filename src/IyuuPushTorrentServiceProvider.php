<?php

namespace NexusPlugin\IyuuPushTorrent;

use Filament\Panel;
use NexusPlugin\IyuuPushTorrent\Commands\UpdateInfoHash;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IyuuPushTorrentServiceProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package->name('IyuuPushTorren')
            ->hasTranslations()
        ;
    }

    public function packageRegistered()
    {
        Panel::configureUsing(function (Panel $panel) {
            $panel->plugin(IyuuPushTorrent::make());
        });

    }

    public function packageBooted(): void
    {
        $rep = new IyuuPushTorrentRepository();
        if ($rep->getIsEnabled()) {
            $this->loadRoutesFrom(dirname(__DIR__) . '/routes/web.php');
        }
        if ($this->app->runningInConsole()) {
            // Register the command if we are using the application via the CLI
            $this->commands([
                UpdateInfoHash::class
            ]);

        }
    }
}
