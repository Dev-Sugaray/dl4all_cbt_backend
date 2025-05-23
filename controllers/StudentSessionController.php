<?php

class StudentSessionController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new student session
    public function create($data) {
        // Basic input validation
        if (!isset($data['user_id'], $data['exam_subject_id'], $data['total_questions'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (user_id, exam_subject_id, total_questions).']);
            return;
        }

        $user_id = $data['user_id'];
        $exam_subject_id = $data['exam_subject_id'];
        $total_questions = $data['total_questions'];
        $time_allocated_seconds = $data['time_allocated_seconds'] ?? null;
        $session_type = $data['session_type'] ?? 'practice';
        $settings = isset($data['settings']) ? json_encode($data['settings']) : null;

        // Prepare and execute the SQL statement to insert the new student session
        $sql = "INSERT INTO StudentSessions (user_id, exam_subject_id, total_questions, time_allocated_seconds, session_type, settings) VALUES (:user_id, :exam_subject_id, :total_questions, :time_allocated_seconds, :session_type, :settings)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
            $stmt->bindParam(':total_questions', $total_questions, PDO::PARAM_INT);
            $stmt->bindParam(':time_allocated_seconds', $time_allocated_seconds, PDO::PARAM_INT);
            $stmt->bindParam(':session_type', $session_type, PDO::PARAM_STR);
            $stmt->bindParam(':settings', $settings, PDO::PARAM_STR);

            if ($stmt->execute()) {
                // Student session creation successful
                ResponseHelper::send(201, ['message' => 'Student session created successfully.', 'session_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'Student session creation failed.']);
            }
        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during student session creation: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle retrieving all student sessions with pagination
    public function getAll($route_params = null, $request_data = null) {
        try {
            // Get pagination parameters from request data, with defaults
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Calculate pagination data
            $paginationData = PaginationHelper::paginate($this->pdo, 'StudentSessions', null, [], $page, $limit);

            // Fetch student sessions with LIMIT and OFFSET
            $sql = "SELECT ss.*, u.full_name as student_name, e.exam_name, s.subject_name FROM StudentSessions ss JOIN Users u ON ss.user_id = u.user_id JOIN ExamSubjects es ON ss.exam_subject_id = es.exam_subject_id JOIN Exams e ON es.exam_id = e.exam_id JOIN Subjects s ON es.subject_id = s.subject_id LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pagination metadata
            $baseUrl = $_SERVER['REQUEST_URI']; // Use current request URI as base URL
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            // Combine data and metadata
            $response_data = [
                'data' => $sessions,
                'meta' => $paginationMeta['pagination']
            ];

            ResponseHelper::send(200, $response_data);

        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during student session retrieval: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during student session retrieval.']);
        }
    }

    // Handle retrieving a single student session by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing session ID.']);
            return;
        }

        $session_id = $route_params[0];

        $sql = "SELECT ss.*, u.full_name, es.exam_subject_id FROM StudentSessions ss\n                JOIN Users u ON ss.user_id = u.user_id\n                JOIN ExamSubjects es ON ss.exam_subject_id = es.exam_subject_id\n                WHERE ss.session_id = :session_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':session_id', $session_id, PDO::PARAM_INT);
            $stmt->execute();

            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session) {
                ResponseHelper::send(200, $session);
            } else {
                ResponseHelper::send(404, ['message' => 'Student session not found.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error fetching student session by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an existing student session
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing session ID.']);
            return;
        }

        $session_id = $route_params[0];

        // Build the update query dynamically based on provided data
        $update_fields = [];
        $allowed_fields = ['end_time', 'total_questions', 'time_allocated_seconds', 'session_type', 'settings'];
        $bind_params = [':session_id' => $session_id];

        foreach ($request_data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $update_fields[] = "`{$key}` = :{$key}";
                $bind_params[":{$key}"] = $value;
            }
        }

        if (empty($update_fields)) {
            ResponseHelper::send(400, ['error' => 'No valid fields provided for update.']);
            return;
        }

        $sql = "UPDATE StudentSessions SET " . implode(', ', $update_fields) . " WHERE session_id = :session_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($bind_params as $param => $value) {
                // Determine parameter type
                $param_type = PDO::PARAM_STR;
                if (is_int($value)) {
                    $param_type = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                     $param_type = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $param_type = PDO::PARAM_NULL;
                }
                 // Special handling for JSON settings
                if ($param === ':settings' && !is_null($value)) {
                    // Ensure the value is a string (JSON) before binding as STR
                    $value = is_array($value) || is_object($value) ? json_encode($value) : $value;
                    $param_type = PDO::PARAM_STR;
                }

                $stmt->bindParam($param, $bind_params[$param], $param_type);
            }

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Student session updated successfully.']);
                } else {
                    ResponseHelper::send(404, ['message' => 'Student session not found or no changes made.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Student session update failed.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error during student session update: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle deleting an existing student session
    public function delete($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing session ID.']);
            return;
        }

        $session_id = $route_params[0];

        $sql = "DELETE FROM StudentSessions WHERE session_id = :session_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':session_id', $session_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Student session deleted successfully.']);
                } else {
                    ResponseHelper::send(404, ['message' => 'Student session not found.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Student session deletion failed.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error during student session deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }
}