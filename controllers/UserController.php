<?php

require_once APP_ROOT . '/config/database.php'; // Include database connection
require_once APP_ROOT . '/utils/JwtHelper.php'; // Include JWT helper
require_once APP_ROOT . '/utils/ResponseHelper.php'; // Include ResponseHelper

class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle user registration
    public function register($data) {
        // Basic input validation
        if (!isset($data['email'], $data['password'], $data['user_role'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (email, password, user_role).']);
            return;
        }

        $email = $data['email'];
        $password = $data['password'];
        $user_role = $data['user_role'];

        // Validate user role
        $allowed_roles = ['student', 'administrator', 'content_creator'];
        if (!in_array($user_role, $allowed_roles)) {
            ResponseHelper::send(400, ['error' => 'Invalid user role specified.']);
            return;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Prepare and execute the SQL statement to insert the new user
        $sql = "INSERT INTO Users (user_role, email, hashed_password) VALUES (:user_role, :email, :hashed_password)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_role', $user_role);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':hashed_password', $hashed_password);

            if ($stmt->execute()) {
                // Registration successful
                ResponseHelper::send(201, ['message' => 'User registered successfully.', 'user_id' => (int)$this->pdo->lastInsertId()]);
            } else {
                // Handle execution error (less likely with exceptions enabled)
                ResponseHelper::send(500, ['error' => 'User registration failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate email)
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                ResponseHelper::send(409, ['error' => 'Email already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during registration: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle user login
    public function login($data) {
        // Basic input validation
        if (!isset($data['email'], $data['password'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (email, password).']);
            return;
        }

        $email = $data['email'];
        $password = $data['password'];

        // Prepare and execute the SQL statement to retrieve the user by email
        $sql = "SELECT user_id, user_role, hashed_password, is_active FROM Users WHERE email = :email LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $user = $stmt->fetch();

            // Verify user exists and password is correct
            if ($user && password_verify($password, $user['hashed_password'])) {
                // Check if user is active
                if (!$user['is_active']) {
                    ResponseHelper::send(401, ['error' => 'Account is inactive.']);
                    return;
                }

                // Login successful, generate JWT token
                $token_data = [
                    'user_id' => $user['user_id'],
                    'user_role' => $user['user_role']
                ];

                $token = JwtHelper::createToken($token_data);

                // Return token and user info
                ResponseHelper::send(200, [
                    'message' => 'Login successful.',
                    'token' => $token,
                    'user' => [
                        'user_id' => $user['user_id'],
                        'user_role' => $user['user_role']
                    ]
                ]);
            } else {
                // Invalid credentials
                ResponseHelper::send(401, ['error' => 'Invalid email or password.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error during login: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle user profile (requires authentication)
    public function getProfile($userId) {
        // Prepare and execute the SQL statement to retrieve the user by ID
        // Exclude sensitive information like hashed_password
        $sql = "SELECT user_id, user_role, registration_timestamp, last_login, email, is_active, full_name, date_of_birth, gender, institution, study_level, preferred_exam_types, admin_name, permissions, creator_name, expertise_area, creation_credentials, profile_picture_url FROM Users WHERE user_id = :user_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch();

            if ($user) {
                // Return user profile data
                ResponseHelper::send(200, $user);
            } else {
                // User not found (should not happen if authenticated, but handle defensively)
                ResponseHelper::send(404, ['error' => 'User profile not found.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching user profile: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Add other user-related methods (update, delete, etc.)
}

?>