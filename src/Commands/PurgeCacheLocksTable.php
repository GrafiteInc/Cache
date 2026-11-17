<?php

namespace Grafite\Cache\Commands;

use Illuminate\Console\Command;
use Grafite\Cache\CacheConnection;

class PurgeCacheLocksTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sql-cache:purge-locks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the cache_locks table.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        CacheConnection::register();

        if (file_exists(config('cache.stores.sqlite.database'))) {
            app('db')->connection(CacheConnection::NAME)->statement('delete from cache_locks;');
        }

        $this->info('Purged cache_locks table...');

        return 0;
    }
}
