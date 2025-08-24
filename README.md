# Typecho RESTful API

Typecho博客平台的RESTful API，提供现代化的接口用于访问和管理博客内容。

## 目录

- [API版本](#api版本)
- [配置说明](#配置说明)
- [认证方式](#认证方式)
- [权限控制](#权限控制)
- [请求频率限制](#请求频率限制)
- [API接口列表](#api接口列表)
  - [认证](#认证)
  - [文章](#文章)
  - [分类](#分类)
  - [标签](#标签)
  - [评论](#评论)
  - [用户](#用户)
- [错误处理](#错误处理)
- [测试方法](#测试方法)

## API版本

当前支持的API版本：

- **v1** - 当前版本，推荐使用

## 配置说明

API配置在 `api/config.php` 文件中：

```json
{
  "REQUIRE_AUTH": true,
  "API_KEY": "change_this_to_a_strong_secret",
  "CORS_ALLOW_ORIGIN": "*",
  "PAGINATION": {
    "perPage": 10,
    "maxPerPage": 100
  },
  "RATE_LIMIT": {
    "enabled": true,
    "requests": 100,
    "window": 3600
  },
  "JWT": {
    "lifetime": 86400
  }
}
```

## 认证方式

### 1. API Key（向后兼容）

对于需要认证的接口，可以在请求头中添加：

```
Authorization: Bearer your_api_key_here
```

### 2. JWT令牌（推荐）

通过登录接口获取JWT令牌，然后在请求头中添加：

```
Authorization: Bearer your_jwt_token_here
```

## 权限控制

| 资源类型 | 游客 | 注册用户 | 作者 | 编辑 | 管理员 |
|---------|------|---------|------|------|--------|
| 文章查看 | ✓ | ✓ | ✓ | ✓ | ✓ |
| 文章创建 | ✗ | ✓ | ✓ | ✓ | ✓ |
| 文章编辑 | ✗ | 自己 | 自己 | ✓ | ✓ |
| 文章删除 | ✗ | 自己 | 自己 | ✓ | ✓ |
| 评论查看 | ✓ | ✓ | ✓ | ✓ | ✓ |
| 评论创建 | ✗ | ✓ | ✓ | ✓ | ✓ |
| 评论删除 | ✗ | 自己 | 自己 | ✓ | ✓ |
| 用户管理 | ✗ | ✗ | ✗ | ✗ | ✓ |
| 系统设置 | ✗ | ✗ | ✗ | ✗ | ✓ |

## 请求频率限制

默认情况下，每个IP每小时最多允许100次API请求。超过限制将返回429状态码。

## API接口列表

### 认证

#### POST 用户登录获取令牌
```
POST /api/v1/tokens
```

请求体示例：
```json
{
  "name": "username_or_email",
  "password": "user_password"
}
```

响应示例：
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires": 86400
}
```

#### POST 刷新令牌
```
POST /api/v1/tokens/refresh
```
需要认证

响应示例：
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires": 86400
}
```

#### DELETE 注销令牌
```
DELETE /api/v1/tokens
```
需要认证

响应示例：
```json
{
  "success": true
}
```

### 文章 (Posts)

#### GET 获取文章列表
```
GET /api/v1/posts
```

查询参数：
| 参数 | 说明 |
|------|------|
| page | 页码，默认为1 |
| per_page | 每页数量，默认10，最大100 |
| status | 文章状态，如publish、draft等 |
| q | 搜索关键词 |

响应示例：
```json
{
  "data": [
    {
      "cid": "1",
      "title": "文章标题",
      "slug": "post-title",
      "created": "1609459200",
      "modified": "1609459200",
      "text": "文章内容",
      "order": "0",
      "authorId": "1",
      "template": null,
      "type": "post",
      "status": "publish",
      "password": null,
      "commentsNum": "0",
      "allowComment": "1",
      "allowPing": "1",
      "allowFeed": "1",
      "parent": "0"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "total_pages": 1
  }
}
```

#### GET 获取文章详情
```
GET /api/v1/posts/{cid}
```

响应示例：
```json
{
  "cid": "1",
  "title": "文章标题",
  "slug": "post-title",
  "created": "1609459200",
  "modified": "1609459200",
  "text": "文章内容",
  "order": "0",
  "authorId": "1",
  "template": null,
  "type": "post",
  "status": "publish",
  "password": null,
  "commentsNum": "0",
  "allowComment": "1",
  "allowPing": "1",
  "allowFeed": "1",
  "parent": "0",
  "metas": [
    {
      "mid": "1",
      "name": "分类名称",
      "slug": "category-name",
      "type": "category",
      "description": "分类描述",
      "count": "1",
      "order": "0",
      "parent": "0"
    }
  ],
  "commentsCount": 0
}
```

#### POST 创建文章
```
POST /api/v1/posts
```
需要认证

请求体示例：
```json
{
  "title": "文章标题",
  "text": "文章内容",
  "status": "publish",
  "slug": "post-slug",
  "authorId": 1,
  "categories": ["分类1", "分类2"],
  "tags": ["标签1", "标签2"]
}
```

响应示例：
```json
{
  "success": true,
  "cid": 2
}
```

#### PUT 更新文章
```
PUT /api/v1/posts/{cid}
```
需要认证

请求体示例：
```json
{
  "title": "更新后的文章标题",
  "text": "更新后的文章内容"
}
```

响应示例：
```json
{
  "success": true
}
```

#### DELETE 删除文章
```
DELETE /api/v1/posts/{cid}
```
需要认证

响应示例：
```json
{
  "success": true
}
```

### 分类 (Categories)

#### GET 获取分类列表
```
GET /api/v1/categories
```

响应示例：
```json
[
  {
    "mid": "1",
    "name": "默认分类",
    "slug": "default",
    "type": "category",
    "description": "默认分类描述",
    "count": "1",
    "order": "0",
    "parent": "0"
  }
]
```

#### POST 创建分类
```
POST /api/v1/categories
```
需要认证

请求体示例：
```json
{
  "name": "新分类",
  "slug": "new-category",
  "description": "分类描述"
}
```

响应示例：
```json
{
  "success": true,
  "mid": 2
}
```

#### PUT 更新分类
```
PUT /api/v1/categories/{mid}
```
需要认证

请求体示例：
```json
{
  "name": "更新后的分类名称",
  "description": "更新后的分类描述"
}
```

响应示例：
```json
{
  "success": true
}
```

#### DELETE 删除分类
```
DELETE /api/v1/categories/{mid}
```
需要认证

响应示例：
```json
{
  "success": true
}
```

### 标签 (Tags)

#### GET 获取标签列表
```
GET /api/v1/tags
```

响应示例：
```json
[
  {
    "mid": "1",
    "name": "默认标签",
    "slug": "default-tag",
    "type": "tag",
    "description": "",
    "count": "1",
    "order": "0",
    "parent": "0"
  }
]
```

#### POST 创建标签
```
POST /api/v1/tags
```
需要认证

请求体示例：
```json
{
  "name": "新标签",
  "slug": "new-tag"
}
```

响应示例：
```json
{
  "success": true,
  "mid": 2
}
```

#### PUT 更新标签
```
PUT /api/v1/tags/{mid}
```
需要认证

请求体示例：
```json
{
  "name": "更新后的标签名称"
}
```

响应示例：
```json
{
  "success": true
}
```

#### DELETE 删除标签
```
DELETE /api/v1/tags/{mid}
```
需要认证

响应示例：
```json
{
  "success": true
}
```

### 评论 (Comments)

#### GET 获取评论列表
```
GET /api/v1/comments
```

查询参数：
| 参数 | 说明 |
|------|------|
| page | 页码，默认为1 |
| per_page | 每页数量，默认10，最大100 |
| status | 评论状态，如approved、waiting等 |
| cid | 文章ID，筛选指定文章的评论 |

响应示例：
```json
{
  "data": [
    {
      "coid": "1",
      "cid": "1",
      "created": "1609459200",
      "author": "评论作者",
      "authorId": "0",
      "ownerId": "1",
      "mail": "author@example.com",
      "url": "http://example.com",
      "ip": "127.0.0.1",
      "agent": "Mozilla/5.0...",
      "text": "评论内容",
      "type": "comment",
      "status": "approved",
      "parent": "0"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "total_pages": 1
  }
}
```

#### POST 创建评论
```
POST /api/v1/comments
```
需要认证

请求体示例：
```json
{
  "cid": 1,
  "author": "评论作者",
  "mail": "author@example.com",
  "url": "http://example.com",
  "text": "评论内容"
}
```

响应示例：
```json
{
  "success": true,
  "coid": 2
}
```

#### PUT 更新评论
```
PUT /api/v1/comments/{coid}
```
需要认证

请求体示例：
```json
{
  "text": "更新后的评论内容"
}
```

响应示例：
```json
{
  "success": true
}
```

#### DELETE 删除评论
```
DELETE /api/v1/comments/{coid}
```
需要认证

响应示例：
```json
{
  "success": true
}
```

### 用户 (Users)

#### GET 获取用户列表
```
GET /api/v1/users
```

响应示例：
```json
{
  "data": [
    {
      "uid": "1",
      "name": "admin",
      "password": "加密后的密码",
      "mail": "admin@example.com",
      "url": "http://example.com",
      "screenName": "管理员",
      "created": "1609459200",
      "activated": "1609459200",
      "logged": "1609459200",
      "group": "administrator",
      "authCode": "认证码"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "total_pages": 1
  }
}
```

#### GET 获取用户详情
```
GET /api/v1/users/{uid}
```

响应示例：
```json
{
  "uid": "1",
  "name": "admin",
  "password": "加密后的密码",
  "mail": "admin@example.com",
  "url": "http://example.com",
  "screenName": "管理员",
  "created": "1609459200",
  "activated": "1609459200",
  "logged": "1609459200",
  "group": "administrator",
  "authCode": "认证码"
}
```

## 错误处理

API使用标准HTTP状态码来表示请求结果：

| 状态码 | 说明 |
|-------|------|
| 200 | 请求成功 |
| 201 | 创建成功 |
| 400 | 请求参数错误 |
| 401 | 未认证 |
| 403 | 权限不足 |
| 404 | 资源不存在 |
| 429 | 请求过于频繁 |
| 500 | 服务器内部错误 |

错误响应格式：
```json
{
  "error": "错误类型",
  "message": "错误详细信息"
}
```

## 测试方法

### 使用curl测试

1. 获取文章列表：
```bash
curl -X GET http://your-typecho-site.com/api/v1/posts
```

2. 用户登录：
```bash
curl -X POST http://your-typecho-site.com/api/v1/tokens \
  -H "Content-Type: application/json" \
  -d '{"name":"username","password":"password"}'
```

3. 创建文章（需要认证）：
```bash
curl -X POST http://your-typecho-site.com/api/v1/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your_jwt_token" \
  -d '{"title":"新文章","text":"文章内容","status":"publish"}'
```

4. 删除文章（需要认证）：
```bash
curl -X DELETE http://your-typecho-site.com/api/v1/posts/1 \
  -H "Authorization: Bearer your_jwt_token"
```

### 使用Postman测试

1. 下载并安装Postman
2. 创建新请求
3. 设置请求方法（GET/POST/PUT/DELETE）
4. 输入请求URL
5. 在Headers中添加认证信息（如需要）
6. 在Body中添加请求体内容（POST/PUT请求）
7. 点击Send按钮发送请求

### 响应验证

所有成功的API响应都遵循以下格式：
- 对于列表请求，返回包含data和pagination的对象
- 对于单个资源请求，直接返回资源对象
- 对于创建操作，返回包含success和新创建资源ID的对象
- 对于更新和删除操作，返回包含success的对象

错误响应始终包含error和message字段，便于调试和错误处理。