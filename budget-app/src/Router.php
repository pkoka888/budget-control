<?php
namespace BudgetApp;

class Router {
    private array $routes = [];

    public function get(string $path, string $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, string $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, string $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, string $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'pattern' => $this->convertPathToRegex($path)
        ];
    }

    private function convertPathToRegex(string $path): string {
        // Convert /path/:id/subpath to regex pattern
        $pattern = preg_quote($path, '#');
        $pattern = preg_replace('#\\\:([a-zA-Z_][a-zA-Z0-9_]*)#', '(?P<\1>[^/]+)', $pattern);
        return "#^{$pattern}$#";
    }

    public function match(string $method, string $path): ?array {
        // Normalize path
        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }

                return [
                    'handler' => $route['handler'],
                    'params' => $params
                ];
            }
        }

        return null;
    }
}
