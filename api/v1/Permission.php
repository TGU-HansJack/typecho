<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/Auth.php';

class Permission {
    // 用户组权限映射 (数字越小权限越高)
    const PERMISSION_MAP = [
        'administrator' => 0, // 管理员
        'editor' => 1,        // 编辑
        'contributor' => 2,   // 贡献者
        'subscriber' => 3,    // 关注者
        'visitor' => 4        // 访问者
    ];
    
    // 资源权限定义
    const RESOURCE_PERMISSIONS = [
        // 文章权限
        'post_view' => ['administrator', 'editor', 'contributor', 'subscriber', 'visitor'],
        'post_create' => ['administrator', 'editor', 'contributor'],
        'post_edit' => ['administrator', 'editor', 'contributor'], // 作者只能编辑自己的
        'post_delete' => ['administrator', 'editor', 'contributor'], // 作者只能删除自己的
        
        // 页面权限
        'page_view' => ['administrator', 'editor', 'contributor', 'subscriber', 'visitor'],
        'page_create' => ['administrator', 'editor'],
        'page_edit' => ['administrator', 'editor'],
        'page_delete' => ['administrator', 'editor'],
        
        // 上传文件权限
        'upload_file' => ['administrator', 'editor', 'contributor'], // 贡献者受限
        'manage_file' => ['administrator', 'editor', 'contributor'], // 贡献者仅能管理自己的文件
        
        // 评论权限
        'comment_view' => ['administrator', 'editor', 'contributor', 'subscriber', 'visitor'],
        'comment_create' => ['administrator', 'editor', 'contributor', 'subscriber'],
        'comment_edit' => ['administrator', 'editor'],
        'comment_delete' => ['administrator', 'editor', 'contributor'], // 用户只能删除自己的评论
        
        // 分类和标签权限
        'manage_metas' => ['administrator', 'editor'],
        
        // 用户管理权限
        'user_view' => ['administrator', 'editor', 'contributor', 'subscriber'],
        'user_create' => ['administrator'],
        'user_edit' => ['administrator'],
        'user_delete' => ['administrator'],
        
        // 插件管理权限
        'manage_plugins' => ['administrator'],
        
        // 外观设置权限
        'manage_themes' => ['administrator'],
        
        // 系统设置权限
        'system_settings' => ['administrator'],
        
        // 基本设置权限
        'general_settings' => ['administrator'],
        
        // 评论设置权限
        'discussion_settings' => ['administrator'],
        
        // 阅读设置权限
        'reading_settings' => ['administrator'],
        
        // 撰写习惯设置权限
        'writing_settings' => ['administrator', 'editor', 'contributor'],
        
        // 进入控制台权限
        'access_dashboard' => ['administrator', 'editor', 'contributor', 'subscriber'],
        
        // 修改自己的档案信息权限
        'edit_profile' => ['administrator', 'editor', 'contributor', 'subscriber']
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
        if (in_array($permission, ['post_edit', 'post_delete', 'comment_delete', 'manage_file'])) {
            if (!$user || !$resource) {
                return false;
            }
            
            // 贡献者组只能操作自己的内容
            if ($userGroup === 'contributor') {
                if ($permission === 'post_edit' || $permission === 'post_delete') {
                    return isset($resource['authorId']) && $resource['authorId'] == $user['uid'];
                }
                
                if ($permission === 'comment_delete') {
                    return isset($resource['authorId']) && $resource['authorId'] == $user['uid'];
                }
                
                if ($permission === 'manage_file') {
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
    
    /**
     * 检查用户是否具有指定等级或更高等级权限
     * 
     * @param string $group 用户组
     * @param array|null $user 用户信息
     * @return bool
     */
    public static function checkGroup(string $group, ?array $user = null): bool {
        $userGroup = $user ? ($user['group'] ?? 'subscriber') : 'visitor';
        
        // 检查用户组是否存在
        if (!isset(self::PERMISSION_MAP[$userGroup]) || !isset(self::PERMISSION_MAP[$group])) {
            return false;
        }
        
        // 数字越小权限越高
        return self::PERMISSION_MAP[$userGroup] <= self::PERMISSION_MAP[$group];
    }
}