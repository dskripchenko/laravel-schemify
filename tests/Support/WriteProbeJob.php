<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WriteProbeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue;

    public function __construct(public string $marker, public bool $fail = false) {}

    public function handle(): void
    {
        DB::connection((string) config('database.layer'))->table('probe')->insert(['marker' => $this->marker]);

        if ($this->fail) {
            throw new RuntimeException('job failed after writing');
        }
    }
}
