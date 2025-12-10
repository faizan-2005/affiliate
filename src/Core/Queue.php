<?php

namespace App\Core;

class Queue
{
    protected static $instance;
    protected $redis;
    protected $queueName;

    private function __construct()
    {
        $this->connectRedis();
        $this->queueName = 'affiliate:jobs';
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
            $config = config('redis.connections.queue');
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
            log_error('Queue Redis connection failed: ' . $e->getMessage());
        }
    }

    public function push($job, $data = [], $delay = 0)
    {
        $payload = [
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
            'created_at' => time(),
        ];

        if ($delay > 0) {
            $this->redis->zadd(
                $this->queueName . ':delayed',
                time() + $delay,
                json_encode($payload)
            );
        } else {
            $this->redis->rpush($this->queueName, json_encode($payload));
        }

        return true;
    }

    public function pop()
    {
        $item = $this->redis->lpop($this->queueName);
        return $item ? json_decode($item, true) : null;
    }

    public function processPending()
    {
        $now = time();
        $delayed = $this->redis->zrangebyscore($this->queueName . ':delayed', 0, $now);

        foreach ($delayed as $item) {
            $this->redis->rpush($this->queueName, $item);
            $this->redis->zrem($this->queueName . ':delayed', $item);
        }
    }

    public function size()
    {
        return $this->redis->llen($this->queueName);
    }

    public function clear()
    {
        $this->redis->del($this->queueName);
    }
}

function queue()
{
    return Queue::getInstance();
}
