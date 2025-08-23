<?php
require_once __DIR__ . '/../helpers.php';

class Auth {
    const TOKEN_LIFETIME = 86400; // 24小时
    
    public static function check(): bool {
        $cfg = $GLOBALS['__API_CONFIG__'];
        if (empty($cfg['REQUIRE_AUTH'])) return true;

        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($auth, 'Bearer ') !== 0) return false;
        $token = trim(substr($auth, 7));
        
        // 首先检查是否为API密钥（向后兼容）
        if ($token === $cfg['API_KEY'] && $cfg['API_KEY'] !== 'disabled_for_security' && !empty($cfg['API_KEY'])) {
            return true;
        }
        
        // 检查是否为JWT令牌
        return self::validateToken($token);
    }
    
    public static function require() {
        if (!self::check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
    }
    
    public static function getUser(): ?array {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($auth, 'Bearer ') !== 0) return null;
        $token = trim(substr($auth, 7));
        
        // 检查是否为JWT令牌
        $payload = self::decodeToken($token);
        if ($payload && isset($payload['uid'])) {
            $d = db();
            return $d->fetchRow($d->select()->from('table.users')->where('uid = ?', $payload['uid'])->limit(1));
        }
        
        return null;
    }
    
    public static function generateToken(int $uid): string {
        // 获取用户信息
        $d = db();
        $user = $d->fetchRow($d->select()->from('table.users')->where('uid = ?', $uid)->limit(1));
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        $cfg = $GLOBALS['__API_CONFIG__'];
        $lifetime = $cfg['JWT']['lifetime'] ?? self::TOKEN_LIFETIME;
        
        // 使用用户特定的密钥而不是全局API_KEY
        $secretKey = $user['authCode'] ?? md5($user['uid'] . $user['name']);
        
        $header = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode([
            'uid' => $uid,
            'name' => $user['name'],
            'screenName' => $user['screenName'],
            'exp' => time() + $lifetime,
            'iat' => time(),
            'iss' => $cfg['JWT']['issuer'] ?? 'Typecho API',
            'aud' => $cfg['JWT']['audience'] ?? 'Typecho Client'
        ])), '+/', '-_'), '=');
        
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header . '.' . $payload, $secretKey, true)), '+/', '-_'), '=');
        
        return $header . '.' . $payload . '.' . $signature;
    }
    
    private static function validateToken(string $token): bool {
        $payload = self::decodeToken($token);
        if (!$payload) {
            return false;
        }
        
        // 检查令牌是否过期
        if (isset($payload['exp']) && $payload['exp'] <= time()) {
            return false;
        }
        
        // 检查令牌是否在黑名单中（已被删除）
        if (self::isTokenBlacklisted($token)) {
            return false;
        }
        
        // 可以添加更多验证逻辑，如检查发行者、受众等
        $cfg = $GLOBALS['__API_CONFIG__'];
        if (isset($cfg['JWT']['issuer']) && (!isset($payload['iss']) || $payload['iss'] !== $cfg['JWT']['issuer'])) {
            return false;
        }
        
        if (isset($cfg['JWT']['audience']) && (!isset($payload['aud']) || $payload['aud'] !== $cfg['JWT']['audience'])) {
            return false;
        }
        
        return true;
    }
    
    private static function decodeToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        
        [$header, $payload, $signature] = $parts;
        
        // 获取载荷数据
        $payloadData = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        if (!isset($payloadData['uid'])) {
            return null;
        }
        
        // 获取用户信息
        $d = db();
        $user = $d->fetchRow($d->select()->from('table.users')->where('uid = ?', $payloadData['uid'])->limit(1));
        if (!$user) {
            return null;
        }
        
        // 使用用户特定的密钥验证签名
        $secretKey = $user['authCode'] ?? md5($user['uid'] . $user['name']);
        $expectedSignature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header . '.' . $payload, $secretKey, true)), '+/', '-_'), '=');
        if (!hash_equals($signature, $expectedSignature)) return null;
        
        return is_array($payloadData) ? $payloadData : null;
    }
    
    /**
     * 检查令牌是否在黑名单中（已被删除）
     * 
     * @param string $token JWT令牌
     * @return bool
     */
    private static function isTokenBlacklisted(string $token): bool {
        $d = db();
        $record = $d->fetchRow($d->select()->from('table.user_tokens')->where('token = ?', $token));
        return !$record; // 如果在数据库中找不到该令牌，则认为已被删除（在黑名单中）
    }
}