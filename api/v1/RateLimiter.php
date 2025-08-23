<?php
require_once __DIR__ . '/../helpers.php';

class RateLimiter {
    private static $limits = [];
    
    const DEFAULT_LIMIT = 100; // 默认每小时请求数
    const DEFAULT_WINDOW = 3600; // 默认时间窗口（秒）
    
    /**
     * 检查是否超出请求限制
     */
    public static function check(string $identifier, int $limit = self::DEFAULT_LIMIT, int $window = self::DEFAULT_WINDOW): bool {
        $key = self::getKey($identifier);
        $currentTime = time();
        
        // 清除过期记录
        self::cleanup($key, $currentTime, $window);
        
        // 获取当前请求数
        $requests = self::$limits[$key] ?? [];
        
        // 检查是否超出限制
        if (count($requests) >= $limit) {
            return false;
        }
        
        // 记录当前请求
        $requests[] = $currentTime;
        self::$limits[$key] = $requests;
        
        return true;
    }
    
    /**
     * 获取剩余请求数
     */
    public static function getRemaining(string $identifier, int $limit = self::DEFAULT_LIMIT, int $window = self::DEFAULT_WINDOW): int {
        $key = self::getKey($identifier);
        self::cleanup($key, time(), $window);
        $requests = self::$limits[$key] ?? [];
        return max(0, $limit - count($requests));
    }
    
    /**
     * 生成键名
     */
    private static function getKey(string $identifier): string {
        return 'rate_limit_' . md5($identifier);
    }
    
    /**
     * 清除过期记录
     */
    private static function cleanup(string $key, int $currentTime, int $window) {
        if (!isset(self::$limits[$key])) {
            return;
        }
        
        $requests = self::$limits[$key];
        $validRequests = [];
        
        foreach ($requests as $requestTime) {
            if ($currentTime - $requestTime < $window) {
                $validRequests[] = $requestTime;
            }
        }
        
        self::$limits[$key] = $validRequests;
    }
}