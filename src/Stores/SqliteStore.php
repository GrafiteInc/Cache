<?php

namespace Grafite\Cache\Stores;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Cache\TagSet;
use Illuminate\Cache\TaggedCache;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;

/**
 * SqliteStore delegates to DatabaseStore but with an sqlite connection instead
 */
class SqliteStore extends DatabaseStore
{
    public function __construct()
    {
        $connection = app('db')->connection('sqlite_cache');

        parent::__construct($connection, config('cache.stores.sqlite.table', 'cache'), config('cache.stores.sqlite.prefix', ''));
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\TaggedCache
     */
    public function tags($names)
    {
        return new TaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
    }

    public function putMany(array $values, $seconds)
    {
        $serializedValues = [];

        $expiration = $this->getTime() + $seconds;

        foreach ($values as $key => $value) {
            $value = $this->serialize($value);

            $serializedValues[] = [
                'key' => $this->prefix.$key,
                'value' => $value,
                'expiration' => $expiration,
            ];
        }

        return $this->table()->upsert($serializedValues, 'key') > 0;
    }

    public function many(array $keys)
    {
        if (count($keys) === 0) {
            return [];
        }

        $results = array_fill_keys($keys, null);

        // First we will retrieve all of the items from the cache using their keys and
        // the prefix value. Then we will need to iterate through each of the items
        // and convert them to an object when they are currently in array format.
        $values = $this->table()
            ->whereIn('key', array_map(function ($key) {
                return $this->prefix.$key;
            }, $keys))
            ->get()
            ->map(function ($value) {
                return is_array($value) ? (object) $value : $value;
            });

        $currentTime = $this->currentTime();

        // If this cache expiration date is past the current time, we will remove this
        // item from the cache. Then we will return a null value since the cache is
        // expired. We will use "Carbon" to make this comparison with the column.
        [$values, $expired] = $values->partition(function ($cache) use ($currentTime) {
            return $cache->expiration > $currentTime;
        });

        if ($expired->isNotEmpty()) {
            $this->forgetManyIfExpired($expired->pluck('key')->all(), prefixed: true);
        }

        return Arr::map($results, function ($value, $key) use ($values) {
            if ($cache = $values->firstWhere('key', $this->prefix.$key)) {
                return $this->unserialize($cache->value);
            }

            return $value;
        });
    }

    public function add($key, $value, $seconds)
    {
        if (! is_null($this->get($key))) {
            return false;
        }

        $key = $this->prefix.$key;

        $value = $this->serialize($value);

        $expiration = $this->getTime() + $seconds;

        if (! $this->getConnection() instanceof SqlServerConnection) {
            return $this->table()->insertOrIgnore(compact('key', 'value', 'expiration')) > 0;
        }

        try {
            return $this->table()->insert(compact('key', 'value', 'expiration'));
        } catch (QueryException) {
            // ...
        }

        return false;
    }

    protected function serialize($value)
    {
        $result = serialize($value);

        if (($this->connection instanceof PostgresConnection ||
             $this->connection instanceof SQLiteConnection) &&
            str_contains($result, "\0")
        ) {
            $result = base64_encode($result);
        }

        if (config('cache.stores.sqlite.encrypted', false)) {
            $result = encrypt($result);
        }

        return $result;
    }

    protected function unserialize($value)
    {
        if (config('cache.stores.sqlite.encrypted', false)) {
            $value = decrypt($value);
        }

        if (($this->connection instanceof PostgresConnection ||
             $this->connection instanceof SQLiteConnection) &&
            ! Str::contains($value, [':', ';'])
        ) {
            $value = base64_decode($value);
        }

        return unserialize($value);
    }
}