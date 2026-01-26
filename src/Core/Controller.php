<?php

namespace App\Core;

class Controller {
    protected function view($view, $data = []) {
        header('Content-Type: text/html; charset=UTF-8');
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View does not exist: " . $view);
        }
    }

    protected function redirect($url) {
        header("Location: $url");
        exit;
    }

    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
