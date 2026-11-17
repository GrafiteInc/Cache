<?php

namespace Grafite\Cache\Commands;

use Illuminate\Console\Command;
use Grafite\Cache\CacheConnection;

class CreateCacheLocksTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sql-cache:make:locks-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Adding cache_locks table...');

        CacheConnection::register();

        if (file_exists(config('cache.stores.sqlite.database'))) {
            app('db')->connection(CacheConnection::NAME)->statement('CREATE TABLE cache_locks (key STRING PRIMARY KEY, owner STRING, expiration INTEGER)');
        }

        return 0;
    }
}
