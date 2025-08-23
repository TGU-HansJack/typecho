<?php
include_once 'config.inc.php';

// 使用Utils\Helper添加action路由
Utils\Helper::addAction('users-token', 'Widget_Users_TokenAction');

echo "Action路由已添加: users-token => Widget_Users_TokenAction\n";