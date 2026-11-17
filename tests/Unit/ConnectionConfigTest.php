<?php

namespace Tests\Unit;

use Tests\TestCase;
use InvalidArgumentException;
use Grafite\Cache\CacheConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ConnectionConfigTest extends TestCase
{
    public function testDefaultConfigIncludesConcurrencySettings()
    {
        $config = config('database.connections.sqlite_cache');

        $this->assertSame('sqlite', $config['driver']);
        $this->assertSame(database_path('cache.sqlite'), $config['database']);
        $this->assertSame(10000, $config['busy_timeout']);
        $this->assertSame('wal', $config['journal_mode']);
        $this->assertSame('normal', $config['synchronous']);
        $this->assertSame('IMMEDIATE', $config['transaction_mode']);
    }

    public function testStoreConfigOverridesAndNullDisables()
    {
        config()->set('cache.stores.sqlite.busy_timeout', 5000);
        config()->set('cache.stores.sqlite.journal_mode', null);
        config()->set('cache.stores.sqlite.transaction_mode', 'exclusive');

        $config = CacheConnection::config();

        $this->assertSame(5000, $config['busy_timeout']);
        $this->assertArrayNotHasKey('journal_mode', $config);
        $this->assertSame('normal', $config['synchronous']);
        $this->assertSame('EXCLUSIVE', $config['transaction_mode']);
    }

    public function testInvalidTransactionModeThrows()
    {
        config()->set('cache.stores.sqlite.transaction_mode', 'IMMEDIATE; DROP TABLE cache');

        $this->expectException(InvalidArgumentException::class);

        CacheConnection::config();
    }

    public function testPragmasAreAppliedToLiveConnection()
    {
        $this->freshDatabase();

        $connection = DB::connection('sqlite_cache');

        $this->assertSame('wal', strtolower($connection->selectOne('PRAGMA journal_mode')->journal_mode));
        $this->assertEquals(10000, $connection->selectOne('PRAGMA busy_timeout')->timeout);
        $this->assertEquals(1, $connection->selectOne('PRAGMA synchronous')->synchronous);
    }

    public function testIncrementAndDecrement()
    {
        $this->freshDatabase();

        Cache::put('counter', 5, 60);

        $this->assertSame(6, Cache::increment('counter'));
        $this->assertSame(9, Cache::increment('counter', 3));
        $this->assertSame(7, Cache::decrement('counter', 2));
        $this->assertEquals(7, Cache::get('counter'));

        $this->assertFalse(Cache::store()->getStore()->increment('missing'));

        Cache::put('word', 'hello', 60);
        $this->assertFalse(Cache::store()->getStore()->increment('word'));
        $this->assertSame('hello', Cache::get('word'));
    }

    public function testIncrementUsesConfiguredTransactionMode()
    {
        if (CacheConnection::transactionModeHandledNatively()) {
            $this->markTestSkipped('transaction_mode is applied natively by the framework on this version.');
        }

        $this->freshDatabase();

        Cache::put('counter', 1, 60);

        $queries = [];
        DB::connection('sqlite_cache')->listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->assertSame(2, Cache::increment('counter'));

        $this->assertContains('BEGIN IMMEDIATE TRANSACTION', $queries);
        $this->assertContains('COMMIT', $queries);
        $this->assertSame(0, DB::connection('sqlite_cache')->transactionLevel());
    }

    public function testIncrementInsideExistingTransaction()
    {
        $this->freshDatabase();

        Cache::put('counter', 1, 60);

        $result = DB::connection('sqlite_cache')->transaction(function () {
            return Cache::increment('counter');
        });

        $this->assertSame(2, $result);
        $this->assertEquals(2, Cache::get('counter'));
    }
}
