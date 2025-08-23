<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Permission.php';

class PostsApi {
    public function list() {
        $d = db();

        $page = max(1, intParam('page', 1));
        $perPage = min(
            $GLOBALS['__API_CONFIG__']['PAGINATION']['maxPerPage'],
            max(1, intParam('per_page', $GLOBALS['__API_CONFIG__']['PAGINATION']['perPage']))
        );
        $offset = ($page - 1) * $perPage;

        $status = strParam('status'); // publish / draft 等（可选）
        $keyword = strParam('q');     // 搜索标题/内容（可选）

        $sel = $d->select()->from('table.contents')
            ->where('type = ?', 'post')
            ->order('created', Typecho_Db::SORT_DESC)
            ->offset($offset)->limit($perPage);

        if ($status) $sel->where('status = ?', $status);
        if ($keyword) {
            $like = '%' . $keyword . '%';
            $sel->where('title LIKE ? OR text LIKE ?', $like, $like);
        }

        $rows = $d->fetchAll($sel);

        // 统计总数（为分页）
        $cntSel = $d->select(['COUNT(*)' => 'cnt'])->from('table.contents')->where('type = ?', 'post');
        if ($status) $cntSel->where('status = ?', $status);
        if ($keyword) $cntSel->where('title LIKE ? OR text LIKE ?', '%' . $keyword . '%', '%' . $keyword . '%');
        $total = (int)$d->fetchObject($cntSel)->cnt;

        Response::json([
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int)ceil($total / $perPage)
            ]
        ]);
    }

    public function detail($params) {
        if (!isset($params['cid']) || !is_numeric($params['cid'])) {
            Response::json(['error' => 'Invalid post ID'], 400);
        }
        
        $cid = (int)$params['cid'];
        $d = db();
        $row = $d->fetchRow($d->select()->from('table.contents')->where('cid = ?', $cid)->limit(1));
        if (!$row) Response::json(['error' => 'Post not found'], 404);

        // metas（分类/标签）
        $mids = $d->fetchAll($d->select('m.*')->from('table.metas AS m')
            ->join('table.relationships AS r', 'm.mid = r.mid', Typecho_Db::LEFT_JOIN)
            ->where('r.cid = ?', $cid));
        $row['metas'] = $mids;

        // comments count
        $cc = $d->fetchObject($d->select(['COUNT(*)' => 'cnt'])->from('table.comments')->where('cid = ?', $cid))->cnt;
        $row['commentsCount'] = (int)$cc;

        Response::json($row);
    }

    public function create() {
        // 权限检查
        $user = Auth::getUser();
        Permission::require('post_create', $user);

        $d = db();
        $body = jsonBody();

        $title = trim($body['title'] ?? '');
        $text  = (string)($body['text'] ?? '');
        if ($title === '' || $text === '') Response::json(['error' => 'title & text required'], 400);

        $status = $body['status'] ?? 'publish'; // draft/publish
        $slug   = $body['slug'] ? slugify($body['slug']) : slugify($title);
        $author = (int)($body['authorId'] ?? ($user['uid'] ?? 1));
        $created= isset($body['created']) ? (int)$body['created'] : now();

        // 验证authorId是否存在
        if ($author > 0) {
            $authorExists = $d->fetchRow($d->select()->from('table.users')->where('uid = ?', $author)->limit(1));
            if (!$authorExists) Response::json(['error' => 'Author not found'], 400);
        }

        $cid = $d->query($d->insert('table.contents')->rows([
            'title'    => $title,
            'slug'     => $slug,
            'created'  => $created,
            'modified' => now(),
            'text'     => $text,
            'order'    => 0,
            'authorId' => $author,
            'template' => NULL,
            'type'     => 'post',
            'status'   => $status,
            'password' => NULL,
            'commentsNum' => 0,
            'allowComment' => 1,
            'allowPing' => 1,
            'allowFeed' => 1
        ]));

        // 分类/标签
        $categoryNames = (array)($body['categories'] ?? []); // by name
        $tagNames      = (array)($body['tags'] ?? []);       // by name

        $midList = [];
        foreach ($categoryNames as $n) {
            if (is_string($n) && trim($n) !== '') {
                $midList[] = ensureMeta(trim($n), 'category');
            }
        }
        foreach ($tagNames as $n) {
            if (is_string($n) && trim($n) !== '') {
                $midList[] = ensureMeta(trim($n), 'tag');
            }
        }
        if ($midList) setRelationships($cid, array_unique($midList));

        Response::json(['success' => true, 'cid' => $cid], 201);
    }

    public function update($params) {
        if (!isset($params['cid']) || !is_numeric($params['cid'])) {
            Response::json(['error' => 'Invalid post ID'], 400);
        }
        
        $cid = (int)$params['cid'];
        $d = db();
        $body = jsonBody();

        $exist = $d->fetchRow($d->select()->from('table.contents')->where('cid = ?', $cid)->limit(1));
        if (!$exist) Response::json(['error' => 'Post not found'], 404);
        
        // 权限检查
        $user = Auth::getUser();
        Permission::require('post_edit', $user, $exist);

        $rows = [];
        if (isset($body['title']))    $rows['title'] = trim((string)$body['title']);
        if (isset($body['text']))     $rows['text'] = (string)$body['text'];
        if (isset($body['status']))   $rows['status'] = (string)$body['status'];
        if (array_key_exists('slug', $body)) $rows['slug'] = slugify((string)$body['slug']);
        if (isset($body['allowComment'])) $rows['allowComment'] = (int)$body['allowComment'];
        if ($rows) $rows['modified'] = now();

        if ($rows) $d->query($d->update('table.contents')->rows($rows)->where('cid = ?', $cid));

        // 分类/标签（完全覆盖）
        $categoryNames = isset($body['categories']) ? (array)$body['categories'] : null;
        $tagNames      = isset($body['tags']) ? (array)$body['tags'] : null;

        if (is_array($categoryNames) || is_array($tagNames)) {
            $midList = [];
            if (is_array($categoryNames)) {
                foreach ($categoryNames as $n) {
                    if (is_string($n) && trim($n) !== '') {
                        $midList[] = ensureMeta(trim($n), 'category');
                    }
                }
            }
            if (is_array($tagNames)) {
                foreach ($tagNames as $n) {
                    if (is_string($n) && trim($n) !== '') {
                        $midList[] = ensureMeta(trim($n), 'tag');
                    }
                }
            }
            setRelationships($cid, array_unique($midList));
        }

        Response::json(['success' => true]);
    }

    public function delete($params) {
        if (!isset($params['cid']) || !is_numeric($params['cid'])) {
            Response::json(['error' => 'Invalid post ID'], 400);
        }
        
        $cid = (int)$params['cid'];
        $d = db();

        $exist = $d->fetchRow($d->select()->from('table.contents')->where('cid = ?', $cid)->limit(1));
        if (!$exist) Response::json(['error' => 'Post not found'], 404);
        
        // 权限检查
        $user = Auth::getUser();
        Permission::require('post_delete', $user, $exist);

        // 获取文章关联的分类和标签，以便更新计数
        $metas = $d->fetchAll($d->select('m.mid, m.type, m.count')
            ->from('table.metas AS m')
            ->join('table.relationships AS r', 'm.mid = r.mid')
            ->where('r.cid = ?', $cid));

        // 删除关系和文章
        $d->query($d->delete('table.relationships')->where('cid = ?', $cid));
        $d->query($d->delete('table.comments')->where('cid = ?', $cid));
        $d->query($d->delete('table.contents')->where('cid = ?', $cid));

        // 更新分类和标签的计数
        foreach ($metas as $meta) {
            if ($exist['status'] === 'publish' && $exist['type'] === 'post' && $meta['count'] > 0) {
                $d->query($d->update('table.metas')
                    ->expression('count', 'count - 1')
                    ->where('mid = ?', $meta['mid']));
            }
        }

        Response::json(['success' => true]);
    }
}