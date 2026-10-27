<?php

namespace Dskripchenko\Schemify\Queue;

use Dskripchenko\Schemify\Support\TransactionScope;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * Propagates the active layer into queued jobs.
 *
 * Enabled via config `schemify.queue.propagate`. When on:
 *  - every payload created while a layer is active carries `schemify_layer`;
 *  - a worker switches to that layer before running the job (and drops any
 *    stale layer when the payload has none);
 *  - after the job the previously active layer of the processing context is
 *    restored — which also makes the `sync` driver safe (the dispatcher's
 *    own layer survives inline execution).
 *
 * A job whose layer no longer exists fails loudly (switchTo throws) — that is
 * intentional: silently running it on the wrong connection would be worse.
 *
 * With the transaction-scoped search_path a job of a layer runs inside one
 * transaction on the layer connection: committed when the job is processed,
 * rolled back when it throws.
 */
final class LayerPropagator
{
    public const PAYLOAD_KEY = 'schemify_layer';

    /** @var list<string|null> layers active in the worker before each nested job */
    private static array $restoreStack = [];

    /** @var list<bool> whether each nested job opened a transaction */
    private static array $transactionStack = [];

    public static function register(): void
    {
        // Idempotent within the current app instance.
        if (app()->bound('schemify.queue.propagating')) {
            return;
        }
        app()->instance('schemify.queue.propagating', true);

        Queue::createPayloadUsing(static function (): array {
            $current = app('schemify')->current();

            return $current === null ? [] : [self::PAYLOAD_KEY => $current];
        });

        Event::listen(JobProcessing::class, static function (JobProcessing $event): void {
            $manager = app('schemify');
            self::$restoreStack[] = $manager->current();

            $layer = $event->job->payload()[self::PAYLOAD_KEY] ?? null;

            $began = false;

            if (is_string($layer) && $layer !== '') {
                $manager->switchTo($layer);

                if (TransactionScope::enabled()) {
                    DB::connection($manager->connectionName())->beginTransaction();
                    $began = true;
                }
            } elseif ($manager->current() !== null) {
                $manager->forget();
            }

            self::$transactionStack[] = $began;
        });

        Event::listen(JobProcessed::class, static function (): void {
            self::finishTransaction(commit: true);
        });

        Event::listen(JobExceptionOccurred::class, static function (): void {
            self::finishTransaction(commit: false);
        });

        // Registered after the transaction listeners, so the transaction ends
        // before the layer is restored.
        // JobExceptionOccurred fires on every failed attempt (before JobFailed),
        // so together with JobProcessed each push gets exactly one pop.
        Event::listen([JobProcessed::class, JobExceptionOccurred::class], static function (): void {
            if (self::$restoreStack === []) {
                return;
            }

            $previous = array_pop(self::$restoreStack);
            $manager = app('schemify');

            if ($previous !== null) {
                $manager->switchTo($previous);
            } elseif ($manager->current() !== null) {
                $manager->forget();
            }
        });
    }

    private static function finishTransaction(bool $commit): void
    {
        if (self::$transactionStack === [] || ! array_pop(self::$transactionStack)) {
            return;
        }

        $connection = DB::connection(app('schemify')->connectionName());

        if ($connection->transactionLevel() === 0) {
            return;
        }

        $commit ? $connection->commit() : $connection->rollBack();
    }
}
