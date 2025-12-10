<?php

namespace App\Core;

class Cache
{
    protected static $instance;
    protected $redis;
    protected $driver;
    protected $prefix;

    private function __construct()
    {
        $this->driver = config('app.cache_driver', 'file');
        $this->prefix = config('redis.options.prefix', 'affiliate:');

        if ($this->driver === 'redis') {
            $this->connectRedis();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connectRedis()
    {
        try {
            $config = config('redis.connections.cache');
            $this->redis = new \Redis();
            $this->redis->connect(
                $config['host'],
                $config['port'],
                0,
                null,
                0,
                0,
                ['auth' => $config['password']]
            );
            $this->redis->select($config['database']);
        } catch (\Exception $e) {
            // Fall back to file caching
            $this->driver = 'file';
        }
    }

    public function get($key, $default = null)
    {
        if ($this->driver === 'redis') {
            $value = $this->redis->get($this->prefix . $key);
            return $value !== false ? unserialize($value) : $default;
        }

        $path = storage_path("cache/{$key}.cache");
        if (file_exists($path)) {
            $data = unserialize(file_get_contents($path));
            if (isset($data['expires']) && $data['expires'] > time()) {
                return $data['value'];
            }
            @unlink($path);
        }

        return $default;
    }

    public function put($key, $value, $minutes = 60)
    {
        $seconds = $minutes * 60;

        if ($this->driver === 'redis') {
            $this->redis->setex(
                $this->prefix . $key,
                $seconds,
                serialize($value)
            );
            return;
        }

        $path = storage_path("cache/{$key}.cache");
        $data = [
            'value' => $value,
            'expires' => time() + $seconds,
        ];

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, serialize($data));
    }

    public function forget($key)
    {
        if ($this->driver === 'redis') {
            $this->redis->del($this->prefix . $key);
            return;
        }

        $path = storage_path("cache/{$key}.cache");
        @unlink($path);
    }

    public function flush()
    {
        if ($this->driver === 'redis') {
            $this->redis->flushDb();
            return;
        }

        $cacheDir = storage_path('cache');
        if (is_dir($cacheDir)) {
            foreach (glob("{$cacheDir}/*.cache") as $file) {
                @unlink($file);
            }
        }
    }

    public function remember($key, $minutes, $callback)
    {
        $value = $this->get($key);

        if ($value === null) {
            $value = $callback();
            $this->put($key, $value, $minutes);
        }

        return $value;
    }
}

function cache()
{
    return Cache::getInstance();
}
