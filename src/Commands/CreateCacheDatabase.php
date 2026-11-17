<?php

namespace Grafite\Cache\Commands;

use Illuminate\Console\Command;
use Grafite\Cache\CacheConnection;

class CreateCacheDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sql-cache:make:database';

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
        $this->info('Creating cache database...');

        if (! file_exists(config('cache.stores.sqlite.database'))) {
            touch(config('cache.stores.sqlite.database'));
        }

        CacheConnection::register();

        // Set the table
        app('db')->connection(CacheConnection::NAME)->statement('CREATE TABLE cache (key STRING PRIMARY KEY, value LONGTEXT, expiration INTEGER)');

        return 0;
    }
}
