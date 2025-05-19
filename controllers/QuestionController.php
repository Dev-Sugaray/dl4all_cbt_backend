<?php

class QuestionController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new question and its options
    public function create($data) {
        // Basic input validation for question fields
        if (!isset($data['exam_subject_id'], $data['question_text'], $data['question_type'], $data['correct_answer'], $data['created_by_user_id'], $data['options']) || !is_array($data['options']) || empty($data['options'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required question fields (exam_subject_id, question_text, question_type, correct_answer, created_by_user_id) or options array is missing/empty.']);
            return;
        }

        $exam_subject_id = $data['exam_subject_id'];
        $topic_id = $data['topic_id'] ?? null; // topic_id is optional
        $question_text = $data['question_text'];
        $question_type = $data['question_type'];
        $correct_answer = $data['correct_answer'];
        $explanation = $data['explanation'] ?? null; // explanation is optional
        $difficulty_level = $data['difficulty_level'] ?? null; // difficulty_level is optional
        $created_by_user_id = $data['created_by_user_id'];
        $options = $data['options'];

        // Start a transaction for atomicity
        $this->pdo->beginTransaction();

        try {
            // Insert the question
            $sql_question = "INSERT INTO Questions (exam_subject_id, topic_id, question_text, question_type, correct_answer, explanation, difficulty_level, created_by_user_id) VALUES (:exam_subject_id, :topic_id, :question_text, :question_type, :correct_answer, :explanation, :difficulty_level, :created_by_user_id)";
            $stmt_question = $this->pdo->prepare($sql_question);
            $stmt_question->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
            $stmt_question->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
            $stmt_question->bindParam(':question_text', $question_text);
            $stmt_question->bindParam(':question_type', $question_type);
            $stmt_question->bindParam(':correct_answer', $correct_answer);
            $stmt_question->bindParam(':explanation', $explanation);
            $stmt_question->bindParam(':difficulty_level', $difficulty_level);
            $stmt_question->bindParam(':created_by_user_id', $created_by_user_id, PDO::PARAM_INT);

            if (!$stmt_question->execute()) {
                throw new \PDOException('Failed to insert question.');
            }

            $question_id = $this->pdo->lastInsertId();

            // Insert options
            $sql_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
            $stmt_option = $this->pdo->prepare($sql_option);

            foreach ($options as $option) {
                // Basic validation for each option
                if (!isset($option['option_letter'], $option['option_text'], $option['is_correct'])) {
                     $this->pdo->rollBack();
                     http_response_code(400);
                     echo json_encode(['error' => 'Missing required option fields (option_letter, option_text, is_correct).']);
                     return;
                }

                $option_letter = $option['option_letter'];
                $option_text = $option['option_text'];
                $is_correct = $option['is_correct'];

                $stmt_option->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                $stmt_option->bindParam(':option_letter', $option_letter);
                $stmt_option->bindParam(':option_text', $option_text);
                $stmt_option->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);

                if (!$stmt_option->execute()) {
                    throw new \PDOException('Failed to insert option.');
                }
            }

            // Commit the transaction
            $this->pdo->commit();

            // Question and options creation successful
            http_response_code(201); // 201 Created
            echo json_encode(['message' => 'Question and options created successfully.', 'question_id' => $question_id]);

        } catch (\PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during question/option creation: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during question/option creation.']);
        }
    }

    // Handle retrieving all questions
    public function getAll() {
        try {
            $sql = "SELECT q.*, es.exam_id, es.subject_id, t.topic_name, u.full_name as created_by_user_name FROM Questions q
                    JOIN ExamSubjects es ON q.exam_subject_id = es.exam_subject_id
                    LEFT JOIN Topics t ON q.topic_id = t.topic_id
                    JOIN Users u ON q.created_by_user_id = u.user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // For each question, fetch its options
            foreach ($questions as &$question) {
                $sql_options = "SELECT option_id, option_letter, option_text, is_correct FROM QuestionOptions WHERE question_id = :question_id";
                $stmt_options = $this->pdo->prepare($sql_options);
                $stmt_options->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                $stmt_options->execute();
                $question['options'] = $stmt_options->fetchAll(PDO::FETCH_ASSOC);
            }

            http_response_code(200); // OK
            echo json_encode($questions);

        } catch (\PDOException $e) {
            // Handle database errors
            error_log("Database Error during question retrieval: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during question retrieval.']);
        }
    }

    // Handle retrieving a question by ID
    public function getById($questionId) {
        // Basic input validation for question ID
        if (!isset($questionId) || !is_numeric($questionId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing question ID.']);
            return;
        }

        try {
            // Fetch the question details
            $sql_question = "SELECT q.*, es.exam_id, es.subject_id, t.topic_name, u.full_name as created_by_user_name FROM Questions q
                             JOIN ExamSubjects es ON q.exam_subject_id = es.exam_subject_id
                             LEFT JOIN Topics t ON q.topic_id = t.topic_id
                             JOIN Users u ON q.created_by_user_id = u.user_id
                             WHERE q.question_id = :question_id LIMIT 1";
            $stmt_question = $this->pdo->prepare($sql_question);
            $stmt_question->bindParam(':question_id', $questionId, PDO::PARAM_INT);
            $stmt_question->execute();
            $question = $stmt_question->fetch(PDO::FETCH_ASSOC);

            if (!$question) {
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Question not found.']);
                return;
            }

            // Fetch options for the question
            $sql_options = "SELECT option_id, option_letter, option_text, is_correct FROM QuestionOptions WHERE question_id = :question_id";
            $stmt_options = $this->pdo->prepare($sql_options);
            $stmt_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
            $stmt_options->execute();
            $question['options'] = $stmt_options->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200); // OK
            echo json_encode($question);

        } catch (\PDOException $e) {
            // Handle database errors
            error_log("Database Error during question retrieval by ID: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during question retrieval.']);
        }
    }

    // Handle updating a question and its options
    public function update($questionId, $data) {
        // Basic input validation for question ID and data
        if (!isset($questionId) || !is_numeric($questionId) || !is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing question ID or update data.']);
            return;
        }

        // Start a transaction for atomicity
        $this->pdo->beginTransaction();

        try {
            // Update question fields if provided in data
            $updateFields = [];
            $updateParams = [':question_id' => $questionId];

            if (isset($data['exam_subject_id'])) { $updateFields[] = 'exam_subject_id = :exam_subject_id'; $updateParams[':exam_subject_id'] = $data['exam_subject_id']; }
            if (isset($data['topic_id'])) { $updateFields[] = 'topic_id = :topic_id'; $updateParams[':topic_id'] = $data['topic_id']; }
            if (isset($data['question_text'])) { $updateFields[] = 'question_text = :question_text'; $updateParams[':question_text'] = $data['question_text']; }
            if (isset($data['question_type'])) { $updateFields[] = 'question_type = :question_type'; $updateParams[':question_type'] = $data['question_type']; }
            if (isset($data['correct_answer'])) { $updateFields[] = 'correct_answer = :correct_answer'; $updateParams[':correct_answer'] = $data['correct_answer']; }
            if (isset($data['explanation'])) { $updateFields[] = 'explanation = :explanation'; $updateParams[':explanation'] = $data['explanation']; }
            if (isset($data['difficulty_level'])) { $updateFields[] = 'difficulty_level = :difficulty_level'; $updateParams[':difficulty_level'] = $data['difficulty_level']; }
            // Note: created_by_user_id and creation_date should not be updated via this endpoint

            if (!empty($updateFields)) {
                $sql_question_update = "UPDATE Questions SET " . implode(', ', $updateFields) . " WHERE question_id = :question_id";
                $stmt_question_update = $this->pdo->prepare($sql_question_update);
                if (!$stmt_question_update->execute($updateParams)) {
                    throw new \PDOException('Failed to update question.');
                }
            }

            // Update options if provided in data
            if (isset($data['options']) && is_array($data['options'])) {
                // A simple approach: delete existing options and insert new ones
                // More complex logic could involve updating existing options and adding/deleting as needed

                // Delete existing options
                $sql_delete_options = "DELETE FROM QuestionOptions WHERE question_id = :question_id";
                $stmt_delete_options = $this->pdo->prepare($sql_delete_options);
                $stmt_delete_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                if (!$stmt_delete_options->execute()) {
                     throw new \PDOException('Failed to delete existing options.');
                }

                // Insert new options
                $sql_insert_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
                $stmt_insert_option = $this->pdo->prepare($sql_insert_option);

                foreach ($data['options'] as $option) {
                    // Basic validation for each option
                    if (!isset($option['option_letter'], $option['option_text'], $option['is_correct'])) {
                         $this->pdo->rollBack();
                         http_response_code(400);
                         echo json_encode(['error' => 'Missing required option fields (option_letter, option_text, is_correct) in update data.']);
                         return;
                    }

                    $option_letter = $option['option_letter'];
                    $option_text = $option['option_text'];
                    $is_correct = $option['is_correct'];

                    $stmt_insert_option->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                    $stmt_insert_option->bindParam(':option_letter', $option_letter);
                    $stmt_insert_option->bindParam(':option_text', $option_text);
                    $stmt_insert_option->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);

                    if (!$stmt_insert_option->execute()) {
                        throw new \PDOException('Failed to insert new option.');
                    }
                }
            }

            // Commit the transaction
            $this->pdo->commit();

            // Question and options update successful
            http_response_code(200); // OK
            echo json_encode(['message' => 'Question and options updated successfully.']);

        } catch (\PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during question/option update: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during question/option update.']);
        }
    }

    // Handle deleting a question and its options
    public function delete($questionId) {
        // Basic input validation for question ID
        if (!isset($questionId) || !is_numeric($questionId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing question ID.']);
            return;
        }

        // Start a transaction for atomicity
        $this->pdo->beginTransaction();

        try {
            // Delete associated options first
            $sql_delete_options = "DELETE FROM QuestionOptions WHERE question_id = :question_id";
            $stmt_delete_options = $this->pdo->prepare($sql_delete_options);
            $stmt_delete_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
            if (!$stmt_delete_options->execute()) {
                throw new \PDOException('Failed to delete associated options.');
            }

            // Delete the question
            $sql_delete_question = "DELETE FROM Questions WHERE question_id = :question_id";
            $stmt_delete_question = $this->pdo->prepare($sql_delete_question);
            $stmt_delete_question->bindParam(':question_id', $questionId, PDO::PARAM_INT);
            if (!$stmt_delete_question->execute()) {
                throw new \PDOException('Failed to delete question.');
            }

            // Check if a row was actually deleted (optional, but good practice)
            if ($stmt_delete_question->rowCount() === 0) {
                $this->pdo->rollBack();
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Question not found.']);
                return;
            }

            // Commit the transaction
            $this->pdo->commit();

            // Question and options deletion successful
            http_response_code(200); // OK or 204 No Content
            echo json_encode(['message' => 'Question and associated options deleted successfully.']);

        } catch (\PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during question/option deletion: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during question/option deletion.']);
        }
    }
}