<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../Auth.php';

class TagsApi {
    public function list() {
        $d = db();
        $rows = $d->fetchAll($d->select()->from('table.metas')->where('type = ?', 'tag')->order('name', Typecho_Db::SORT_ASC));
        Response::json(['data' => $rows]);
    }

    public function create() {
        Auth::require();
        $b = jsonBody();
        $name = trim($b['name'] ?? '');
        if ($name === '') Response::json(['error' => 'name required'], 400);
        $mid = ensureMeta($name, 'tag', $b['slug'] ?? null);
        Response::json(['success' => true, 'mid' => $mid], 201);
    }

    public function update($params) {
        Auth::require();
        
        if (!isset($params['mid']) || !is_numeric($params['mid'])) {
            Response::json(['error' => 'Invalid tag ID'], 400);
        }
        
        $mid = (int)$params['mid'];
        $d = db();
        $b = jsonBody();

        $exist = $d->fetchRow($d->select()->from('table.metas')->where('mid = ?', $mid)->where('type = ?', 'tag'));
        if (!$exist) Response::json(['error' => 'Tag not found'], 404);

        $rows = [];
        if (isset($b['name'])) $rows['name'] = trim((string)$b['name']);
        if (array_key_exists('slug', $b)) $rows['slug'] = slugify((string)$b['slug']);

        if ($rows) $d->query($d->update('table.metas')->rows($rows)->where('mid = ?', $mid));
        Response::json(['success' => true]);
    }

    public function delete($params) {
        Auth::require();
        
        if (!isset($params['mid']) || !is_numeric($params['mid'])) {
            Response::json(['error' => 'Invalid tag ID'], 400);
        }
        
        $mid = (int)$params['mid'];
        $d = db();

        $exist = $d->fetchRow($d->select()->from('table.metas')->where('mid = ?', $mid)->where('type = ?', 'tag'));
        if (!$exist) Response::json(['error' => 'Tag not found'], 404);

        $d->query($d->delete('table.relationships')->where('mid = ?', $mid));
        $d->query($d->delete('table.metas')->where('mid = ?', $mid));
        Response::json(['success' => true]);
    }
}