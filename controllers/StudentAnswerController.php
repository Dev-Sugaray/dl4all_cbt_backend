<?php

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/utils/ResponseHelper.php';

class StudentAnswerController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Method to create a new Student Answer
    public function create($request_data) {
        // Basic input validation
        if (!isset($request_data['session_id'], $request_data['question_id'], $request_data['submitted_answer'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (session_id, question_id, submitted_answer).']);
            return;
        }

        $session_id = $request_data['session_id'];
        $question_id = $request_data['question_id'];
        $submitted_answer = $request_data['submitted_answer'];
        $is_correct = $request_data['is_correct'] ?? null; // Optional, can be determined later
        $time_taken_seconds = $request_data['time_taken_seconds'] ?? null;

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

    // Method to get Student Answers by Session ID
    public function getBySessionId($route_params) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing session ID.']);
            return;
        }

        $session_id = $route_params[0];

        $sql = "SELECT sa.*, q.question_text FROM StudentAnswers sa
                JOIN Questions q ON sa.question_id = q.question_id
                WHERE sa.session_id = :session_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':session_id', $session_id, PDO::PARAM_INT);
            $stmt->execute();

            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($answers) {
                ResponseHelper::send(200, $answers);
            } else {
                ResponseHelper::send(404, ['message' => 'No student answers found for this session.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error fetching student answers by session ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Method to update a Student Answer by ID (Optional, depending on requirements)
    // public function update($route_params, $request_data) {
    //     ResponseHelper::send(501, ['error' => 'Not Implemented']);
    // }

    // Method to delete a Student Answer by ID (Optional, depending on requirements)
    // public function delete($route_params) {
    //     ResponseHelper::send(501, ['error' => 'Not Implemented']);
    // }
}