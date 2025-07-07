<?php

class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle user registration
    public function register($data) {
        // Accept 'admin' as 'administrator' for user_role
        if (isset($data['user_role']) && $data['user_role'] === 'admin') {
            $data['user_role'] = 'administrator';
        }

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

    // Handle retrieving user profile (requires authentication)
    // Assumes authenticated user ID is available in $request_data['user_id'] after middleware processing
    public function getProfile($route_params = null, $request_data = null) {
        // Check if authenticated user ID is available
        if (!isset($request_data['user_id'])) {
            ResponseHelper::send(401, ['error' => 'Unauthorized. User ID not available.']);
            return;
        }

        $userId = $request_data['user_id'];

        // Prepare and execute the SQL statement to retrieve the user by ID
        // Exclude sensitive information like hashed_password
        $sql = "SELECT user_id, user_role, registration_timestamp, last_login, email, is_active, full_name, date_of_birth, gender, institution, study_level, preferred_exam_types, admin_name, permissions, creator_name, expertise_area, creation_credentials, profile_picture_url FROM Users WHERE user_id = :user_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

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

    // Handle retrieving all users with pagination
    public function getAll($route_params = null, $request_data = null) {
        try {
            // Get pagination parameters from request data, with defaults
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Calculate pagination data
            $paginationData = PaginationHelper::paginate($this->pdo, 'Users', null, [], $page, $limit);

            // Fetch users with LIMIT and OFFSET
            $sql = "SELECT user_id, user_role, registration_timestamp, last_login, email, is_active, full_name, date_of_birth, gender, institution, study_level, preferred_exam_types, admin_name, permissions, creator_name, expertise_area, creation_credentials, profile_picture_url FROM Users LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pagination metadata
            $baseUrl = $_SERVER['REQUEST_URI']; // Use current request URI as base URL
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            // Combine data and metadata
            $response_data = [
                'data' => $users,
                'meta' => $paginationMeta['pagination']
            ];

            http_response_code(200); // OK
            echo json_encode($response_data);

        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during user retrieval: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during user retrieval.']);
        }
    }

    // Handle retrieving a single user by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing user ID.']);
            return;
        }

        $userId = $route_params[0];

        // Prepare and execute the SQL statement to retrieve the user by ID
        // Exclude sensitive information like hashed_password
        $sql = "SELECT user_id, user_role, registration_timestamp, last_login, email, is_active, full_name, date_of_birth, gender, institution, study_level, preferred_exam_types, admin_name, permissions, creator_name, expertise_area, creation_credentials, profile_picture_url FROM Users WHERE user_id = :user_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Return user profile data
                ResponseHelper::send(200, $user);
            } else {
                // User not found
                ResponseHelper::send(404, ['error' => 'User not found.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching user by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an existing user
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing user ID.']);
            return;
        }

        $userId = $route_params[0];

        // Basic input validation - check if any data is provided for update
        if (empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'No update data provided.']);
            return;
        }

        // Build the SQL query dynamically based on provided data
        $set_clauses = [];
        $params = [':user_id' => $userId];

        // Allowed fields to update
        $allowed_fields = [
            'user_role', 'email', 'password', 'is_active', 'full_name',
            'date_of_birth', 'gender', 'institution', 'study_level', 'preferred_exam_types',
            'admin_name', 'permissions', 'creator_name', 'expertise_area', 'creation_credentials',
            'profile_picture_url'
        ];

        foreach ($request_data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                // Special handling for password hashing
                if ($key === 'password') {
                    $set_clauses[] = 'hashed_password = :hashed_password';
                    $params[':hashed_password'] = password_hash($value, PASSWORD_BCRYPT);
                } elseif ($key === 'user_role') {
                     // Validate user role if provided
                    $allowed_roles = ['student', 'administrator', 'content_creator'];
                    if (!in_array($value, $allowed_roles)) {
                        ResponseHelper::send(400, ['error' => 'Invalid user role specified.']);
                        return;
                    }
                    $set_clauses[] = "`{$key}` = :{$key}";
                    $params[":{$key}"] = $value;
                } elseif ($key === 'is_active') {
                     // Ensure is_active is a boolean
                    $set_clauses[] = "`{$key}` = :{$key}";
                    $params[":{$key}"] = (bool) $value;
                } elseif ($key === 'preferred_exam_types') {
                    // Handle SET type - assuming input is an array or comma-separated string
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $set_clauses[] = "`{$key}` = :{$key}";
                    $params[":{$key}"] = $value;
                } else {
                    $set_clauses[] = "`{$key}` = :{$key}";
                    $params[":{$key}"] = $value;
                }
            }
        }

        // If no valid fields to update, return error
        if (empty($set_clauses)) {
             ResponseHelper::send(400, ['error' => 'No valid fields provided for update.']);
             return;
        }

        $sql = "UPDATE Users SET " . implode(', ', $set_clauses) . " WHERE user_id = :user_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind parameters
            foreach ($params as $key => $value) {
                // Determine parameter type (simplified, could be more robust)
                $param_type = PDO::PARAM_STR;
                if (is_int($value)) $param_type = PDO::PARAM_INT;
                if (is_bool($value)) $param_type = PDO::PARAM_BOOL;
                if (is_null($value)) $param_type = PDO::PARAM_NULL;

                $stmt->bindParam($key, $params[$key], $param_type);
            }

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'User updated successfully.']);
                } else {
                    // No rows affected, likely user_id not found or no changes made
                    ResponseHelper::send(404, ['error' => 'User not found or no changes made.']);
                }
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'User update failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate email)
             if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                ResponseHelper::send(409, ['error' => 'Email already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during user update: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle deleting an existing user
    public function delete($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing user ID.']);
            return;
        }

        $userId = $route_params[0];

        // Prepare and execute the SQL statement to delete the user
        $sql = "DELETE FROM Users WHERE user_id = :user_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'User deleted successfully.']);
                } else {
                    // No rows affected, likely user_id not found
                    ResponseHelper::send(404, ['error' => 'User not found.']);
                }
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'User deletion failed.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error during user deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }
}

?>