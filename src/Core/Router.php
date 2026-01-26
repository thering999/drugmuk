<?php

namespace App\Core;

class Router {
    protected $routes = [];

    public function get($uri, $controller) {
        $this->addRoute('GET', $uri, $controller);
    }

    public function post($uri, $controller) {
        $this->addRoute('POST', $uri, $controller);
    }

    public function put($uri, $controller) {
        $this->addRoute('PUT', $uri, $controller);
    }

    public function delete($uri, $controller) {
        $this->addRoute('DELETE', $uri, $controller);
    }

    protected function addRoute($method, $uri, $controller) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'controller' => $controller
        ];
    }

    public function dispatch($uri, $method) {
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Support method override for PUT/DELETE via _method parameter
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        // Also check for X-HTTP-Method-Override header
        if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route pattern to regex
            // First, escape special regex characters except for our placeholders
            $pattern = $route['uri'];
            
            // Replace {param} with regex pattern for dynamic segments
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $pattern);
            
            // Escape forward slashes and other special chars for regex
            $pattern = str_replace('/', '\/', $pattern);
            
            // Build final regex pattern with optional trailing slash
            $pattern = "#^" . $pattern . "/?$#";

            if (preg_match($pattern, $uri, $matches)) {
                // CSRF Protection for state-changing methods
                if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
                    try {
                        \App\Core\CSRF::verifyRequest();
                    } catch (\Exception $e) {
                        http_response_code(403);
                        echo "Forbidden: " . $e->getMessage();
                        return;
                    }
                }

                $parts = explode('@', $route['controller']);
                $controllerName = "App\\Controllers\\" . $parts[0];
                $action = $parts[1];

                // Extract only named parameters (not numeric keys)
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[] = $value; // Use numeric array for call_user_func_array
                    }
                }

                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $action)) {
                        call_user_func_array([$controller, $action], $params);
                        return;
                    } else {
                        echo "Method $action not found in controller $controllerName";
                        return;
                    }
                } else {
                    echo "Controller $controllerName not found";
                    return;
                }
            }
        }


        http_response_code(404);
        echo "404 Not Found";
    }
    
    /**
     * Run the router - dispatch current request
     */
    public function run() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $this->dispatch($uri, $method);
    }
}
