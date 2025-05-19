<?php

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/utils/ResponseHelper.php';

class StudentSessionController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Method to create a new Student Session
    public function create($request_data) {
        // Basic input validation
        if (!isset($request_data['user_id'], $request_data['exam_subject_id'], $request_data['total_questions'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (user_id, exam_subject_id, total_questions).']);
            return;
        }

        $user_id = $request_data['user_id'];
        $exam_subject_id = $request_data['exam_subject_id'];
        $total_questions = $request_data['total_questions'];
        $time_allocated_seconds = $request_data['time_allocated_seconds'] ?? null;
        $session_type = $request_data['session_type'] ?? 'practice';
        $settings = isset($request_data['settings']) ? json_encode($request_data['settings']) : null;

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

    // Method to get all Student Sessions
    public function getAll() {
        $sql = "SELECT ss.*, u.full_name, es.exam_subject_id FROM StudentSessions ss
                JOIN Users u ON ss.user_id = u.user_id
                JOIN ExamSubjects es ON ss.exam_subject_id = es.exam_subject_id";

        try {
            $stmt = $this->pdo->query($sql);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($sessions) {
                ResponseHelper::send(200, $sessions);
            } else {
                ResponseHelper::send(404, ['message' => 'No student sessions found.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error fetching all student sessions: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Method to get a single Student Session by ID
    public function getById($route_params) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing session ID.']);
            return;
        }

        $session_id = $route_params[0];

        $sql = "SELECT ss.*, u.full_name, es.exam_subject_id FROM StudentSessions ss
                JOIN Users u ON ss.user_id = u.user_id
                JOIN ExamSubjects es ON ss.exam_subject_id = es.exam_subject_id
                WHERE ss.session_id = :session_id LIMIT 1";

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

    // Method to update a Student Session by ID
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
                    $value = json_encode($value);
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

    // Method to delete a Student Session by ID
    public function delete($route_params) {
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