<?php
require_once __DIR__ . '/../helpers.php';

class CategoriesApi {
    public function list() {
        $d = db();
        $rows = $d->fetchAll($d->select()->from('table.metas')->where('type = ?', 'category')->order('order', Typecho_Db::SORT_ASC));
        Response::json(['data' => $rows]);
    }

    public function create() {
        Auth::require();
        $d = db();
        $b = jsonBody();
        $name = trim($b['name'] ?? '');
        if ($name === '') Response::json(['error' => 'name required'], 400);
        $slug = isset($b['slug']) ? slugify($b['slug']) : slugify($name);
        $parent = (int)($b['parent'] ?? 0);
        $order  = (int)($b['order'] ?? 0);

        // 检查父分类是否存在
        if ($parent > 0) {
            $parentExists = $d->fetchRow($d->select()->from('table.metas')->where('mid = ?', $parent)->where('type = ?', 'category')->limit(1));
            if (!$parentExists) Response::json(['error' => 'Parent category not found'], 400);
        }

        $mid = ensureMeta($name, 'category', $slug);
        if ($parent || $order) {
            $d->query($d->update('table.metas')->rows(['parent' => $parent, 'order' => $order])->where('mid = ?', $mid));
        }
        Response::json(['success' => true, 'mid' => $mid], 201);
    }

    public function update($params) {
        Auth::require();
        
        if (!isset($params['mid']) || !is_numeric($params['mid'])) {
            Response::json(['error' => 'Invalid category ID'], 400);
        }
        
        $mid = (int)$params['mid'];
        $d = db();
        $b = jsonBody();

        $exist = $d->fetchRow($d->select()->from('table.metas')->where('mid = ?', $mid)->where('type = ?', 'category'));
        if (!$exist) Response::json(['error' => 'Category not found'], 404);

        $rows = [];
        if (isset($b['name'])) $rows['name'] = trim((string)$b['name']);
        if (array_key_exists('slug', $b)) $rows['slug'] = slugify((string)$b['slug']);
        if (isset($b['order'])) $rows['order'] = (int)$b['order'];
        if (isset($b['parent'])) {
            $parent = (int)$b['parent'];
            // 检查父分类是否存在
            if ($parent > 0) {
                $parentExists = $d->fetchRow($d->select()->from('table.metas')->where('mid = ?', $parent)->where('type = ?', 'category')->limit(1));
                if (!$parentExists) Response::json(['error' => 'Parent category not found'], 400);
            }
            $rows['parent'] = $parent;
        }

        if ($rows) $d->query($d->update('table.metas')->rows($rows)->where('mid = ?', $mid));
        Response::json(['success' => true]);
    }

    public function delete($params) {
        Auth::require();
        
        if (!isset($params['mid']) || !is_numeric($params['mid'])) {
            Response::json(['error' => 'Invalid category ID'], 400);
        }
        
        $mid = (int)$params['mid'];
        $d = db();

        $exist = $d->fetchRow($d->select()->from('table.metas')->where('mid = ?', $mid)->where('type = ?', 'category'));
        if (!$exist) Response::json(['error' => 'Category not found'], 404);

        $d->query($d->delete('table.relationships')->where('mid = ?', $mid));
        $d->query($d->delete('table.metas')->where('mid = ?', $mid));
        Response::json(['success' => true]);
    }
}