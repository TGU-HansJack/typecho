<?php
include 'config.inc.php';

// 获取当前actionTable
$actionTable = \Utils\Helper::options()->actionTable;
echo "Current actionTable: " . $actionTable . "\n";

// 添加users-token action
\Utils\Helper::addAction('users-token', 'Widget_Users_TokenAction');

// 获取更新后的actionTable
$newActionTable = \Utils\Helper::options()->actionTable;
echo "New actionTable: " . $newActionTable . "\n";

echo "Action added successfully!\n";