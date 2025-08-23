<?php
return [
    // 是否启用鉴权：true 时必须携带 Bearer Token
    'REQUIRE_AUTH' => true,

    // 简易 API Key（Bearer <API_KEY>）- 仅用于向后兼容，生产环境应禁用
    'API_KEY' => '', // 禁用全局API_KEY，仅使用JWT令牌

    // 允许的跨域源（* 表示全开放，生产建议精确到你的域名）
    'CORS_ALLOW_ORIGIN' => '*',

    // 分页默认值
    'PAGINATION' => [
        'perPage' => 10,
        'maxPerPage' => 100,
    ],
    
    // 请求频率限制
    'RATE_LIMIT' => [
        'enabled' => true,
        'requests' => 100,  // 每个时间窗口的请求数
        'window' => 3600    // 时间窗口（秒）
    ],
    
    // JWT配置
    'JWT' => [
        'lifetime' => 86400, // 令牌有效期（秒）
        'issuer' => 'Typecho API', // 令牌发行者
        'audience' => 'Typecho Client' // 令牌受众
    ],
    
    // 安全建议
    'SECURITY_NOTICE' => '生产环境请禁用全局API_KEY，使用用户个人JWT令牌'
];