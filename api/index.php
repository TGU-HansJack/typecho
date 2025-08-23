<?php
// 如果访问的是文档页面
if (isset($_GET['action']) && $_GET['action'] === 'docs') {
    require_once __DIR__ . '/docs.php';
    exit;
}

require_once __DIR__ . '/bootstrap.php';

// 请求频率限制
$identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!RateLimiter::check($identifier, 100, 3600)) { // 每小时最多100次请求
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(429);
    echo json_encode(['error' => 'Too Many Requests', 'message' => 'Rate limit exceeded']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// CORS
Cors::handle($GLOBALS['__API_CONFIG__']);

// 路由与分发
$router = new Router([
    // v1 API 路由
    // 文章
    ['GET',    '#^/api/v1/posts$#',                [PostsApi::class, 'list']],
    ['GET',    '#^/api/v1/posts/(?P<cid>\d+)$#',   [PostsApi::class, 'detail']],
    ['POST',   '#^/api/v1/posts$#',                [PostsApi::class, 'create'], true],
    ['PUT',    '#^/api/v1/posts/(?P<cid>\d+)$#',   [PostsApi::class, 'update'], true],
    ['DELETE', '#^/api/v1/posts/(?P<cid>\d+)$#',   [PostsApi::class, 'delete'], true],

    // 分类
    ['GET',    '#^/api/v1/categories$#',            [CategoriesApi::class, 'list']],
    ['POST',   '#^/api/v1/categories$#',            [CategoriesApi::class, 'create'], true],
    ['PUT',    '#^/api/v1/categories/(?P<mid>\d+)$#', [CategoriesApi::class, 'update'], true],
    ['DELETE', '#^/api/v1/categories/(?P<mid>\d+)$#', [CategoriesApi::class, 'delete'], true],

    // 标签
    ['GET',    '#^/api/v1/tags$#',                  [TagsApi::class, 'list']],
    ['POST',   '#^/api/v1/tags$#',                  [TagsApi::class, 'create'], true],
    ['PUT',    '#^/api/v1/tags/(?P<mid>\d+)$#',     [TagsApi::class, 'update'], true],
    ['DELETE', '#^/api/v1/tags/(?P<mid>\d+)$#',     [TagsApi::class, 'delete'], true],

    // 评论
    ['GET',    '#^/api/v1/comments$#',              [CommentsApi::class, 'list']],
    ['POST',   '#^/api/v1/comments$#',              [CommentsApi::class, 'create'], true],
    ['PUT',    '#^/api/v1/comments/(?P<coid>\d+)$#',[CommentsApi::class, 'update'], true],
    ['DELETE', '#^/api/v1/comments/(?P<coid>\d+)$#',[CommentsApi::class, 'delete'], true],

    // 用户（只读）
    ['GET',    '#^/api/v1/users$#',                 [UsersApi::class, 'list']],
    ['GET',    '#^/api/v1/users/(?P<uid>\d+)$#',    [UsersApi::class, 'detail']],

    // 认证和令牌
    ['POST',   '#^/api/v1/tokens$#',               [TokensApi::class, 'create']],
    ['POST',   '#^/api/v1/tokens/refresh$#',       [TokensApi::class, 'refresh'], true],
    ['DELETE', '#^/api/v1/tokens$#',               [TokensApi::class, 'delete'], true],
    
    // 兼容旧版API路由
    // 文章
    ['GET',    '#^/api/posts$#',                [PostsApi::class, 'list']],
    ['GET',    '#^/api/posts/(?P<cid>\d+)$#',   [PostsApi::class, 'detail']],
    ['POST',   '#^/api/posts$#',                [PostsApi::class, 'create'], true],
    ['PUT',    '#^/api/posts/(?P<cid>\d+)$#',   [PostsApi::class, 'update'], true],
    ['DELETE', '#^/api/posts/(?P<cid>\d+)$#',   [PostsApi::class, 'delete'], true],

    // 分类
    ['GET',    '#^/api/categories$#',            [CategoriesApi::class, 'list']],
    ['POST',   '#^/api/categories$#',            [CategoriesApi::class, 'create'], true],
    ['PUT',    '#^/api/categories/(?P<mid>\d+)$#', [CategoriesApi::class, 'update'], true],
    ['DELETE', '#^/api/categories/(?P<mid>\d+)$#', [CategoriesApi::class, 'delete'], true],

    // 标签
    ['GET',    '#^/api/tags$#',                  [TagsApi::class, 'list']],
    ['POST',   '#^/api/tags$#',                  [TagsApi::class, 'create'], true],
    ['PUT',    '#^/api/tags/(?P<mid>\d+)$#',     [TagsApi::class, 'update'], true],
    ['DELETE', '#^/api/tags/(?P<mid>\d+)$#',     [TagsApi::class, 'delete'], true],

    // 评论
    ['GET',    '#^/api/comments$#',              [CommentsApi::class, 'list']],
    ['POST',   '#^/api/comments$#',              [CommentsApi::class, 'create'], true],
    ['PUT',    '#^/api/comments/(?P<coid>\d+)$#',[CommentsApi::class, 'update'], true],
    ['DELETE', '#^/api/comments/(?P<coid>\d+)$#',[CommentsApi::class, 'delete'], true],

    // 用户（只读）
    ['GET',    '#^/api/users$#',                 [UsersApi::class, 'list']],
    ['GET',    '#^/api/users/(?P<uid>\d+)$#',    [UsersApi::class, 'detail']],
]);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));