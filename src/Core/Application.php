<?php

namespace App\Core;

class Application
{
    protected static $instance;
    protected $basePath;
    protected $config = [];
    protected $services = [];
    protected $booted = false;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        self::$instance = $this;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->loadEnvironment();
        $this->loadConfig();
        $this->registerServices();

        $this->booted = true;
    }

    private function loadEnvironment()
    {
        $envFile = $this->basePath . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value, '\'"');
            }
        }
    }

    private function loadConfig()
    {
        $configPath = $this->basePath . '/config';
        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    private function registerServices()
    {
        Database::getInstance();
        Cache::getInstance();
        Session::start();
    }

    public function config($key, $default = null)
    {
        $parts = explode('.', $key);
        $value = $this->config[$parts[0]] ?? null;

        for ($i = 1; $i < count($parts); $i++) {
            if (is_array($value) && isset($value[$parts[$i]])) {
                $value = $value[$parts[$i]];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public function basePath($path = '')
    {
        return $this->basePath . ($path ? '/' . $path : $path);
    }

    public function storagePath($path = '')
    {
        return $this->basePath . '/storage' . ($path ? '/' . $path : $path);
    }

    public function handle()
    {
        $router = new Router();
        return $router->dispatch();
    }
}

function app()
{
    return Application::getInstance();
}

function env($key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

function config($key, $default = null)
{
    return app()->config($key, $default);
}

function storage_path($path = '')
{
    return app()->storagePath($path);
}

function base_path($path = '')
{
    return app()->basePath($path);
}

function route($name, $params = [])
{
    // Route helper - to be implemented with Router
    return '#';
}

function dd($data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit;
}

function abort($code, $message = '')
{
    http_response_code($code);
    die($message ?: "Error $code");
}

function response($data = [], $code = 200)
{
    return new Response($data, $code);
}
