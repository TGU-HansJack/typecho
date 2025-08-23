<?php
class Router {
    private $routes = [];

    public function __construct(array $routes = []) {
        foreach ($routes as $r) {
            // [method, regex, [class, method], requireAuth?]
            $this->routes[] = [
                'method' => $r[0],
                'regex'  => $r[1],
                'handler'=> $r[2],
                'auth'   => $r[3] ?? false,
            ];
        }
    }

    public function dispatch($method, $path) {
        // 处理OPTIONS请求
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        
        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) continue;
            if (preg_match($r['regex'], $path, $m)) {
                if ($r['auth'] && !Auth::check()) {
                    Response::json(['error' => 'Unauthorized'], 401);
                }
                $args = [];
                foreach ($m as $k => $v) if (!is_int($k)) $args[$k] = $v;
                [$class, $fn] = $r['handler'];
                
                // 检查类和方法是否存在
                if (!class_exists($class)) {
                    Response::json(['error' => 'Handler class not found: ' . $class], 500);
                }
                
                if (!method_exists($class, $fn)) {
                    Response::json(['error' => 'Handler method not found: ' . $class . '::' . $fn], 500);
                }
                
                $instance = new $class();
                return call_user_func_array([$instance, $fn], [$args]);
            }
        }
        Response::json(['error' => 'Not Found'], 404);
    }
}