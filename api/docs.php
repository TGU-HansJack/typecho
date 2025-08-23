<?php
/**
 * Typecho RESTful API 文档
 */

// 引导 Typecho 核心
require_once __DIR__ . '/bootstrap.php';

// 获取基础URL
$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

$config_sample = [
    'REQUIRE_AUTH' => true,
    'API_KEY' => 'change_this_to_a_strong_secret',
    'CORS_ALLOW_ORIGIN' => '*',
    'PAGINATION' => [
        'perPage' => 10,
        'maxPerPage' => 100,
    ],
    'RATE_LIMIT' => [
        'enabled' => true,
        'requests' => 100,
        'window' => 3600
    ],
    'JWT' => [
        'lifetime' => 86400
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Typecho RESTful API Documentation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #333; }
        code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .endpoint { margin: 20px 0; padding: 15px; border-left: 4px solid #007cba; background: #f9f9f9; }
        .method { display: inline-block; padding: 2px 8px; border-radius: 3px; color: white; font-weight: bold; }
        .get { background: #28a745; }
        .post { background: #007cba; }
        .put { background: #ffc107; color: black; }
        .delete { background: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        .permission-table { font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Typecho RESTful API Documentation</h1>
        
        <h2>API版本</h2>
        <p>当前支持的API版本：</p>
        <ul>
            <li><strong>v1</strong> - 当前版本，推荐使用</li>
        </ul>
        
        <h2>配置说明</h2>
        <p>API配置在 <code>api/config.php</code> 文件中：</p>
        <pre><?php echo htmlspecialchars(json_encode($config_sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        
        <h2>认证方式</h2>
        <h3>1. API Key（向后兼容）</h3>
        <p>对于需要认证的接口，可以在请求头中添加：</p>
        <pre>Authorization: Bearer your_api_key_here</pre>
        
        <h3>2. JWT令牌（推荐）</h3>
        <p>通过登录接口获取JWT令牌，然后在请求头中添加：</p>
        <pre>Authorization: Bearer your_jwt_token_here</pre>
        
        <h2>权限控制</h2>
        <table class="permission-table">
            <thead>
                <tr>
                    <th>资源类型</th>
                    <th>游客</th>
                    <th>注册用户</th>
                    <th>作者</th>
                    <th>编辑</th>
                    <th>管理员</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>文章查看</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>文章创建</td>
                    <td>✗</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>文章编辑</td>
                    <td>✗</td>
                    <td>自己</td>
                    <td>自己</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>文章删除</td>
                    <td>✗</td>
                    <td>自己</td>
                    <td>自己</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>评论查看</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>评论创建</td>
                    <td>✗</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>评论删除</td>
                    <td>✗</td>
                    <td>自己</td>
                    <td>自己</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>用户管理</td>
                    <td>✗</td>
                    <td>✗</td>
                    <td>✗</td>
                    <td>✗</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>系统设置</td>
                    <td>✗</td>
                    <td>✗</td>
                    <td>✗</td>
                    <td>✗</td>
                    <td>✓</td>
                </tr>
            </tbody>
        </table>
        
        <h2>请求频率限制</h2>
        <p>默认情况下，每个IP每小时最多允许100次API请求。超过限制将返回429状态码。</p>
        
        <h2>API 接口列表 (v1版本)</h2>
        
        <div class="endpoint">
            <h3>认证</h3>
            
            <h4><span class="method post">POST</span> 用户登录获取令牌</h4>
            <p><code>POST <?php echo $base_url; ?>/v1/tokens</code></p>
            <p>请求体示例：</p>
            <pre>{
  "name": "username_or_email",
  "password": "user_password"
}</pre>
            
            <h4><span class="method post">POST</span> 刷新令牌</h4>
            <p><code>POST <?php echo $base_url; ?>/v1/tokens/refresh</code> (需要认证)</p>
            
            <h4><span class="method delete">DELETE</span> 注销令牌</h4>
            <p><code>DELETE <?php echo $base_url; ?>/v1/tokens</code> (需要认证)</p>
        </div>
        
        <div class="endpoint">
            <h3>文章 (Posts)</h3>
            
            <h4><span class="method get">GET</span> 获取文章列表</h4>
            <p><code>GET <?php echo $base_url; ?>/v1/posts</code></p>
            <p>查询参数：</p>
            <table>
                <tr><th>参数</th><th>说明</th></tr>
                <tr><td>page</td><td>页码，默认为1</td></tr>
                <tr><td>per_page</td><td>每页数量，默认10，最大100</td></tr>
                <tr><td>status</td><td>文章状态，如publish、draft等</td></tr>
                <tr><td>q</td><td>搜索关键词</td></tr>
            </table>
            
            <h4><span class="method get">GET</span> 获取文章详情</h4>
            <p><code>GET <?php echo $base_url; ?>/v1/posts/{cid}</code></p>
            
            <h4><span class="method post">POST</span> 创建文章</h4>
            <p><code>POST <?php echo $base_url; ?>/v1/posts</code> (需要认证)</p>
            <p>请求体示例：</p>
            <pre>{
  "title": "文章标题",
  "text": "文章内容",
  "status": "publish",
  "slug": "post-slug",
  "authorId": 1,
  "categories": ["分类1", "分类2"],
  "tags": ["标签1", "标签2"]
}</pre>
            
            <h4><span class="method put">PUT</span> 更新文章</h4>
            <p><code>PUT <?php echo $base_url; ?>/v1/posts/{cid}</code> (需要认证)</p>
            
            <h4><span class="method delete">DELETE</span> 删除文章</h4>
            <p><code>DELETE <?php echo $base_url; ?>/v1/posts/{cid}</code> (需要认证)</p>
        </div>
        
        <div class="endpoint">
            <h3>分类 (Categories)</h3>
            
            <h4><span class="method get">GET</span> 获取分类列表</h4>
            <p><code>GET <?php echo $base_url; ?>/v1/categories</code></p>
            
            <h4><span class="method post">POST</span> 创建分类</h4>
            <p><code>POST <?php echo $base_url; ?>/v1/categories</code> (需要认证)</p>
            
            <h4><span class="method put">PUT</span> 更新分类</h4>
            <p><code>PUT <?php echo $base_url; ?>/v1/categories/{mid}</code> (需要认证)</p>
            
            <h4><span class="method delete">DELETE</span> 删除分类</h4>
            <p><code>DELETE <?php echo $base_url; ?>/v1/categories/{mid}</code> (需要认证)</p>
        </div>
        
        <div class="endpoint">
            <h3>标签 (Tags)</h3>
            
            <h4><span class="method get">GET</span> 获取标签列表</h4>
            <p><code>GET <?php echo $base_url; ?>/v1/tags</code></p>
            
            <h4><span class="method post">POST</span> 创建标签</h4>
            <p><code>POST <?php echo $base_url; ?>/v1/tags</code> (需要认证)</p>
            
            <h4><span class="method put">PUT</span> 更新标签</h4>
            <p><code>PUT <?php echo $base_url; ?>/v1/tags/{mid}</code> (需要认证)</p>
            
            <h4><span class="method delete">DELETE</span> 删除标签</h4>
            <p><code>DELETE <?php echo $base_url; ?>/v1/tags/{mid}</code> (需要认证)</p>
        </div>
        
        <div class="endpoint">
            <h3>评论 (Comments)</h3>
            
            <h4><span class="method get">GET</span> 获取评论列表</h4>
            <p><code>GET <?php echo $base_url; ?>/v1/comments</code></p>
            <p>查询参数：</p>
            <table>
                <tr><th>参数</th><th>说明</th></tr>
                <tr><td>page</td><td>页码，默认为1</td></tr>
                <tr><td>per_page</td><td>每页数量，默认10，最大100</td></tr>
                <tr><td>status</td><td>评论状态，如approved、waiting等</td></tr>
                <tr><td>cid</td><td>文章ID，筛选指定文章的评论</td></tr>
            </table>
            
            <h4><span class="method post">POST</span> 创建评论</h4>
            <p><code>POST <?php echo $base_url; ?>/v1/comments</code> (需要认证)</p>
            
            <h4><span class="method put">PUT</span> 更新评论</h4>
            <p><code>PUT <?php echo $base_url; ?>/v1/comments/{coid}</code> (需要认证)</p>
            
            <h4><span class="method delete">DELETE</span> 删除评论</h4>
            <p><code>DELETE <?php echo $base_url; ?>/v1/comments/{coid}</code> (需要认证)</p>
        </div>
        
        <div class="endpoint">
            <h3>用户 (Users)</h3>
            
            <h4><span class="method get">GET</span> 获取用户列表</h4>
            <p><code>GET <?php echo $base_url; ?>/v1/users</code></p>
            
            <h4><span class="method get">GET</span> 获取用户详情</h4>
            <p><code>GET <?php echo $base_url; ?>/v1/users/{uid}</code></p>
        </div>
    </div>
</body>
</html>