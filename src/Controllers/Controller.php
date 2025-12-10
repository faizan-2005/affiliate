<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Request;

class Controller
{
    protected $request;
    protected $response;

    public function __construct()
    {
        $this->request = request();
        $this->response = new Response();
    }

    protected function view($view, $data = [])
    {
        return $this->response->view($view, $data);
    }

    protected function json($data, $code = 200)
    {
        return $this->response->status($code)->json($data);
    }

    protected function redirect($url, $code = 302)
    {
        return $this->response->redirect($url, $code);
    }

    protected function requireAuth()
    {
        if (!auth()->check()) {
            return $this->redirect('/login');
        }
    }

    protected function requireRole($role)
    {
        if (!auth()->check() || !auth()->user()->hasRole($role)) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }
    }

    protected function validate($rules)
    {
        return $this->request->validate($rules);
    }

    protected function input($key = null, $default = null)
    {
        return $this->request->input($key, $default);
    }

    protected function hasErrors($errors)
    {
        return !empty(array_filter($errors));
    }
}
