<?php
// 定义根目录
define('__TYPECHO_ROOT_DIR__', dirname(__DIR__));

// 载入配置支持
if (!@include_once __TYPECHO_ROOT_DIR__ . '/config.inc.php') {
    file_exists(__TYPECHO_ROOT_DIR__ . '/install.php') ? header('Location: install.php') : print('Missing Config File');
    exit;
}

// 自动加载本目录下类（简单实现）
spl_autoload_register(function($class) {
    $paths = [
        __DIR__ . '/' . $class . '.php',
        __DIR__ . '/v1/' . $class . '.php', // 提高v1目录下类的加载优先级
        __DIR__ . '/v1/middleware/' . $class . '.php',
        __DIR__ . '/v1/routes/' . $class . '.php',
    ];
    foreach ($paths as $p) {
        if (is_file($p)) { 
            require_once $p; 
            return; 
        }
    }
});

// 显式加载Auth类以确保其可用
require_once __DIR__ . '/v1/Auth.php';

// 载入配置
$GLOBALS['__API_CONFIG__'] = require __DIR__ . '/config.php';

// 初始化数据库（Typecho）
Typecho_Widget::widget('Widget_Init');

// JSON 统一：出错时也输出 JSON
set_exception_handler(function(Throwable $e){
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Internal Server Error', 'message' => $e->getMessage()]);
    exit;
});

set_error_handler(function($errno, $errstr, $errfile, $errline){
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'PHP Error',
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});