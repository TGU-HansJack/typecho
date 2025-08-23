<?php
function db(): Typecho_Db {
    return Typecho_Db::get();
}

function jsonBody(): array {
    $raw = file_get_contents('php://input');
    $arr = json_decode($raw ?: '[]', true);
    return is_array($arr) ? $arr : [];
}

function intParam($name, $default = 0) {
    $v = $_GET[$name] ?? null;
    return is_numeric($v) ? (int)$v : $default;
}

function strParam($name, $default = '') {
    $v = $_GET[$name] ?? null;
    return is_string($v) ? trim($v) : $default;
}

function now(): int { return time(); }

function slugify($text) {
    if (class_exists('Typecho_Common') && method_exists('Typecho_Common', 'slugName')) {
        return Typecho_Common::slugName($text);
    }
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]+/u', '-', $text);
    $text = trim($text, '-');
    return $text ?: uniqid('post-');
}

/**
 * 确保 metas 中存在（tag/category），不存在则创建，返回 mid
 */
function ensureMeta(string $name, string $type, ?string $slug = null): int {
    if (empty($name)) {
        throw new InvalidArgumentException('Meta name cannot be empty');
    }
    
    $d = db();
    $row = $d->fetchRow($d->select()->from('table.metas')->where('name = ?', $name)->where('type = ?', $type)->limit(1));
    if ($row) return (int)$row['mid'];
    $slug = $slug ? slugify($slug) : slugify($name);
    $mid = $d->query($d->insert('table.metas')->rows([
        'name' => $name,
        'slug' => $slug,
        'type' => $type,
        'order'=> 0,
        'parent'=> 0
    ]));
    return $mid;
}

/**
 * 设置 content<->meta 关系（先清除旧的再绑定）
 */
function setRelationships(int $cid, array $mids) {
    if ($cid <= 0) {
        throw new InvalidArgumentException('Invalid content ID');
    }
    
    $d = db();
    $d->query($d->delete('table.relationships')->where('cid = ?', $cid));
    foreach ($mids as $mid) {
        if (is_numeric($mid) && $mid > 0) {
            $d->query($d->insert('table.relationships')->rows(['cid' => $cid, 'mid' => (int)$mid]));
        }
    }
}