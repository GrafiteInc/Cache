<?php

namespace Grafite\Cache;

use Exception;
use Grafite\Cache\Stores\SqliteStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Grafite\Cache\Commands\CreateCacheDatabase;

class CacheProvider extends ServiceProvider
{
    /**
     * Boot method.
     *
     * @return void
     */
    public function boot()
    {
        Cache::extend('sqlite', function ($app) {
            return Cache::repository(new SqliteStore);
        });

        Config::set('database.connections.sqlite_cache', [
            'driver' => 'sqlite',
            'database' => config('cache.stores.sqlite.database'),
            'prefix' => config('cache.stores.sqlite.prefix'),
        ]);

        Cache::macro('forgetLike', function ($query) {
            app('db')->connection('sqlite_cache')->table('cache')->where('key', 'like', "%{$query}%")->delete();

            return true;
        });

        Cache::macro('forgetTag', function ($queryTag) {
            $items = app('db')->connection('sqlite_cache')->table('cache')->get();

            $keys = $items->filter(function ($item) use ($queryTag) {
                [$key, $tags] = explode(':', $item->key);

                foreach (explode('|', $tags) as $tag) {
                    if ($queryTag === $tag) {
                        return $item;
                    }
                }
            })->pluck('key');

            app('db')->connection('sqlite_cache')->table('cache')->whereIn('key', $keys)->delete();

            return true;
        });

        Cache::macro('forgetTags', function ($queryTag) {
            $items = app('db')->connection('sqlite_cache')->table('cache')->get();

            $keys = $items->filter(function ($item) use ($queryTag) {
                [$key, $tags] = explode(':', $item->key);
                $tags = explode('|', $tags);

                if (count(array_intersect($queryTag, $tags)) == count($queryTag)){
                    return $item;
                }
            })->pluck('key');

            app('db')->connection('sqlite_cache')->table('cache')->whereIn('key', $keys)->delete();

            return true;
        });

        Cache::macro('getTag', function ($queryTag) {
            $items = app('db')->connection('sqlite_cache')->table('cache')->get();

            return $items->filter(function ($item) use ($queryTag) {
                [$key, $tags] = explode(':', $item->key);

                foreach (explode('|', $tags) as $tag) {
                    if ($queryTag === $tag) {
                        return $item;
                    }
                }
            })->map(function ($item) {
                return unserialize(decrypt($item->value));
            });
        });

        Cache::macro('key', function ($key, $tags = []) {
            if (str_contains($key, ':')) {
                throw new Exception("Cache Key cannot contain ':'", 1);
            }

            if (empty($tags)) {
                return $key;
            }

            $compiledKey = $key . ':' . collect($tags)->implode('|');

            if (strlen($compiledKey) > 255) {
                throw new Exception("Cache Key is too long.", 1);
            }

            return $compiledKey;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            CreateCacheDatabase::class,
        ]);
    }
}
