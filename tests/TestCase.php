<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected $app;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.stores.sqlite', [
            'driver' => 'sqlite',
            'table' => 'cache',
            'database' => database_path('cache.sqlite'),
            'prefix' => '',
            'encrypted' => true,
        ]);

        $app->make('Illuminate\Contracts\Http\Kernel');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Grafite\Cache\CacheProvider::class,
        ];
    }

    /**
     * Remove the cache database (and its WAL side files) and recreate it.
     */
    protected function freshDatabase(bool $withLocks = false): void
    {
        foreach (['', '-wal', '-shm'] as $suffix) {
            if (file_exists(database_path('cache.sqlite'.$suffix))) {
                unlink(database_path('cache.sqlite'.$suffix));
            }
        }

        Artisan::call('sql-cache:make:database');

        if ($withLocks) {
            Artisan::call('sql-cache:make:locks-table');
        }
    }

    public function setUp(): void
    {
        parent::setUp();

        app('config')->set('cache.default', 'sqlite');

        $this->withoutMiddleware();
    }
}
