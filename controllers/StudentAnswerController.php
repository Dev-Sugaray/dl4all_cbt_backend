<?php

class StudentAnswerController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle submitting a student answer
    public function create($data) {
        // Basic input validation
        if (!isset($data['session_id'], $data['question_id'], $data['submitted_answer'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (session_id, question_id, submitted_answer).']);
            return;
        }

        $session_id = $data['session_id'];
        $question_id = $data['question_id'];
        $submitted_answer = $data['submitted_answer'];
        $is_correct = $data['is_correct'] ?? null; // Optional, can be determined later
        $time_taken_seconds = $data['time_taken_seconds'] ?? null;

        // Prepare and execute the SQL statement to insert the new student answer
        $sql = "INSERT INTO StudentAnswers (session_id, question_id, submitted_answer, is_correct, time_taken_seconds) VALUES (:session_id, :question_id, :submitted_answer, :is_correct, :time_taken_seconds)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':session_id', $session_id, PDO::PARAM_INT);
            $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
            $stmt->bindParam(':submitted_answer', $submitted_answer, PDO::PARAM_STR);
            $stmt->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);
            $stmt->bindParam(':time_taken_seconds', $time_taken_seconds, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Student answer creation successful
                ResponseHelper::send(201, ['message' => 'Student answer recorded successfully.', 'answer_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'Failed to record student answer.']);
            }
        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during student answer creation: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle retrieving all student answers with pagination
    public function getAll($route_params = null, $request_data = null) {
        try {
            // Get pagination parameters from request data, with defaults
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Calculate pagination data
            $paginationData = PaginationHelper::paginate($this->pdo, 'StudentAnswers', null, [], $page, $limit);

            // Fetch student answers with LIMIT and OFFSET
            $sql = "SELECT sa.*, ss.session_id, q.question_text FROM StudentAnswers sa JOIN StudentSessions ss ON sa.session_id = ss.session_id JOIN Questions q ON sa.question_id = q.question_id LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pagination metadata
            $baseUrl = $_SERVER['REQUEST_URI']; // Use current request URI as base URL
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            // Combine data and metadata
            $response_data = [
                'data' => $answers,
                'meta' => $paginationMeta['pagination']
            ];

            ResponseHelper::send(200, $response_data);

        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during student answer retrieval: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during student answer retrieval.']);
        }
    }

    // Handle retrieving a single student answer by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing answer ID.']);
            return;
        }

        $answer_id = $route_params[0];

        $sql = "SELECT sa.*, q.question_text FROM StudentAnswers sa\n                JOIN Questions q ON sa.question_id = q.question_id\n                WHERE sa.answer_id = :answer_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);
            $stmt->execute();

            $answer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($answer) {
                ResponseHelper::send(200, $answer);
            } else {
                ResponseHelper::send(404, ['message' => 'Student answer not found.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error fetching student answer by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an existing student answer
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing answer ID.']);
            return;
        }

        $answer_id = $route_params[0];

        // Basic input validation - check if any data is provided for update
        if (empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'No update data provided.']);
            return;
        }

        // Build the update query dynamically based on provided data
        $update_fields = [];
        $allowed_fields = ['submitted_answer', 'is_correct', 'time_taken_seconds'];
        $bind_params = [':answer_id' => $answer_id];

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

        $sql = "UPDATE StudentAnswers SET " . implode(', ', $update_fields) . " WHERE answer_id = :answer_id";

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

                $stmt->bindParam($param, $bind_params[$param], $param_type);
            }

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Student answer updated successfully.']);
                } else {
                    ResponseHelper::send(404, ['message' => 'Student answer not found or no changes made.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Student answer update failed.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error during student answer update: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle deleting an existing student answer
    public function delete($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing answer ID.']);
            return;
        }

        $answer_id = $route_params[0];

        $sql = "DELETE FROM StudentAnswers WHERE answer_id = :answer_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Student answer deleted successfully.']);
                } else {
                    ResponseHelper::send(404, ['message' => 'Student answer not found.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Student answer deletion failed.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error during student answer deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }
}