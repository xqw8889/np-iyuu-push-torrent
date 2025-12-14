<?php

namespace NexusPlugin\IyuuPushTorrent;

use App\Support\StaticMake;
use Filament\Contracts\Plugin;
use Filament\Panel;
class IyuuPushTorrent implements Plugin
{
    use StaticMake;

    const ID = "IyuuPushTorren";

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {

    }

    public function boot(Panel $panel): void
    {

    }
    public static function make(): static
    {
        return app(static::class);
    }
}
