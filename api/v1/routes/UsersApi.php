<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../Auth.php';

class UsersApi {
    public function list() {
        $d = db();
        $rows = $d->fetchAll($d->select('uid,name,screenName,mail,url,created,activated,logged,group')->from('table.users')->order('uid', Typecho_Db::SORT_ASC));
        Response::json(['data' => $rows]);
    }

    public function detail($params) {
        if (!isset($params['uid']) || !is_numeric($params['uid'])) {
            Response::json(['error' => 'Invalid user ID'], 400);
        }
        
        $uid = (int)$params['uid'];
        $d = db();
        $row = $d->fetchRow($d->select('uid,name,screenName,mail,url,created,activated,logged,group')->from('table.users')->where('uid = ?', $uid)->limit(1));
        if (!$row) Response::json(['error' => 'User not found'], 404);
        Response::json($row);
    }
}