<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    public function testCacheProvider()
    {
        // Create the database
        Artisan::call('make:cache:database');

        // Put a value
        Cache::put('foo', 'bar', 60);

        $value = Cache::get('foo');

        $this->assertEquals('bar', $value);
    }

        public function testCacheKeyMaker()
    {
        // Create the database
        Artisan::call('make:cache:database');

        // Put a value
        $key = cache()->key('foo', ['bar']);

        cache()->put($key, 'bar', 60);

        $value = Cache::get($key);

        $this->assertEquals('bar', $value);
    }
}
