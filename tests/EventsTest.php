<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Events\LayerForgotten;
use Illuminate\Support\Facades\Event;

class EventsTest extends TestCase
{
    public function test_forget_dispatches_layer_forgotten(): void
    {
        Event::fake([LayerForgotten::class]);

        $this->app->make('schemify')->forget();

        Event::assertDispatched(LayerForgotten::class, fn (LayerForgotten $e) => $e->previous === null);
    }
}
