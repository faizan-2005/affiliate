<?php

namespace App\Core;

class Request
{
    protected $method;
    protected $uri;
    protected $path;
    protected $query;
    protected $post;
    protected $headers;
    protected $server;
    protected $cookies;
    protected $ip;
    protected $userAgent;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?? '/';
        $this->query = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->headers = $this->getHeaders();
        $this->server = $_SERVER ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->ip = $this->detectIP();
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    private function getHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('HTTP_', '', $key);
                $headerName = str_replace('_', '-', $headerName);
                $headers[strtolower($headerName)] = $value;
            }
        }
        return $headers;
    }

    private function detectIP()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function method()
    {
        return $this->method;
    }

    public function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function isGet()
    {
        return $this->isMethod('GET');
    }

    public function isPost()
    {
        return $this->isMethod('POST');
    }

    public function path()
    {
        return $this->path;
    }

    public function uri()
    {
        return $this->uri;
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input($key = null, $default = null)
    {
        $data = array_merge($this->query, $this->post);
        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    public function header($key, $default = null)
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function headers()
    {
        return $this->headers;
    }

    public function cookie($key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    public function cookies()
    {
        return $this->cookies;
    }

    public function ip()
    {
        return $this->ip;
    }

    public function userAgent()
    {
        return $this->userAgent;
    }

    public function isJson()
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }

    public function json()
    {
        if ($this->isJson()) {
            return json_decode(file_get_contents('php://input'), true);
        }
        return null;
    }

    public function validate($rules)
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $rulesList = explode('|', $rule);

            foreach ($rulesList as $r) {
                $r = trim($r);
                if ($r === 'required' && empty($value)) {
                    $errors[$field][] = "{$field} is required";
                } elseif (strpos($r, 'min:') === 0 && strlen($value) < (int)substr($r, 4)) {
                    $errors[$field][] = "{$field} must be at least " . substr($r, 4) . " characters";
                } elseif (strpos($r, 'max:') === 0 && strlen($value) > (int)substr($r, 4)) {
                    $errors[$field][] = "{$field} must not exceed " . substr($r, 4) . " characters";
                } elseif ($r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "{$field} must be a valid email";
                }
            }
        }
        return $errors;
    }
}

function request()
{
    static $request;
    if (!$request) {
        $request = new Request();
    }
    return $request;
}
