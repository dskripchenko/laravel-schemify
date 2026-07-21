<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordLayerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public static ?string $seenLayer = null;

    public function handle(): void
    {
        self::$seenLayer = app('schemify')->current();
    }
}
