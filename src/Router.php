<?php

/**
 * Router
 *
 * Handles route registration, matching, and dispatching
 */
class Router
{
    private array $routes           = [];
    private array $middlewareGroups = [];
    private array $globalMiddleware = [];

    /**
     * Register a GET route
     */
    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add a route to the routing table
     */
    private function addRoute(string $method, string $path, $handler, array $middleware): void
    {
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'handler'    => $handler,
            'middleware' => $middleware,
            'pattern'    => $this->convertToRegex($path),
        ];
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex(string $path): string
    {
        // Convert {param} to named capture groups first
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);

        // Escape forward slashes for regex delimiter
        $pattern = str_replace('/', '\/', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Register global middleware (runs on all routes)
     */
    public function addGlobalMiddleware($middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Register a middleware group
     */
    public function middlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    /**
     * Dispatch the request
     */
    public function dispatch(string $method, string $uri): void
    {
        // Parse URI to remove query string
        $uri = parse_url($uri, PHP_URL_PATH);

        // Find matching route
        $matchedRoute = null;
        $params       = [];

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $matchedRoute = $route;

                // Extract named parameters
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                break;
            }
        }

        // Handle 404
        if (! $matchedRoute) {
            $this->handleNotFound();
            return;
        }

        // Build middleware pipeline
        $middleware = array_merge(
            $this->globalMiddleware,
            $matchedRoute['middleware']
        );

        // Execute middleware pipeline
        $this->executeMiddleware($middleware, $matchedRoute['handler'], $params);
    }

    /**
     * Execute middleware pipeline
     */
    private function executeMiddleware(array $middleware, $handler, array $params): void
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            function ($next, $middleware) use ($params) {
                return function () use ($middleware, $next, $params) {
                    $middlewareInstance = is_string($middleware) ? new $middleware() : $middleware;
                    return $middlewareInstance->handle($next, $params);
                };
            },
            function () use ($handler, $params) {
                return $this->executeHandler($handler, $params);
            }
        );

        $pipeline();
    }

    /**
     * Execute the route handler
     */
    private function executeHandler($handler, array $params)
    {
        if (is_callable($handler)) {
            // Closure handler
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            // Controller@method format
            list($controller, $method) = explode('@', $handler);

            if (! class_exists($controller)) {
                throw new Exception("Controller {$controller} not found");
            }

            $controllerInstance = new $controller();

            if (! method_exists($controllerInstance, $method)) {
                throw new Exception("Method {$method} not found in controller {$controller}");
            }

            return call_user_func_array([$controllerInstance, $method], $params);
        }

        throw new Exception('Invalid route handler');
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound(): void
    {
        http_response_code(404);

        if (file_exists(__DIR__ . '/../views/errors/404.php')) {
            require __DIR__ . '/../views/errors/404.php';
        } else {
            echo '<h1>404 Not Found</h1>';
            echo '<p>The requested page could not be found.</p>';
        }

        exit;
    }
}
