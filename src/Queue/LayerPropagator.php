<?php

namespace Dskripchenko\Schemify\Queue;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
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
 */
final class LayerPropagator
{
    public const PAYLOAD_KEY = 'schemify_layer';

    /** @var list<string|null> layers active in the worker before each nested job */
    private static array $restoreStack = [];

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

            if (is_string($layer) && $layer !== '') {
                $manager->switchTo($layer);
            } elseif ($manager->current() !== null) {
                $manager->forget();
            }
        });

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
}
