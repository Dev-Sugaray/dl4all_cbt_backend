<?php

require_once APP_ROOT . '/utils/JwtHelper.php'; // Include JWT helper

class AuthMiddleware {
    public static function handle() {
        // Get the Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Check if the header is in the Bearer token format
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            // No token found or invalid format
            ResponseHelper::send(401, ['error' => 'Unauthorized: Bearer token required.']);
            exit();
        }

        $token = $matches[1];

        // Validate the token using JwtHelper
        $userData = JwtHelper::verifyToken($token);

        if (!$userData) {
            // Invalid or expired token
            ResponseHelper::send(401, ['error' => 'Unauthorized: Invalid or expired token.']);
            exit();
        }

        // Token is valid, return user data
        return $userData;
    }
}

?>