<?php
include_once 'config.inc.php';

// 使用Utils\Helper添加action路由
Utils\Helper::addAction('users-token', 'Widget_Users_TokenAction');

echo "Action路由已添加: users-token => Widget_Users_TokenAction<br>\n";

// 显示当前的actionTable
$options = \Widget\Options::alloc();
$actionTable = unserialize($options->actionTable);
echo "<h3>当前Action路由表:</h3>\n";
echo "<pre>" . print_r($actionTable, true) . "</pre>\n";