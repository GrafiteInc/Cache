<?php

namespace Grafite\Cache;

use InvalidArgumentException;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\SQLiteConnection;

/**
 * Single source of truth for the `sqlite_cache` database connection.
 */
class CacheConnection
{
    /**
     * The database connection name used by the cache.
     */
    public const NAME = 'sqlite_cache';

    /**
     * Concurrency-friendly defaults, suited to multiple workers sharing one cache file.
     * Any of these can be overridden in `cache.stores.sqlite`, or set to null to disable.
     */
    public const DEFAULTS = [
        'busy_timeout' => 10000,
        'journal_mode' => 'wal',
        'synchronous' => 'normal',
        'transaction_mode' => 'IMMEDIATE',
    ];

    /**
     * Valid SQLite transaction modes.
     */
    public const TRANSACTION_MODES = ['DEFERRED', 'IMMEDIATE', 'EXCLUSIVE'];

    /**
     * Build the connection config from the `cache.stores.sqlite` store config.
     */
    public static function config(): array
    {
        $store = (array) config('cache.stores.sqlite', []);

        $config = [
            'driver' => 'sqlite',
            'database' => $store['database'] ?? null,
            'prefix' => $store['prefix'] ?? '',
        ];

        foreach (self::DEFAULTS as $key => $default) {
            $value = array_key_exists($key, $store) ? $store[$key] : $default;

            if (! is_null($value)) {
                $config[$key] = $value;
            }
        }

        if (isset($config['transaction_mode'])) {
            $config['transaction_mode'] = self::normalizeTransactionMode($config['transaction_mode']);
        }

        return $config;
    }

    /**
     * Register (or refresh) the connection config and drop any connection
     * resolved with stale settings.
     */
    public static function register(): void
    {
        Config::set('database.connections.'.self::NAME, self::config());

        if (app()->bound('db')) {
            app('db')->purge(self::NAME);
        }
    }

    /**
     * Whether the framework applies `transaction_mode` itself
     * (Laravel 12+ running on PHP 8.4+).
     */
    public static function transactionModeHandledNatively(): bool
    {
        return PHP_VERSION_ID >= 80400
            && method_exists(SQLiteConnection::class, 'executeBeginTransactionStatement');
    }

    /**
     * Validate the transaction mode, since it is interpolated into SQL.
     */
    protected static function normalizeTransactionMode($mode): string
    {
        $normalized = strtoupper(trim((string) $mode));

        if (! in_array($normalized, self::TRANSACTION_MODES, true)) {
            throw new InvalidArgumentException(
                "Invalid cache transaction_mode [{$mode}]. Expected one of: ".implode(', ', self::TRANSACTION_MODES).'.'
            );
        }

        return $normalized;
    }
}
