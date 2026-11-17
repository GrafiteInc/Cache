<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    public function testCacheProvider()
    {
        $this->freshDatabase();

        // Put a value
        Cache::put('foo', 'bar', 60);

        $value = Cache::get('foo');

        $this->assertEquals('bar', $value);
    }

    public function testCacheKeyMaker()
    {
        $this->freshDatabase();

        // Put a value
        $key = cache()->key('foo', ['bar']);

        cache()->put($key, 'bar', 60);

        $value = Cache::get($key);

        $this->assertEquals('bar', $value);
    }

    public function testCacheLocksTable()
    {
        $this->freshDatabase(withLocks: true);

        $lock = cache()->lock('foo-bar', 10);
        $value = 'bar';

        if ($lock->get()) {
            // Lock acquired for 10 seconds...
            $this->assertEquals('bar', $value);

            $lock->release();
        }
    }
}
