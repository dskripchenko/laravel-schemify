<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Queue\LayerPropagator;
use Dskripchenko\Schemify\Tests\Support\RecordLayerJob;

class QueuePropagationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
        LayerPropagator::register();
        RecordLayerJob::$seenLayer = null;
    }

    public function test_job_runs_in_the_dispatchers_layer_and_context_is_restored(): void
    {
        Schemify::provision('q_layer');
        Schemify::switchTo('q_layer');

        RecordLayerJob::dispatch();

        $this->assertSame('q_layer', RecordLayerJob::$seenLayer);
        // The sync driver: the dispatcher's context is restored after the inline run.
        $this->assertSame('q_layer', Schemify::current());
    }

    public function test_job_dispatched_without_layer_runs_without_layer(): void
    {
        Schemify::provision('q_none');
        Schemify::forget();

        RecordLayerJob::dispatch();

        $this->assertNull(RecordLayerJob::$seenLayer);
        $this->assertNull(Schemify::current());
    }
}
