<?php

declare(strict_types=1);

namespace Dskripchenko\Schemify\Tests;

use Dskripchenko\Schemify\Traits\PathByLayer;

class PathByLayerTest extends TestCase
{
    /**
     * Build a throwaway object that uses the trait with a stubbed console
     * input, so we can assert path resolution without a real command.
     */
    private function resolver(?string $layer, ?string $path = null, bool $realpath = false): object
    {
        return new class($layer, $path, $realpath)
        {
            use PathByLayer;

            public object $input;

            public object $laravel;

            public function __construct(?string $layer, private ?string $path, private bool $realpath)
            {
                $this->input = new class($layer, $path)
                {
                    public function __construct(private ?string $layer, private ?string $path) {}

                    public function getOption(string $name)
                    {
                        return $name === 'path' ? $this->path : $this->layer;
                    }
                };
                $this->laravel = new class
                {
                    public function basePath(): string
                    {
                        return '/base';
                    }
                };
            }

            protected function usingRealPath(): bool
            {
                return $this->realpath;
            }

            public function resolve(): string
            {
                return $this->getMigrationPath();
            }

            /** @return array<int, string> */
            public function resolveAll(): array
            {
                return $this->getMigrationPaths();
            }
        };
    }

    public function test_central_layer_uses_central_path(): void
    {
        config()->set('schemify.central_layer', 'core');
        config()->set('schemify.migrations.central_path', '/app/database/migrations');

        $this->assertSame('/app/database/migrations', $this->resolver('core')->resolve());
    }

    public function test_tenant_layer_uses_tenant_path(): void
    {
        config()->set('schemify.central_layer', 'core');
        config()->set('schemify.migrations.path', '/app/database/migrations/tenant');

        $this->assertSame('/app/database/migrations/tenant', $this->resolver('main')->resolve());
    }

    public function test_explicit_path_option_wins(): void
    {
        $this->assertSame('/base/custom/path', $this->resolver('main', 'custom/path')->resolve());
    }

    public function test_explicit_realpath_option_is_returned_as_is(): void
    {
        $this->assertSame('/abs/custom', $this->resolver('main', '/abs/custom', true)->resolve());
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>} central, tenant
     */
    private function pathsWith(string $registered): array
    {
        config()->set('schemify.central_layer', 'core');
        config()->set('schemify.migrations.central_path', '/central');
        config()->set('schemify.migrations.path', '/tenant');
        config()->set('schemify.migrations.registered', $registered);
        app('migrator')->path('/vendor/pkg');

        return [$this->resolver('core')->resolveAll(), $this->resolver('acme')->resolveAll()];
    }

    public function test_package_migrations_stay_central_by_default(): void
    {
        [$central, $tenant] = $this->pathsWith('central');

        $this->assertSame(['/vendor/pkg', '/central'], $central);
        $this->assertSame(['/tenant'], $tenant);
    }

    public function test_package_migrations_can_go_to_tenants(): void
    {
        [$central, $tenant] = $this->pathsWith('tenant');

        $this->assertSame(['/central'], $central);
        $this->assertSame(['/vendor/pkg', '/tenant'], $tenant);
    }

    public function test_package_migrations_can_go_everywhere(): void
    {
        [$central, $tenant] = $this->pathsWith('both');

        $this->assertSame(['/vendor/pkg', '/central'], $central);
        $this->assertSame(['/vendor/pkg', '/tenant'], $tenant);
    }
}
