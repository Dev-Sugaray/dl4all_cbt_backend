<?php

namespace App\GraphQL\Resolvers;

use App\Models\User;
use App\Config\JWTConfig;
use GraphQL\Error\Error;

class UserResolver {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login($_, array $args) {
        $input = $args['input'];
        $user = $this->userModel->findByEmail($input['email']);

        if (!$user || !password_verify($input['password'], $user['hashed_password'])) {
            throw new Error('Invalid email or password.');
        }

        if (!$user['is_active']) {
            throw new Error('User account is not active.');
        }

        $this->userModel->updateLastLogin($user['user_id']);

        $token = JWTConfig::generateToken($user['user_id'], $user['user_role'], $user['email']);
        $refreshToken = JWTConfig::generateRefreshToken($user['user_id']);

        return [
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => $user,
        ];
    }

    public function register($_, array $args) {
        $input = $args['input'];
        
        // Validate input (basic example, add more comprehensive validation)
        if (empty($input['email']) || empty($input['password']) || empty($input['fullName']) || empty($input['userRole'])) {
            throw new Error('Email, password, full name, and user role are required.');
        }

        if ($this->userModel->findByEmail($input['email'])) {
            throw new Error('Email already exists.');
        }

        $user = $this->userModel->create($input);

        if (!$user) {
            throw new Error('Failed to create user.');
        }
        
        // TODO: Send email verification link using $user['email_verification_token']

        $token = JWTConfig::generateToken($user['user_id'], $user['user_role'], $user['email']);
        $refreshToken = JWTConfig::generateRefreshToken($user['user_id']);

        return [
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => $user,
        ];
    }

    public function refreshToken($_, array $args) {
        $refreshToken = $args['refreshToken'];
        try {
            $decoded = JWTConfig::validateToken($refreshToken);
            if ($decoded->type !== 'refresh') {
                throw new Error('Invalid refresh token.');
            }

            $user = $this->userModel->findById($decoded->user_id);
            if (!$user || !$user['is_active']) {
                throw new Error('User not found or inactive.');
            }

            $newAccessToken = JWTConfig::generateToken($user['user_id'], $user['user_role'], $user['email']);
            $newRefreshToken = JWTConfig::generateRefreshToken($user['user_id']); // Optionally issue a new refresh token

            return [
                'token' => $newAccessToken,
                'refreshToken' => $newRefreshToken,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }

    public function requestPasswordReset($_, array $args) {
        $email = $args['email'];
        $user = $this->userModel->findByEmail($email);

        if ($user) {
            $resetToken = bin2hex(random_bytes(32));
            $this->userModel->updatePasswordResetToken($user['user_id'], $resetToken);
            // TODO: Send password reset email with $resetToken
        }
        // Always return success to prevent email enumeration
        return ['success' => true, 'message' => 'If an account with that email exists, a password reset link has been sent.'];
    }

    public function resetPassword($_, array $args) {
        $token = $args['token'];
        $newPassword = $args['newPassword'];

        $user = $this->userModel->findByPasswordResetToken($token);

        if (!$user) {
            throw new Error('Invalid or expired password reset token.');
        }

        if (strlen($newPassword) < 8) { // Basic password policy
            throw new Error('Password must be at least 8 characters long.');
        }

        if ($this->userModel->updatePassword($user['user_id'], $newPassword)) {
            return ['success' => true, 'message' => 'Password has been reset successfully.'];
        }

        throw new Error('Failed to reset password.');
    }

    public function me($_, array $args, $context) {
        if (empty($context['user'])) {
            throw new Error('Not authenticated.');
        }
        return $this->userModel->findById($context['user']['user_id']);
    }

    public function getUser($_, array $args, $context) {
        // Add authorization: only admin or the user themselves can fetch user details
        if (empty($context['user']) || ($context['user']['user_role'] !== 'administrator' && $context['user']['user_id'] != $args['userId'])) {
            throw new Error('Not authorized to view this user.');
        }
        return $this->userModel->findById($args['userId']);
    }

    public function getUsers($_, array $args, $context) {
        // Add authorization: only admin can list users
        if (empty($context['user']) || $context['user']['user_role'] !== 'administrator') {
            throw new Error('Not authorized to list users.');
        }
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        return $this->userModel->getAllUsers($limit, $offset);
    }

    public function updateUserProfile($_, array $args, $context) {
        if (empty($context['user'])) {
            throw new Error('Not authenticated.');
        }
        // Authorization: User can update their own profile, or admin can update any profile
        if ($context['user']['user_role'] !== 'administrator' && $context['user']['user_id'] != $args['userId']) {
            throw new Error('Not authorized to update this profile.');
        }

        $updateData = [];
        if (isset($args['fullName'])) $updateData['fullName'] = $args['fullName'];
        if (isset($args['dateOfBirth'])) $updateData['dateOfBirth'] = $args['dateOfBirth'];
        if (isset($args['gender'])) $updateData['gender'] = $args['gender'];
        if (isset($args['institution'])) $updateData['institution'] = $args['institution'];
        if (isset($args['studyLevel'])) $updateData['studyLevel'] = $args['studyLevel'];
        if (isset($args['preferredExamTypes'])) $updateData['preferredExamTypes'] = $args['preferredExamTypes'];

        $updatedUser = $this->userModel->updateUserProfile($args['userId'], $updateData);
        if (!$updatedUser) {
            throw new Error('Failed to update user profile.');
        }
        return $updatedUser;
    }

    public function updateUserStatus($_, array $args, $context) {
        if (empty($context['user']) || $context['user']['user_role'] !== 'administrator') {
            throw new Error('Not authorized to update user status.');
        }

        $updatedUser = $this->userModel->updateUserStatus($args['userId'], $args['isActive']);
        if (!$updatedUser) {
            throw new Error('Failed to update user status.');
        }
        return $updatedUser;
    }
}