<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\LaravelApi\Console\Commands\BaseCommand as BaseApiCommand;

/**
 * Thin base for Schemify's own console commands. Kept as an extension point
 * (inherits laravel-api's askValid() and friends); the legacy per-layer
 * migration-copy helpers were removed with the single-tenant-set model.
 */
class BaseCommand extends BaseApiCommand {}
