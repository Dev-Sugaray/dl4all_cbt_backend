<?php

class QuestionController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new question and its options
    public function create($request_data) {
        // 1. Input Validation
        $required_fields = ['exam_subject_id', 'question_text', 'question_type', 'correct_answer', 'options'];
        foreach ($required_fields as $field) {
            if (!isset($request_data[$field])) {
                ResponseHelper::send(400, ['error' => "Missing required field: {$field}."]);
                return;
            }
        }

        if (!is_array($request_data['options']) || empty($request_data['options'])) {
            ResponseHelper::send(400, ['error' => 'Options array is missing or empty.']);
            return;
        }

        $exam_subject_id = (int) $request_data['exam_subject_id'];
        $topic_id = isset($request_data['topic_id']) ? (int) $request_data['topic_id'] : null;
        $question_text = trim($request_data['question_text']);
        $question_type = trim($request_data['question_type']);
        $correct_answer = trim($request_data['correct_answer']);
        $explanation = isset($request_data['explanation']) ? trim($request_data['explanation']) : null;
        $difficulty_level = isset($request_data['difficulty_level']) ? trim($request_data['difficulty_level']) : null;
        // IMPORTANT: created_by_user_id should ideally come from authenticated user context (e.g., JWT token)
        // For this task, we'll assume it's securely provided or hardcode for demonstration.
        // In a real application, you'd get this from AuthMiddleware or similar.
        $created_by_user_id = 1; // Placeholder: Replace with actual authenticated user ID

        if (empty($question_text) || empty($question_type) || empty($correct_answer)) {
            ResponseHelper::send(400, ['error' => 'Question text, type, and correct answer cannot be empty.']);
            return;
        }

        // Validate exam_subject_id exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ExamSubjects WHERE exam_subject_id = :exam_subject_id");
        $stmt->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            ResponseHelper::send(404, ['error' => 'Exam Subject not found.']);
            return;
        }

        // Validate topic_id if provided
        if ($topic_id !== null) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Topics WHERE topic_id = :topic_id");
            $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                ResponseHelper::send(404, ['error' => 'Topic not found.']);
                return;
            }
        }

        // Validate question_type (example types)
        $allowed_question_types = ['multiple_choice', 'true_false', 'short_answer']; // Define your allowed types
        if (!in_array($question_type, $allowed_question_types)) {
            ResponseHelper::send(400, ['error' => 'Invalid question type.']);
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $sql_question = "INSERT INTO Questions (exam_subject_id, topic_id, question_text, question_type, correct_answer, explanation, difficulty_level, created_by_user_id, is_active) VALUES (:exam_subject_id, :topic_id, :question_text, :question_type, :correct_answer, :explanation, :difficulty_level, :created_by_user_id, TRUE)";
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

            $sql_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
            $stmt_option = $this->pdo->prepare($sql_option);

            $found_correct_answer = false;
            $option_letters = [];

            foreach ($request_data['options'] as $option) {
                $option_required_fields = ['option_letter', 'option_text', 'is_correct'];
                foreach ($option_required_fields as $field) {
                    if (!isset($option[$field])) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => "Missing required option field: {$field}."]);
                        return;
                    }
                }

                $option_letter = trim($option['option_letter']);
                $option_text = trim($option['option_text']);
                $is_correct = filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if (empty($option_letter) || empty($option_text)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Option letter and text cannot be empty.']);
                    return;
                }
                if ($is_correct === null) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => "Invalid 'is_correct' value for option {$option_letter}. Must be true or false."]);
                    return;
                }

                if (in_array($option_letter, $option_letters)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => "Duplicate option letter found: {$option_letter}."]);
                    return;
                }
                $option_letters[] = $option_letter;

                if ($is_correct) {
                    $found_correct_answer = true;
                }

                $stmt_option->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                $stmt_option->bindParam(':option_letter', $option_letter);
                $stmt_option->bindParam(':option_text', $option_text);
                $stmt_option->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);

                if (!$stmt_option->execute()) {
                    throw new \PDOException('Failed to insert option.');
                }
            }

            // Validate correct_answer against provided options
            if (!in_array($correct_answer, $option_letters)) {
                $this->pdo->rollBack();
                ResponseHelper::send(400, ['error' => 'Correct answer does not match any provided option letters.']);
                return;
            }

            // Ensure at least one option is marked as correct if question type is multiple_choice
            if ($question_type === 'multiple_choice' && !$found_correct_answer) {
                $this->pdo->rollBack();
                ResponseHelper::send(400, ['error' => 'Multiple choice questions must have at least one correct option.']);
                return;
            }

            $this->pdo->commit();

            ResponseHelper::send(201, ['message' => 'Question and options created successfully.', 'question_id' => $question_id]);

        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Database Error during question/option creation: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during question/option creation.']);
        }
    }

    // Handle retrieving all questions with pagination
    public function getAll($route_params = null, $request_data = null) {
        try {
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;
            $exam_subject_id = isset($request_data['exam_subject_id']) ? (int) $request_data['exam_subject_id'] : null;
            $topic_id = isset($request_data['topic_id']) ? (int) $request_data['topic_id'] : null;
            $is_active = isset($request_data['is_active']) ? filter_var($request_data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

            $conditions = [];
            $params = [];

            if ($exam_subject_id !== null) {
                $conditions[] = "q.exam_subject_id = :exam_subject_id";
                $params[':exam_subject_id'] = $exam_subject_id;
            }
            if ($topic_id !== null) {
                $conditions[] = "q.topic_id = :topic_id";
                $params[':topic_id'] = $topic_id;
            }
            if ($is_active !== null) {
                $conditions[] = "q.is_active = :is_active";
                $params[':is_active'] = $is_active;
            }

            $whereClause = '';
            if (!empty($conditions)) {
                $whereClause = "WHERE " . implode(" AND ", $conditions);
            }

            $paginationData = PaginationHelper::paginate(
                $this->pdo,
                "Questions q JOIN ExamSubjects es ON q.exam_subject_id = es.exam_subject_id LEFT JOIN Topics t ON q.topic_id = t.topic_id JOIN Users u ON q.created_by_user_id = u.user_id",
                null, // Let helper build count query
                $params,
                $page,
                $limit,
                $whereClause
            );

            $sql = "SELECT q.*, es.exam_id, es.subject_id, t.topic_name, u.full_name as created_by_user_name FROM Questions q
                    JOIN ExamSubjects es ON q.exam_subject_id = es.exam_subject_id
                    LEFT JOIN Topics t ON q.topic_id = t.topic_id
                    JOIN Users u ON q.created_by_user_id = u.user_id
                    {$whereClause} ORDER BY q.creation_date DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR));
            }
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($questions as &$question) {
                $sql_options = "SELECT option_id, option_letter, option_text, is_correct FROM QuestionOptions WHERE question_id = :question_id";
                $stmt_options = $this->pdo->prepare($sql_options);
                $stmt_options->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                $stmt_options->execute();
                $question['options'] = $stmt_options->fetchAll(PDO::FETCH_ASSOC);
                $question['is_active'] = isset($question['is_active']) ? (bool)$question['is_active'] : false; // Ensure boolean type, default to false if not set
            }
            unset($question);

            $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            $response_data = [
                'data' => $questions,
                'meta' => $paginationMeta['pagination']
            ];

            ResponseHelper::send(200, $response_data);

        } catch (PDOException $e) {
            error_log("Database Error during question retrieval: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during question retrieval.']);
        }
    }

    // Handle retrieving a single question by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing question ID.']);
            return;
        }

        $questionId = (int) $route_params[0];

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
                $sql_options = "SELECT option_id, option_letter, option_text, is_correct FROM QuestionOptions WHERE question_id = :question_id";
                $stmt_options = $this->pdo->prepare($sql_options);
                $stmt_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                $stmt_options->execute();
                $options = $stmt_options->fetchAll(PDO::FETCH_ASSOC);

                $question['options'] = $options;
                $question['is_active'] = (bool)$question['is_active']; // Ensure boolean type

                ResponseHelper::send(200, $question);
            } else {
                ResponseHelper::send(404, ['error' => 'Question not found.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error fetching question by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an existing question and its options
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing question ID.']);
            return;
        }

        $questionId = (int) $route_params[0];

        if (empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'No update data provided.']);
            return;
        }

        $this->pdo->beginTransaction();

        try {
            // Fetch current question to validate updates against existing data
            $stmt = $this->pdo->prepare("SELECT question_type FROM Questions WHERE question_id = :question_id");
            $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
            $stmt->execute();
            $current_question = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current_question) {
                $this->pdo->rollBack();
                ResponseHelper::send(404, ['error' => 'Question not found.']);
                return;
            }

            $question_updates = [];
            $question_params = [':question_id' => $questionId];

            // Validate and add fields to update
            if (isset($request_data['exam_subject_id'])) {
                $exam_subject_id = (int) $request_data['exam_subject_id'];
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ExamSubjects WHERE exam_subject_id = :exam_subject_id");
                $stmt->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() == 0) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(404, ['error' => 'Exam Subject not found.']);
                    return;
                }
                $question_updates[] = 'exam_subject_id = :exam_subject_id';
                $question_params[':exam_subject_id'] = $exam_subject_id;
            }
            if (isset($request_data['topic_id'])) {
                $topic_id = (int) $request_data['topic_id'];
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Topics WHERE topic_id = :topic_id");
                $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() == 0) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(404, ['error' => 'Topic not found.']);
                    return;
                }
                $question_updates[] = 'topic_id = :topic_id';
                $question_params[':topic_id'] = $topic_id;
            }
            if (isset($request_data['question_text'])) {
                $question_text = trim($request_data['question_text']);
                if (empty($question_text)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Question text cannot be empty.']);
                    return;
                }
                $question_updates[] = 'question_text = :question_text';
                $question_params[':question_text'] = $question_text;
            }
            if (isset($request_data['question_type'])) {
                $question_type = trim($request_data['question_type']);
                $allowed_question_types = ['multiple_choice', 'true_false', 'short_answer'];
                if (!in_array($question_type, $allowed_question_types)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Invalid question type.']);
                    return;
                }
                $question_updates[] = 'question_type = :question_type';
                $question_params[':question_type'] = $question_type;
            }
            if (isset($request_data['correct_answer'])) {
                $correct_answer = trim($request_data['correct_answer']);
                if (empty($correct_answer)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Correct answer cannot be empty.']);
                    return;
                }
                $question_updates[] = 'correct_answer = :correct_answer';
                $question_params[':correct_answer'] = $correct_answer;
            }
            if (array_key_exists('explanation', $request_data)) {
                $question_updates[] = 'explanation = :explanation';
                $question_params[':explanation'] = isset($request_data['explanation']) ? trim($request_data['explanation']) : null;
            }
            if (array_key_exists('difficulty_level', $request_data)) {
                $question_updates[] = 'difficulty_level = :difficulty_level';
                $question_params[':difficulty_level'] = isset($request_data['difficulty_level']) ? trim($request_data['difficulty_level']) : null;
            }
            if (isset($request_data['is_active'])) {
                $is_active_value = filter_var($request_data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($is_active_value === null) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => "Invalid 'is_active' value. Must be true or false."]);
                    return;
                }
                $question_updates[] = 'is_active = :is_active';
                $question_params[':is_active'] = $is_active_value;
            }

            if (!empty($question_updates)) {
                $sql_question_update = "UPDATE Questions SET " . implode(', ', $question_updates) . " WHERE question_id = :question_id";
                $stmt_question_update = $this->pdo->prepare($sql_question_update);
                foreach ($question_params as $key => $value) {
                    $param_type = PDO::PARAM_STR;
                    if (is_int($value)) $param_type = PDO::PARAM_INT;
                    else if (is_bool($value)) $param_type = PDO::PARAM_BOOL;
                    else if (is_null($value)) $param_type = PDO::PARAM_NULL;
                    $stmt_question_update->bindValue($key, $value, $param_type);
                }
                if (!$stmt_question_update->execute()) {
                    throw new \PDOException('Failed to update question fields.');
                }
            }

            // Update options if provided
            if (isset($request_data['options']) && is_array($request_data['options'])) {
                if (empty($request_data['options'])) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Options array cannot be empty if provided.']);
                    return;
                }

                // Delete existing options
                $sql_delete_options = "DELETE FROM QuestionOptions WHERE question_id = :question_id";
                $stmt_delete_options = $this->pdo->prepare($sql_delete_options);
                $stmt_delete_options->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                if (!$stmt_delete_options->execute()) {
                     throw new \PDOException('Failed to delete existing options.');
                }

                $sql_insert_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
                $stmt_insert_option = $this->pdo->prepare($sql_insert_option);

                $found_correct_answer = false;
                $option_letters = [];

                foreach ($request_data['options'] as $option) {
                    $option_required_fields = ['option_letter', 'option_text', 'is_correct'];
                    foreach ($option_required_fields as $field) {
                        if (!isset($option[$field])) {
                            $this->pdo->rollBack();
                            ResponseHelper::send(400, ['error' => "Missing required option field: {$field}."]);
                            return;
                        }
                    }

                    $option_letter = trim($option['option_letter']);
                    $option_text = trim($option['option_text']);
                    $is_correct = filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                    if (empty($option_letter) || empty($option_text)) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => 'Option letter and text cannot be empty.']);
                        return;
                    }
                    if ($is_correct === null) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => "Invalid 'is_correct' value for option {$option_letter}. Must be true or false."]);
                        return;
                    }

                    if (in_array($option_letter, $option_letters)) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => "Duplicate option letter found: {$option_letter}."]);
                        return;
                    }
                    $option_letters[] = $option_letter;

                    if ($is_correct) {
                        $found_correct_answer = true;
                    }

                    $stmt_insert_option->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                    $stmt_insert_option->bindParam(':option_letter', $option_letter);
                    $stmt_insert_option->bindParam(':option_text', $option_text);
                    $stmt_insert_option->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);

                    if (!$stmt_insert_option->execute()) {
                        throw new \PDOException('Failed to insert updated option.');
                    }
                }

                // Re-validate correct_answer against newly provided options
                $final_correct_answer = isset($request_data['correct_answer']) ? trim($request_data['correct_answer']) : $current_question['correct_answer'];
                if (!in_array($final_correct_answer, $option_letters)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Correct answer does not match any provided option letters after update.']);
                    return;
                }

                // Ensure at least one option is marked as correct if question type is multiple_choice
                $final_question_type = isset($request_data['question_type']) ? trim($request_data['question_type']) : $current_question['question_type'];
                if ($final_question_type === 'multiple_choice' && !$found_correct_answer) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Multiple choice questions must have at least one correct option after update.']);
                    return;
                }
            }

            $this->pdo->commit();

            ResponseHelper::send(200, ['message' => 'Question and options updated successfully.']);

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Database Error during question update: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle deleting an existing question and its options
    public function delete($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing question ID.']);
            return;
        }

        $questionId = (int) $route_params[0];

        // Check current status
        $currentStatusSql = "SELECT is_active FROM Questions WHERE question_id = :question_id";
        $statusStmt = $this->pdo->prepare($currentStatusSql);
        $statusStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $statusStmt->execute();
        $questionStatus = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$questionStatus) {
            ResponseHelper::send(404, ['error' => 'Question not found.']);
            return;
        }

        if ($questionStatus['is_active'] == 0) {
            ResponseHelper::send(200, ['message' => 'Question was already disabled.']);
            return;
        }

        $sql = "UPDATE Questions SET is_active = FALSE WHERE question_id = :question_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Question disabled (soft-deleted) successfully.']);
                } else {
                    ResponseHelper::send(404, ['error' => 'Question found but could not be disabled.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Question disabling (soft-deletion) failed.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error during question soft-deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle creating multiple questions and their options in bulk
    public function bulkCreate($request_data) {
        if (!is_array($request_data) || empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'Invalid or empty data array provided for bulk creation.']);
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $sql_question = "INSERT INTO Questions (exam_subject_id, topic_id, question_text, question_type, correct_answer, explanation, difficulty_level, created_by_user_id, is_active) VALUES (:exam_subject_id, :topic_id, :question_text, :question_type, :correct_answer, :explanation, :difficulty_level, :created_by_user_id, TRUE)";
            $stmt_question = $this->pdo->prepare($sql_question);

            $sql_option = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES (:question_id, :option_letter, :option_text, :is_correct)";
            $stmt_option = $this->pdo->prepare($sql_option);

            $created_question_ids = [];
            $allowed_question_types = ['multiple_choice', 'true_false', 'short_answer'];

            foreach ($request_data as $question_data) {
                $required_fields = ['exam_subject_id', 'question_text', 'question_type', 'correct_answer', 'options'];
                foreach ($required_fields as $field) {
                    if (!isset($question_data[$field])) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => "Missing required field for a question in bulk creation: {$field}."]);
                        return;
                    }
                }

                if (!is_array($question_data['options']) || empty($question_data['options'])) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Options array is missing or empty for a question in bulk creation.']);
                    return;
                }

                $exam_subject_id = (int) $question_data['exam_subject_id'];
                $topic_id = isset($question_data['topic_id']) ? (int) $question_data['topic_id'] : null;
                $question_text = trim($question_data['question_text']);
                $question_type = trim($question_data['question_type']);
                $correct_answer = trim($question_data['correct_answer']);
                $explanation = isset($question_data['explanation']) ? trim($question_data['explanation']) : null;
                $difficulty_level = isset($question_data['difficulty_level']) ? trim($question_data['difficulty_level']) : null;
                $created_by_user_id = 1; // Placeholder: Replace with actual authenticated user ID

                if (empty($question_text) || empty($question_type) || empty($correct_answer)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Question text, type, and correct answer cannot be empty for a question in bulk creation.']);
                    return;
                }

                // Validate exam_subject_id exists
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ExamSubjects WHERE exam_subject_id = :exam_subject_id");
                $stmt->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() == 0) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(404, ['error' => 'Exam Subject not found for a question in bulk creation.']);
                    return;
                }

                // Validate topic_id if provided
                if ($topic_id !== null) {
                    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Topics WHERE topic_id = :topic_id");
                    $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
                    $stmt->execute();
                    if ($stmt->fetchColumn() == 0) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(404, ['error' => 'Topic not found for a question in bulk creation.']);
                        return;
                    }
                }

                // Validate question_type
                if (!in_array($question_type, $allowed_question_types)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Invalid question type for a question in bulk creation.']);
                    return;
                }

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

                $found_correct_answer = false;
                $option_letters = [];

                // Insert options for the question
                foreach ($question_data['options'] as $option) {
                    $option_required_fields = ['option_letter', 'option_text', 'is_correct'];
                    foreach ($option_required_fields as $field) {
                        if (!isset($option[$field])) {
                            $this->pdo->rollBack();
                            ResponseHelper::send(400, ['error' => "Missing required option field for an option in bulk creation: {$field}."]);
                            return;
                        }
                    }

                    $option_letter = trim($option['option_letter']);
                    $option_text = trim($option['option_text']);
                    $is_correct = filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                    if (empty($option_letter) || empty($option_text)) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => 'Option letter and text cannot be empty for an option in bulk creation.']);
                        return;
                    }
                    if ($is_correct === null) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => "Invalid 'is_correct' value for an option in bulk creation. Must be true or false."]);
                        return;
                    }

                    if (in_array($option_letter, $option_letters)) {
                        $this->pdo->rollBack();
                        ResponseHelper::send(400, ['error' => "Duplicate option letter found for a question in bulk creation: {$option_letter}."]);
                        return;
                    }
                    $option_letters[] = $option_letter;

                    if ($is_correct) {
                        $found_correct_answer = true;
                    }

                    $stmt_option->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                    $stmt_option->bindParam(':option_letter', $option_letter);
                    $stmt_option->bindParam(':option_text', $option_text);
                    $stmt_option->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);

                    if (!$stmt_option->execute()) {
                        throw new \PDOException('Failed to insert option during bulk creation.');
                    }
                }

                // Validate correct_answer against provided options
                if (!in_array($correct_answer, $option_letters)) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Correct answer does not match any provided option letters for a question in bulk creation.']);
                    return;
                }

                // Ensure at least one option is marked as correct if question type is multiple_choice
                if ($question_type === 'multiple_choice' && !$found_correct_answer) {
                    $this->pdo->rollBack();
                    ResponseHelper::send(400, ['error' => 'Multiple choice questions must have at least one correct option for a question in bulk creation.']);
                    return;
                }
            }

            $this->pdo->commit();

            ResponseHelper::send(201, ['message' => 'Questions and options created successfully in bulk.', 'question_ids' => $created_question_ids]);

        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Database Error during bulk question/option creation: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during bulk question/option creation.']);
        }
    }

    // Handle updating multiple questions and their options in bulk
    public function bulkUpdate($request_data) {

    }

    // Handle bulk upload of questions via CSV
    public function uploadCsv($request_data) {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            ResponseHelper::send(400, ['error' => 'No file uploaded or upload error.']);
            return;
        }

        if (!isset($request_data['exam_subject_id'])) {
            ResponseHelper::send(400, ['error' => 'Missing exam_subject_id in request.']);
            return;
        }

        $exam_subject_id = (int) $request_data['exam_subject_id'];
        $topic_id = isset($request_data['topic_id']) ? (int) $request_data['topic_id'] : null;

        $file_tmp_path = $_FILES['csv_file']['tmp_name'];
        $file_type = mime_content_type($file_tmp_path);

        if ($file_type !== 'text/csv' && $file_type !== 'application/vnd.ms-excel') { // Common MIME types for CSV
            ResponseHelper::send(400, ['error' => 'Invalid file type. Please upload a CSV file.']);
            return;
        }

        $questions_data = [];
        $row_number = 0;

        if (($handle = fopen($file_tmp_path, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_number++;
                if ($row_number === 1) { // Skip header row
                    continue;
                }

                // Expected CSV format: question_text,correct_answer,option_A,option_B,option_C,option_D,explanation,difficulty_level
                // Ensure there are enough columns for multiple choice (at least 4 options + required fields)
                if (count($row) < 6) { // Minimum: question_text, correct_answer, option_A, option_B, option_C, option_D
                    ResponseHelper::send(400, ['error' => "Row {$row_number}: Missing required columns. Expected at least 6 columns."]);
                    fclose($handle);
                    return;
                }

                $question_text = trim($row[0]);
                $correct_answer = trim($row[1]);
                $explanation = !empty($row[6]) ? trim($row[6]) : null;
                $difficulty_level = !empty($row[7]) ? trim($row[7]) : null;

                if (empty($question_text) || empty($correct_answer)) {
                    ResponseHelper::send(400, ['error' => "Row {$row_number}: Question text or correct answer cannot be empty."]);
                    fclose($handle);
                    return;
                }

                $options = [];
                $option_letters = ['A', 'B', 'C', 'D', 'E', 'F']; // Support up to 6 options
                $has_correct_option = false;

                for ($i = 4; $i < count($row); $i++) {
                    if (isset($option_letters[$i - 4]) && !empty(trim($row[$i]))) {
                        $option_letter = $option_letters[$i - 4];
                        $option_text = trim($row[$i]);
                        $is_correct = ($option_letter === strtoupper($correct_answer));

                        $options[] = [
                            'option_letter' => $option_letter,
                            'option_text' => $option_text,
                            'is_correct' => $is_correct
                        ];
                        if ($is_correct) {
                            $has_correct_option = true;
                        }
                    }
                }

                if (empty($options)) {
                    ResponseHelper::send(400, ['error' => "Row {$row_number}: No options provided."]);
                    fclose($handle);
                    return;
                }

                if (!$has_correct_option) {
                    ResponseHelper::send(400, ['error' => "Row {$row_number}: Correct answer '{$correct_answer}' does not match any provided option letters."]);
                    fclose($handle);
                    return;
                }

                $questions_data[] = [
                    'exam_subject_id' => $exam_subject_id,
                    'topic_id' => $topic_id,
                    'question_text' => $question_text,
                    'question_type' => 'multiple_choice', // Fixed for now
                    'correct_answer' => $correct_answer,
                    'explanation' => $explanation,
                    'difficulty_level' => $difficulty_level,
                    'options' => $options
                ];
            }
            fclose($handle);
        } else {
            ResponseHelper::send(500, ['error' => 'Could not open uploaded CSV file.']);
            return;
        }

        if (empty($questions_data)) {
            ResponseHelper::send(400, ['error' => 'No valid question data found in the CSV file.']);
            return;
        }

        // Use the existing bulkCreate method
        $this->bulkCreate($questions_data);
    }

    // Handle deleting multiple questions and their options in bulk
    public function bulkDelete($request_data) {
        if (!is_array($request_data) || empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'Invalid or empty data array provided for bulk update.']);
            return;
        }

        // Ensure all elements in the array are integers (question IDs)
        foreach ($request_data as $id) {
            if (!is_int($id) || $id <= 0) {
                ResponseHelper::send(400, ['error' => 'Invalid question ID found in the bulk deletion request.']);
                return;
            }
        }

        $this->pdo->beginTransaction();

        try {
            // Soft delete questions by setting is_active to FALSE
            $placeholders = implode(',', array_fill(0, count($request_data), '?'));
            $sql_soft_delete_questions = "UPDATE Questions SET is_active = FALSE WHERE question_id IN ({$placeholders})";
            $stmt_soft_delete_questions = $this->pdo->prepare($sql_soft_delete_questions);

            foreach ($request_data as $index => $id) {
                $stmt_soft_delete_questions->bindValue(($index + 1), $id, PDO::PARAM_INT);
            }

            if ($stmt_soft_delete_questions->execute()) {
                $deleted_count = $stmt_soft_delete_questions->rowCount();
                $this->pdo->commit();
                ResponseHelper::send(200, ['message' => "{$deleted_count} questions soft-deleted successfully in bulk."]);
            } else {
                throw new \PDOException('Failed to soft-delete questions during bulk deletion.');
            }
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Database Error during bulk question soft-deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during bulk question soft-deletion.']);
        }
    }
}