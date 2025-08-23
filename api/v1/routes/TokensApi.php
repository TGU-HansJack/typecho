<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../Auth.php';

class TokensApi {
    /**
     * 用户登录并获取访问令牌
     */
    public function create() {
        $body = jsonBody();
        $name = trim($body['name'] ?? '');
        $password = $body['password'] ?? '';
        
        if (!$name || !$password) {
            Response::json(['error' => 'Username and password required'], 400);
        }
        
        $d = db();
        $user = $d->fetchRow($d->select()->from('table.users')->where('name = ? OR mail = ?', $name, $name)->limit(1));
        
        if (!$user) {
            Response::json(['error' => 'Invalid credentials'], 401);
        }
        
        // 验证密码
        if (!class_exists('PasswordHash')) {
            require_once __TYPECHO_ROOT_DIR__ . '/var/Utils/PasswordHash.php';
        }
        $hasher = new PasswordHash(8, true);
        if (!$hasher->checkPassword($password, $user['password'])) {
            Response::json(['error' => 'Invalid credentials'], 401);
        }
        
        // 生成JWT令牌
        $token = Auth::generateToken((int)$user['uid']);
        
        Response::json([
            'success' => true,
            'token' => $token,
            'user' => [
                'uid' => $user['uid'],
                'name' => $user['name'],
                'screenName' => $user['screenName'],
                'mail' => $user['mail'],
                'group' => $user['group']
            ]
        ]);
    }
    
    /**
     * 刷新访问令牌
     */
    public function refresh() {
        $user = Auth::getUser();
        if (!$user) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $token = Auth::generateToken((int)$user['uid']);
        
        Response::json([
            'success' => true,
            'token' => $token
        ]);
    }
    
    /**
     * 注销令牌（客户端应删除本地存储的令牌）
     */
    public function delete() {
        // JWT是无状态的，服务器端无法直接注销令牌
        // 客户端应删除本地存储的令牌
        Response::json(['success' => true]);
    }
}