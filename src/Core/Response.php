<?php

namespace App\Core;

class Response
{
    protected $data;
    protected $status;
    protected $headers = [];

    public function __construct($data = [], $status = 200)
    {
        $this->data = $data;
        $this->status = $status;
    }

    public function json($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        }

        header('Content-Type: application/json');
        http_response_code($this->status);

        foreach ($this->headers as $header => $value) {
            header("{$header}: {$value}");
        }

        echo json_encode($this->data);
        exit;
    }

    public function view($view, $data = [])
    {
        $viewPath = base_path("/views/{$view}.php");

        if (!file_exists($viewPath)) {
            abort(404, "View not found: {$view}");
        }

        extract($data);

        http_response_code($this->status);

        foreach ($this->headers as $header => $value) {
            header("{$header}: {$value}");
        }

        include $viewPath;
        exit;
    }

    public function redirect($url, $status = 302)
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    public function download($file, $name = null)
    {
        if (!file_exists($file)) {
            abort(404, "File not found");
        }

        $name = $name ?: basename($file);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($file));

        readfile($file);
        exit;
    }

    public function header($key, $value)
    {
        $this->headers[$k]=$k = $value;
        return $this;
    }

    public function status($code)
    {
        $this->status = $code;
        return $this;
    }

    public function send()
    {
        http_response_code($this->status);

        foreach ($this->headers as $header => $value) {
            header("{$header}: {$value}");
        }

        echo $this->data;
        exit;
    }
}
