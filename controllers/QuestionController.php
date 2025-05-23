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

    // Handle retrieving all questions with pagination
    public function getAll($route_params = null, $request_data = null) {
        try {
            // Get pagination parameters from request data, with defaults
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Calculate pagination data
            $paginationData = PaginationHelper::paginate($this->pdo, 'Questions', null, [], $page, $limit);

            // Fetch questions with LIMIT and OFFSET
            $sql = "SELECT q.*, es.exam_id, es.subject_id, t.topic_name, u.full_name as created_by_user_name FROM Questions q
                    JOIN ExamSubjects es ON q.exam_subject_id = es.exam_subject_id
                    LEFT JOIN Topics t ON q.topic_id = t.topic_id
                    JOIN Users u ON q.created_by_user_id = u.user_id
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
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

            // Get pagination metadata
            $baseUrl = $_SERVER['REQUEST_URI']; // Use current request URI as base URL
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            // Combine data and metadata
            $response_data = [
                'data' => $questions,
                'meta' => $paginationMeta['pagination']
            ];

            http_response_code(200); // OK
            echo json_encode($response_data);

        } catch (PDOException $e) {
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

    // Handle creating multiple questions and their options in bulk
    public function bulkCreate($data) {
        // Basic input validation: check if data is an array and not empty
        if (!is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or empty data array provided for bulk creation.']);
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $sql_question = "INSERT INTO Questions (exam_subject_id, topic_id, question_text, question_type, correct_answer, explanation, difficulty_level, created_by_user_id) VALUES (:exam_subject_id, :topic_id, :question_text, :question_type, :correct_answer, :explanation, :difficulty_level, :created_by_user_id)";
            $stmt_question = $this->pdo->prepare($sql_question);

            $sql_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
            $stmt_option = $this->pdo->prepare($sql_option);

            $created_question_ids = [];

            foreach ($data as $question_data) {
                // Basic input validation for each question
                if (!isset($question_data['exam_subject_id'], $question_data['question_text'], $question_data['question_type'], $question_data['correct_answer'], $question_data['created_by_user_id'], $question_data['options']) || !is_array($question_data['options']) || empty($question_data['options'])) {
                     $this->pdo->rollBack();
                     http_response_code(400);
                     echo json_encode(['error' => 'Missing required fields or options array is missing/empty for one or more questions.']);
                     return;
                }

                $exam_subject_id = $question_data['exam_subject_id'];
                $topic_id = $question_data['topic_id'] ?? null;
                $question_text = $question_data['question_text'];
                $question_type = $question_data['question_type'];
                $correct_answer = $question_data['correct_answer'];
                $explanation = $question_data['explanation'] ?? null;
                $difficulty_level = $question_data['difficulty_level'] ?? null;
                $created_by_user_id = $question_data['created_by_user_id'];
                $options = $question_data['options'];

                // Insert the question
                $stmt_question->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
                $stmt_question->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
                $stmt_question->bindParam(':question_text', $question_text);
                $stmt_question->bindParam(':question_type', $question_type);
                $stmt_question->bindParam(':correct_answer', $correct_answer);
                $stmt_question->bindParam(':explanation', $explanation);
                $stmt_question->bindParam(':difficulty_level', $difficulty_level);
                $stmt_question->bindParam(':created_by_user_id', $created_by_user_id, PDO::PARAM_INT);

                if (!$stmt_question->execute()) {
                    throw new \PDOException('Failed to insert question during bulk creation.');
                }

                $question_id = $this->pdo->lastInsertId();
                $created_question_ids[] = $question_id;

                // Insert options for the question
                foreach ($options as $option) {
                    if (!isset($option['option_letter'], $option['option_text'], $option['is_correct'])) {
                         $this->pdo->rollBack();
                         http_response_code(400);
                         echo json_encode(['error' => 'Missing required option fields for one or more options during bulk creation.']);
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
                        throw new \PDOException('Failed to insert option during bulk creation.');
                    }
                }
            }

            // Commit the transaction
            $this->pdo->commit();

            http_response_code(201); // 201 Created
            echo json_encode(['message' => 'Questions and options created successfully in bulk.', 'question_ids' => $created_question_ids]);

        } catch (\PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during bulk question/option creation: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during bulk question/option creation.']);
        }
    }

    // Handle updating multiple questions and their options in bulk
    public function bulkUpdate($data) {
        // Basic input validation: check if data is an array and not empty
        if (!is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or empty data array provided for bulk update.']);
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $sql_update_question = "UPDATE Questions SET exam_subject_id = :exam_subject_id, topic_id = :topic_id, question_text = :question_text, question_type = :question_type, correct_answer = :correct_answer, explanation = :explanation, difficulty_level = :difficulty_level WHERE question_id = :question_id";
            $stmt_update_question = $this->pdo->prepare($sql_update_question);

            $sql_delete_options = "DELETE FROM QuestionOptions WHERE question_id = :question_id";
            $stmt_delete_options = $this->pdo->prepare($sql_delete_options);

            $sql_insert_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
            $stmt_insert_option = $this->pdo->prepare($sql_insert_option);

            $updated_count = 0;

            foreach ($data as $question_data) {
                // Basic input validation for each question update
                if (!isset($question_data['question_id']) || !isset($question_data['exam_subject_id'], $question_data['question_text'], $question_data['question_type'], $question_data['correct_answer'], $question_data['options']) || !is_array($question_data['options'])) {
                     $this->pdo->rollBack();
                     http_response_code(400);
                     echo json_encode(['error' => 'Missing required fields or options array is missing/invalid for one or more questions in bulk update.']);
                     return;
                }

                $question_id = $question_data['question_id'];
                $exam_subject_id = $question_data['exam_subject_id'];
                $topic_id = $question_data['topic_id'] ?? null;
                $question_text = $question_data['question_text'];
                $question_type = $question_data['question_type'];
                $correct_answer = $question_data['correct_answer'];
                $explanation = $question_data['explanation'] ?? null;
                $difficulty_level = $question_data['difficulty_level'] ?? null;
                $options = $question_data['options'];

                // Update the question
                $stmt_update_question->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                $stmt_update_question->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
                $stmt_update_question->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
                $stmt_update_question->bindParam(':question_text', $question_text);
                $stmt_update_question->bindParam(':question_type', $question_type);
                $stmt_update_question->bindParam(':correct_answer', $correct_answer);
                $stmt_update_question->bindParam(':explanation', $explanation);
                $stmt_update_question->bindParam(':difficulty_level', $difficulty_level);

                if (!$stmt_update_question->execute()) {
                    throw new \PDOException('Failed to update question during bulk update.');
                }

                // Delete existing options for the question
                $stmt_delete_options->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                if (!$stmt_delete_options->execute()) {
                     throw new \PDOException('Failed to delete existing options during bulk update.');
                }

                // Insert new options for the question
                foreach ($options as $option) {
                    if (!isset($option['option_letter'], $option['option_text'], $option['is_correct'])) {
                         $this->pdo->rollBack();
                         http_response_code(400);
                         echo json_encode(['error' => 'Missing required option fields for one or more options during bulk update.']);
                         return;
                    }

                    $option_letter = $option['option_letter'];
                    $option_text = $option['option_text'];
                    $is_correct = $option['is_correct'];

                    $stmt_insert_option->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                    $stmt_insert_option->bindParam(':option_letter', $option_letter);
                    $stmt_insert_option->bindParam(':option_text', $option_text);
                    $stmt_insert_option->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);

                    if (!$stmt_insert_option->execute()) {
                        throw new \PDOException('Failed to insert option during bulk update.');
                    }
                }
                $updated_count++;
            }

            // Commit the transaction
            $this->pdo->commit();

            http_response_code(200); // OK
            echo json_encode(['message' => "{$updated_count} questions and their options updated successfully in bulk."]);

        } catch (\PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during bulk question/option update: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during bulk question/option update.']);
        }
    }

    // Handle deleting multiple questions and their options in bulk
    public function bulkDelete($data) {
        // Basic input validation: check if data is an array of IDs and not empty
        if (!is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or empty data array provided for bulk deletion.']);
            return;
        }

        // Ensure all elements in the array are integers (question IDs)
        foreach ($data as $id) {
            if (!is_int($id) || $id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid question ID found in the bulk deletion request.']);
                return;
            }
        }

        $this->pdo->beginTransaction();

        try {
            // Create a comma-separated string of question IDs for the SQL query
            $placeholders = implode(',', array_fill(0, count($data), '?'));

            // Delete associated options first due to foreign key constraint
            $sql_delete_options = "DELETE FROM QuestionOptions WHERE question_id IN ({$placeholders})";
            $stmt_delete_options = $this->pdo->prepare($sql_delete_options);
            // Bind parameters - PDO requires binding each value individually for IN clause
            foreach ($data as $index => $id) {
                $stmt_delete_options->bindValue(($index + 1), $id, PDO::PARAM_INT);
            }

            if (!$stmt_delete_options->execute()) {
                 throw new \PDOException('Failed to delete associated options during bulk deletion.');
            }

            // Then delete the questions
            $sql_delete_question = "DELETE FROM Questions WHERE question_id IN ({$placeholders})";
            $stmt_delete_question = $this->pdo->prepare($sql_delete_question);
             // Bind parameters for question deletion
            foreach ($data as $index => $id) {
                $stmt_delete_question->bindValue(($index + 1), $id, PDO::PARAM_INT);
            }

            if ($stmt_delete_question->execute()) {
                $deleted_count = $stmt_delete_question->rowCount();
                $this->pdo->commit();
                http_response_code(200); // OK
                echo json_encode(['message' => "{$deleted_count} questions and associated options deleted successfully in bulk."]);
            } else {
                throw new \PDOException('Failed to delete questions during bulk deletion.');
            }
        } catch (\PDOException $e) {
            // Rollback the transaction on error
            $this->pdo->rollBack();

            // Handle database errors
            error_log("Database Error during bulk question/option deletion: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during bulk question/option deletion.']);
        }
    }
}