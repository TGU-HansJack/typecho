<?php
require_once __DIR__ . '/../helpers.php';

class CommentsApi {
    public function list() {
        $d = db();
        $page = max(1, intParam('page', 1));
        $perPage = min(
            $GLOBALS['__API_CONFIG__']['PAGINATION']['maxPerPage'],
            max(1, intParam('per_page', $GLOBALS['__API_CONFIG__']['PAGINATION']['perPage']))
        );
        $offset = ($page - 1) * $perPage;

        $status = strParam('status'); // approved / waiting / spam / hold（Typecho 有 waiting/approved/spam 等）
        $cid = intParam('cid', 0);

        $sel = $d->select()->from('table.comments')->order('created', Typecho_Db::SORT_DESC)->offset($offset)->limit($perPage);
        if ($status) $sel->where('status = ?', $status);
        if ($cid)    $sel->where('cid = ?', $cid);

        $rows = $d->fetchAll($sel);

        $cntSel = $d->select(['COUNT(*)' => 'cnt'])->from('table.comments');
        if ($status) $cntSel->where('status = ?', $status);
        if ($cid)    $cntSel->where('cid = ?', $cid);
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

    public function create() {
        // 评论投稿一般不强制鉴权
        $d = db();
        $b = jsonBody();

        $cid = (int)($b['cid'] ?? 0);
        $text = trim($b['text'] ?? '');
        $author = trim($b['author'] ?? 'Guest');
        $mail   = trim($b['mail'] ?? '');
        $url    = trim($b['url'] ?? '');
        $parent = (int)($b['parent'] ?? 0);

        if (!$cid || $text === '') Response::json(['error' => 'cid & text required'], 400);

        $post = $d->fetchRow($d->select()->from('table.contents')->where('cid = ?', $cid));
        if (!$post) Response::json(['error' => 'Post not found'], 404);

        // 验证父评论是否存在
        if ($parent > 0) {
            $parentExists = $d->fetchRow($d->select()->from('table.comments')->where('coid = ?', $parent)->limit(1));
            if (!$parentExists) Response::json(['error' => 'Parent comment not found'], 400);
        }

        $d->query($d->insert('table.comments')->rows([
            'cid' => $cid,
            'created' => now(),
            'author'  => $author,
            'authorId'=> 0,
            'ownerId' => (int)$post['authorId'],
            'mail'    => $mail,
            'url'     => $url,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
            'agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'text'    => $text,
            'type'    => 'comment',
            'status'  => 'waiting',
            'parent'  => $parent
        ]));

        // 更新文章评论数（简单做法：重新统计）
        $cc = $d->fetchObject($d->select(['COUNT(*)' => 'cnt'])->from('table.comments')->where('cid = ?', $cid)->where('status = ?', 'approved'))->cnt;
        $d->query($d->update('table.contents')->rows(['commentsNum' => (int)$cc])->where('cid = ?', $cid));

        Response::json(['success' => true], 201);
    }

    public function update($params) {
        Auth::require();
        
        if (!isset($params['coid']) || !is_numeric($params['coid'])) {
            Response::json(['error' => 'Invalid comment ID'], 400);
        }
        
        $coid = (int)$params['coid'];
        $d = db();
        $b = jsonBody();

        $exist = $d->fetchRow($d->select()->from('table.comments')->where('coid = ?', $coid)->limit(1));
        if (!$exist) Response::json(['error' => 'Comment not found'], 404);

        $rows = [];
        if (isset($b['text']))   $rows['text'] = (string)$b['text'];
        if (isset($b['status'])) $rows['status'] = (string)$b['status']; // approved / waiting / spam / hold

        if ($rows) {
            $d->query($d->update('table.comments')->rows($rows)->where('coid = ?', $coid));
        }
        Response::json(['success' => true]);
    }

    public function delete($params) {
        Auth::require();
        
        if (!isset($params['coid']) || !is_numeric($params['coid'])) {
            Response::json(['error' => 'Invalid comment ID'], 400);
        }
        
        $coid = (int)$params['coid'];
        $d = db();
        $exist = $d->fetchRow($d->select()->from('table.comments')->where('coid = ?', $coid)->limit(1));
        if (!$exist) Response::json(['error' => 'Comment not found'], 404);

        $d->query($d->delete('table.comments')->where('coid = ?', $coid));
        Response::json(['success' => true]);
    }
}