<?php

namespace Grafite\Cache\Commands;

use Illuminate\Console\Command;

class CreateCacheDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:cache-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an sqlite cache database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Creating cache database...');

        if (! file_exists(database_path('cache.sqlite'))) {
            touch(database_path('cache.sqlite'));
            // Set the table
            app('db')->connection('sqlite_cache')->statement('CREATE TABLE cache (key STRING PRIMARY KEY, value LONGTEXT, expiration INTEGER)');
        }

        return 0;
    }
}
