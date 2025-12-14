<?php

namespace NexusPlugin\IyuuPushTorrent;

use Filament\PluginServiceProvider;
use NexusPlugin\IyuuPushTorrent\Commands\UpdateInfoHash;
use Spatie\LaravelPackageTools\Package;

class IyuuPushTorrentServiceProvider extends PluginServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package->name('IyuuPushTorren')
            ->hasTranslations()
            ->hasViews()
        ;
    }

    protected function registerMacros(): void
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
