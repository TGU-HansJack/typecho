<?php
include_once 'config.inc.php';

// 获取当前actionTable
$options = \Widget\Options::alloc();
$actionTable = unserialize($options->actionTable);

echo "当前Action路由表:\n";
print_r($actionTable);

// 检查users-token是否存在
if (isset($actionTable['users-token'])) {
    echo "\nusers-token路由已存在: " . $actionTable['users-token'] . "\n";
} else {
    echo "\nusers-token路由不存在\n";
}