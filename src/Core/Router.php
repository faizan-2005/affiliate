<?php

namespace App\Core;

class Router
{
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => [],
    ];

    protected $middleware = [];
    protected $currentGroup = null;

    public function __construct()
    {
        $this->registerRoutes();
    }

    private function registerRoutes()
    {
        // Auth routes
        $this->get('/login', 'AuthController@showLogin');
        $this->post('/login', 'AuthController@login');
        $this->get('/register', 'AuthController@showRegister');
        $this->post('/register', 'AuthController@register');
        $this->post('/logout', 'AuthController@logout');
        $this->get('/forgot-password', 'AuthController@showForgotPassword');
        $this->post('/forgot-password', 'AuthController@sendReset');

        // Dashboard routes
        $this->get('/', 'DashboardController@index');

        // Admin routes
        $this->get('/admin/dashboard', 'AdminController@dashboard');
        $this->get('/admin/users', 'AdminController@users');
        $this->get('/admin/offers', 'AdminController@offers');
        $this->get('/admin/reports', 'AdminController@reports');
        $this->get('/admin/fraud', 'AdminController@fraud');

        // Affiliate routes
        $this->get('/affiliate/dashboard', 'AffiliateController@dashboard');
        $this->get('/affiliate/offers', 'AffiliateController@offers');
        $this->get('/affiliate/reports', 'AffiliateController@reports');
        $this->get('/affiliate/payouts', 'AffiliateController@payouts');

        // Advertiser routes
        $this->get('/advertiser/dashboard', 'AdvertiserController@dashboard');
        $this->get('/advertiser/offers', 'AdvertiserController@offers');
        $this->get('/advertiser/conversions', 'AdvertiserController@conversions');
        $this->get('/advertiser/postbacks', 'AdvertiserController@postbacks');

        // Offer routes
        $this->post('/offer/create', 'OfferController@store');
        $this->post('/offer/{id}/update', 'OfferController@update');
        $this->post('/offer/{id}/delete', 'OfferController@destroy');

        // Click tracking (external)
        $this->get('/click', 'ClickController@track');

        // Conversion/Postback (external)
        $this->post('/postback', 'PostbackController@handle');
        $this->get('/postback', 'PostbackController@handle');
    }

    public function get($path, $action)
    {
        return $this->register('GET', $path, $action);
    }

    public function post($path, $action)
    {
        return $this->register('POST', $path, $action);
    }

    public function put($path, $action)
    {
        return $this->register('PUT', $path, $action);
    }

    public function delete($path, $action)
    {
        return $this->register('DELETE', $path, $action);
    }

    public function patch($path, $action)
    {
        return $this->register('PATCH', $path, $action);
    }

    protected function register($method, $path, $action)
    {
        $this->routes[$method][$path] = $action;
        return $this;
    }

    public function group($options, $callback)
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = $options;

        call_user_func($callback, $this);

        $this->currentGroup = $previousGroup;
    }

    public function dispatch()
    {
        $request = request();
        $method = $request->method();
        $path = $request->path();

        $route = $this->findRoute($method, $path);

        if (!$route) {
            abort(404, 'Route not found');
        }

        return $this->executeAction($route['action'], $route['params']);
    }

    protected function findRoute($method, $path)
    {
        if (isset($this->routes[$method][$path])) {
            return [
                'action' => $this->routes[$method][$path],
                'params' => [],
            ];
        }

        // Try pattern matching
        foreach ($this->routes[$method] as $pattern => $action) {
            $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }
                return [
                    'action' => $action,
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    protected function executeAction($action, $params)
    {
        list($controller, $method) = explode('@', $action);
        $controllerClass = "App\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            abort(500, "Controller not found: {$controllerClass}");
        }

        $instance = new $controllerClass();

        if (!method_exists($instance, $method)) {
            abort(500, "Method not found: {$method}");
        }

        return call_user_func_array([$instance, $method], $params);
    }
}

function route($name, $params = [])
{
    // Helper for generating route URLs - to be extended
    return '#';
}
