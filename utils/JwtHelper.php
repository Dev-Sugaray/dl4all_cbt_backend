<?php

// Note: This class requires the firebase/php-jwt library.
// You can install it using Composer: composer require firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper {
    private static $secret_key = 'YOUR_SECRET_KEY'; // Replace with a strong, unique secret key
    private static $algorithm = 'HS256';
    private static $issued_at_time = null;
    private static $not_before_time = null;
    private static $expire_time = null;

    public static function createToken($payload, $expiry_seconds = 3600) {
        self::$issued_at_time = time();
        self::$not_before_time = self::$issued_at_time;
        self::$expire_time = self::$issued_at_time + $expiry_seconds; // Default expiry: 1 hour

        $token_payload = array_merge($payload, [
            'iat' => self::$issued_at_time, // Issued at: time when the token was issued
            'nbf' => self::$not_before_time, // Not before: time before which the token must not be accepted for processing
            'exp' => self::$expire_time,     // Expiration time: time after which the token must not be accepted for processing
        ]);

        try {
            $jwt = JWT::encode($token_payload, self::$secret_key, self::$algorithm);
            return $jwt;
        } catch (Exception $e) {
            error_log("Error creating JWT: " . $e->getMessage());
            return false;
        }
    }

    public static function verifyToken($token) {
        if (empty($token)) {
            return false;
        }

        try {
            $decoded = JWT::decode($token, new Key(self::$secret_key, self::$algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            // Log specific JWT errors for debugging
            error_log("Error verifying JWT: " . $e->getMessage());
            return false;
        }
    }

    // Optional: Method to refresh a token (more complex, might involve blacklisting old tokens)
    // public static function refreshToken($token) {
    //     // Implementation depends on refresh token strategy
    // }
}

?>