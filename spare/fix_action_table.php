<?php
include_once 'config.inc.php';

// 获取数据库实例
$db = \Typecho\Db::get();

// 获取当前的actionTable
$options = \Widget\Options::alloc();
$actionTable = unserialize($options->actionTable);

echo "修复前的Action路由表:\n";
print_r($actionTable);

// 添加users-token路由
$actionTable['users-token'] = 'Widget_Users_TokenAction';

// 更新数据库
$result = $db->query($db->update('table.options')
    ->rows(['value' => serialize($actionTable)])
    ->where('name = ?', 'actionTable'));

echo "\n已更新actionTable，影响行数: " . $result . "\n";

// 验证更新
$updatedOptions = \Widget\Options::alloc();
$updatedActionTable = unserialize($updatedOptions->actionTable);

echo "\n修复后的Action路由表:\n";
print_r($updatedActionTable);

if (isset($updatedActionTable['users-token'])) {
    echo "\nusers-token路由已成功添加: " . $updatedActionTable['users-token'] . "\n";
} else {
    echo "\nusers-token路由添加失败\n";
}