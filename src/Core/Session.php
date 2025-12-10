<?php

namespace App\Core;

class Session
{
    protected static $started = false;

    public static function start()
    {
        if (self::$started) {
            return;
        }

        $sessionPath = storage_path('sessions');
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }

        session_save_path($sessionPath);
        session_start([
            'name' => 'affiliate_session',
            'lifetime' => 3600,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        self::$started = true;
    }

    public static function put($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function forget($key)
    {
        unset($_SESSION[$key]);
    }

    public static function flush()
    {
        session_destroy();
    }

    public static function regenerate()
    {
        session_regenerate_id(true);
    }

    public static function flash($key, $value)
    {
        self::put("_flash_{$key}", $value);
    }

    public static function getFlash($key, $default = null)
    {
        $value = self::get("_flash_{$key}", $default);
        self::forget("_flash_{$key}");
        return $value;
    }

    public static function all()
    {
        return $_SESSION;
    }
}
