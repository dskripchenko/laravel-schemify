<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests\Integration;

use Dskripchenko\Schemify\Facades\Schemify;
use Dskripchenko\Schemify\Queue\LayerPropagator;
use Dskripchenko\Schemify\Services\ConnectionHelper;
use Dskripchenko\Schemify\Tests\Support\WriteProbeJob;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Under the transaction-scoped search_path a job of a layer runs inside one
 * transaction: its writes land in the layer and are committed, or rolled back
 * when it throws.
 */
class QueueTransactionScopeTest extends IntegrationTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('schemify.search_path.scope', 'transaction');
        $app['config']->set('queue.default', 'sync');
    }

    protected function setUp(): void
    {
        parent::setUp();

        LayerPropagator::register();
        // The base class drops q_* schemas between tests; the process-wide
        // "schema exists" cache must not outlive them.
        ConnectionHelper::forgetEnsuredSchemas();
        Schemify::provision('q_tx');
        Schemify::transaction('q_tx', fn () => DB::connection((string) config('database.layer'))->statement('CREATE TABLE probe (marker text)'));
    }

    private function markers(): array
    {
        return Schemify::transaction('q_tx', fn () => DB::connection((string) config('database.layer'))->table('probe')->pluck('marker')->all());
    }

    public function test_a_job_writes_into_its_layer_inside_a_transaction(): void
    {
        Schemify::switchTo('q_tx');
        WriteProbeJob::dispatch('done');
        Schemify::forget();

        $this->assertSame(['done'], $this->markers());
    }

    public function test_a_failing_job_is_rolled_back(): void
    {
        Schemify::switchTo('q_tx');

        try {
            WriteProbeJob::dispatch('lost', true);
            $this->fail('the job should have thrown');
        } catch (RuntimeException) {
        }

        Schemify::forget();

        $this->assertSame([], $this->markers());
        $this->assertSame(0, DB::connection((string) config('database.layer'))->transactionLevel());
    }
}
