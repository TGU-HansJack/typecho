<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/Auth.php';

class Permission {
    // 用户组权限映射
    const PERMISSION_MAP = [
        'visitor' => 0,    // 游客
        'subscriber' => 1, // 注册用户
        'contributor' => 2,// 作者
        'editor' => 3,     // 编辑
        'administrator' => 4 // 管理员
    ];
    
    // 资源权限定义
    const RESOURCE_PERMISSIONS = [
        // 文章权限
        'post_view' => ['visitor', 'subscriber', 'contributor', 'editor', 'administrator'],
        'post_create' => ['subscriber', 'contributor', 'editor', 'administrator'],
        'post_edit' => ['contributor', 'editor', 'administrator'], // 作者只能编辑自己的
        'post_delete' => ['contributor', 'editor', 'administrator'], // 作者只能删除自己的
        
        // 评论权限
        'comment_view' => ['visitor', 'subscriber', 'contributor', 'editor', 'administrator'],
        'comment_create' => ['subscriber', 'contributor', 'editor', 'administrator'],
        'comment_delete' => ['contributor', 'editor', 'administrator'], // 用户只能删除自己的评论
        
        // 用户管理权限
        'user_manage' => ['administrator'],
        
        // 系统设置权限
        'system_settings' => ['administrator']
    ];
    
    /**
     * 检查用户是否有指定权限
     */
    public static function check(string $permission, ?array $user = null, ?array $resource = null): bool {
        // 获取用户组
        $userGroup = $user ? ($user['group'] ?? 'subscriber') : 'visitor';
        
        // 检查基本权限
        if (!isset(self::RESOURCE_PERMISSIONS[$permission])) {
            return false;
        }
        
        $allowedGroups = self::RESOURCE_PERMISSIONS[$permission];
        if (!in_array($userGroup, $allowedGroups)) {
            return false;
        }
        
        // 特殊权限检查（编辑/删除自己的内容）
        if (in_array($permission, ['post_edit', 'post_delete', 'comment_delete'])) {
            if (!$user || !$resource) {
                return false;
            }
            
            // 作者组只能操作自己的内容
            if ($userGroup === 'contributor') {
                if ($permission === 'post_edit' || $permission === 'post_delete') {
                    return isset($resource['authorId']) && $resource['authorId'] == $user['uid'];
                }
                
                if ($permission === 'comment_delete') {
                    return isset($resource['authorId']) && $resource['authorId'] == $user['uid'];
                }
            }
        }
        
        return true;
    }
    
    /**
     * 要求用户具有指定权限
     */
    public static function require(string $permission, ?array $user = null, ?array $resource = null) {
        if (!self::check($permission, $user, $resource)) {
            Response::json(['error' => 'Forbidden'], 403);
        }
    }
}