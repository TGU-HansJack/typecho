<?php
include 'config.inc.php';

// 获取数据库实例
$db = \Typecho\Db::get();

// 获取当前actionTable
$options = \Widget\Options::alloc();
$actionTable = $options->actionTable;
echo "Current actionTable: " . $actionTable . "<br>";

// 反序列化当前actionTable
$actionTableArray = unserialize($actionTable);
if (!is_array($actionTableArray)) {
    $actionTableArray = [];
}

// 添加users-token action
$actionTableArray['users-token'] = 'Widget_Users_TokenAction';

// 序列化新的actionTable
$newActionTable = serialize($actionTableArray);
echo "New actionTable: " . $newActionTable . "<br>";

// 更新数据库中的actionTable
$result = $db->query($db->update('table.options')
    ->rows(['value' => $newActionTable])
    ->where('name = ?', 'actionTable'));

echo "Update result: " . $result . "<br>";
echo "Action added successfully!";