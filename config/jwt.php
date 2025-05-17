<?php

namespace App\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

class JWTConfig {
    private static $secret;
    private static $expiration;
    private static $refreshExpiration;
    
    public static function initialize() {
        self::$secret = getenv('JWT_SECRET') ?: 'default_jwt_secret_key_change_this_in_production';
        self::$expiration = getenv('JWT_EXPIRATION') ? (int)getenv('JWT_EXPIRATION') : 3600; // 1 hour
        self::$refreshExpiration = getenv('JWT_REFRESH_EXPIRATION') ? (int)getenv('JWT_REFRESH_EXPIRATION') : 86400; // 24 hours
    }
    
    public static function generateToken($userId, $userRole, $email) {
        self::initialize();
        
        $issuedAt = time();
        $expirationTime = $issuedAt + self::$expiration;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId,
            'user_role' => $userRole,
            'email' => $email,
            'type' => 'access'
        ];
        
        return JWT::encode($payload, self::$secret, 'HS256');
    }
    
    public static function generateRefreshToken($userId) {
        self::initialize();
        
        $issuedAt = time();
        $expirationTime = $issuedAt + self::$refreshExpiration;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId,
            'type' => 'refresh'
        ];
        
        return JWT::encode($payload, self::$secret, 'HS256');
    }
    
    public static function validateToken($token) {
        self::initialize();
        
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, 'HS256'));
            return $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired', 401);
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Invalid token signature', 401);
        } catch (BeforeValidException $e) {
            throw new \Exception('Token not yet valid', 401);
        } catch (\Exception $e) {
            throw new \Exception('Invalid token', 401);
        }
    }
    
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }
        
        return $matches[1];
    }
    
    public static function getUserFromToken() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }
        
        try {
            $decoded = self::validateToken($token);
            
            if ($decoded->type !== 'access') {
                throw new \Exception('Invalid token type', 401);
            }
            
            return [
                'user_id' => $decoded->user_id,
                'user_role' => $decoded->user_role,
                'email' => $decoded->email
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}