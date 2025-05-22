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

    // Handle retrieving a single question by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing question ID.']);
            return;
        }

        $questionId = $route_params[0];

        try {
            $sql = "SELECT q.*, es.exam_id, es.subject_id, t.topic_name, u.full_name as created_by_user_name FROM Questions q
                    JOIN ExamSubjects es ON q.exam_subject_id = es.exam_subject_id
                    LEFT JOIN Topics t ON q.topic_id = t.topic_id
                    JOIN Users u ON q.created_by_user_id = u.user_id
                    WHERE q.question_id = :question_id LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
            $stmt->execute();

            $question = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($question) {
                // Also fetch options for this question
                $sql_options = "SELECT option_id, option_letter, option_text, is_correct FROM QuestionOptions WHERE question_id = :question_id";
                $stmt_options = $this->pdo->prepare($sql_options);
                $stmt_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                $stmt_options->execute();
                $options = $stmt_options->fetchAll(PDO::FETCH_ASSOC);

                $question['options'] = $options;

                http_response_code(200); // OK
                echo json_encode($question);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Question not found.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error fetching question by ID: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an existing question and its options
    public function update($route_params, $request_data) {
        // Basic input validation
        if (!isset($route_params[0])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing question ID.']);
            return;
        }

        $questionId = $route_params[0];

        if (empty($request_data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No update data provided.']);
            return;
        }

        $this->pdo->beginTransaction();

        try {
            // Update question fields if provided
            $question_updates = [];
            $question_params = [':question_id' => $questionId];

            if (isset($request_data['exam_subject_id'])) { $question_updates[] = 'exam_subject_id = :exam_subject_id'; $question_params[':exam_subject_id'] = $request_data['exam_subject_id']; }
            if (isset($request_data['topic_id'])) { $question_updates[] = 'topic_id = :topic_id'; $question_params[':topic_id'] = $request_data['topic_id']; }
            if (isset($request_data['question_text'])) { $question_updates[] = 'question_text = :question_text'; $question_params[':question_text'] = $request_data['question_text']; }
            if (isset($request_data['question_type'])) { $question_updates[] = 'question_type = :question_type'; $question_params[':question_type'] = $request_data['question_type']; }
            if (isset($request_data['correct_answer'])) { $question_updates[] = 'correct_answer = :correct_answer'; $question_params[':correct_answer'] = $request_data['correct_answer']; }
            if (isset($request_data['explanation'])) { $question_updates[] = 'explanation = :explanation'; $question_params[':explanation'] = $request_data['explanation']; }
            if (isset($request_data['difficulty_level'])) { $question_updates[] = 'difficulty_level = :difficulty_level'; $question_params[':difficulty_level'] = $request_data['difficulty_level']; }
            // last_modified_date is updated automatically by the database

            if (!empty($question_updates)) {
                $sql_question_update = "UPDATE Questions SET " . implode(', ', $question_updates) . " WHERE question_id = :question_id";
                $stmt_question_update = $this->pdo->prepare($sql_question_update);
                foreach ($question_params as $key => $value) {
                    $stmt_question_update->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                if (!$stmt_question_update->execute()) {
                    throw new \PDOException('Failed to update question fields.');
                }
            }

            // Update options if provided
            if (isset($request_data['options']) && is_array($request_data['options'])) {
                // Simple approach: delete existing options and insert new ones
                // More complex: update existing options and add/delete as needed
                // For simplicity, we'll delete and re-insert.

                $sql_delete_options = "DELETE FROM QuestionOptions WHERE question_id = :question_id";
                $stmt_delete_options = $this->pdo->prepare($sql_delete_options);
                $stmt_delete_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                if (!$stmt_delete_options->execute()) {
                     throw new \PDOException('Failed to delete existing options.');
                }

                $sql_insert_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
                $stmt_insert_option = $this->pdo->prepare($sql_insert_option);

                foreach ($request_data['options'] as $option) {
                    if (!isset($option['option_letter'], $option['option_text'], $option['is_correct'])) {
                         throw new \PDOException('Missing required option fields (option_letter, option_text, is_correct) in update data.');
                    }
                    $stmt_insert_option->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                    $stmt_insert_option->bindParam(':option_letter', $option['option_letter']);
                    $stmt_insert_option->bindParam(':option_text', $option['option_text']);
                    $stmt_insert_option->bindParam(':is_correct', $option['is_correct'], PDO::PARAM_BOOL);
                    if (!$stmt_insert_option->execute()) {
                        throw new \PDOException('Failed to insert updated option.');
                    }
                }
            }

            // Commit the transaction
            $this->pdo->commit();

            http_response_code(200); // OK
            echo json_encode(['message' => 'Question and options updated successfully.']);

        } catch (PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during question update: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during question update.']);
        }
    }

    // Handle deleting an existing question and its options
    public function delete($route_params, $request_data = null) {
        // Basic input validation
        if (!isset($route_params[0])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing question ID.']);
            return;
        }

        $questionId = $route_params[0];

        $this->pdo->beginTransaction();

        try {
            // Delete associated options first due to foreign key constraint
            $sql_delete_options = "DELETE FROM QuestionOptions WHERE question_id = :question_id";
            $stmt_delete_options = $this->pdo->prepare($sql_delete_options);
            $stmt_delete_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
            if (!$stmt_delete_options->execute()) {
                 throw new \PDOException('Failed to delete associated options.');
            }

            // Then delete the question
            $sql_delete_question = "DELETE FROM Questions WHERE question_id = :question_id";
            $stmt_delete_question = $this->pdo->prepare($sql_delete_question);
            $stmt_delete_question->bindParam(':question_id', $questionId, PDO::PARAM_INT);

            if ($stmt_delete_question->execute()) {
                // Check if any rows were affected (for the question deletion)
                if ($stmt_delete_question->rowCount() > 0) {
                    $this->pdo->commit();
                    http_response_code(200); // OK or 204 No Content
                    echo json_encode(['message' => 'Question and associated options deleted successfully.']);
                } else {
                    $this->pdo->rollBack(); // Rollback if question not found
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Question not found.']);
                }
            } else {
                throw new \PDOException('Failed to delete question.');
            }
        } catch (PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during question deletion: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during question deletion.']);
        }
    }
}