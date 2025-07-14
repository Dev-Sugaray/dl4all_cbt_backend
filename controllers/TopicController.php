<?php

require_once APP_ROOT . '/utils/ResponseHelper.php';
require_once APP_ROOT . '/utils/PaginationHelper.php';

class TopicController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new topic
    public function create($request_data) {
        if (!isset($request_data['topic_name'], $request_data['subject_id'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (topic_name, subject_id).']);
            return;
        }

        $topic_name = trim($request_data['topic_name']);
        $subject_id = $request_data['subject_id'];
        $description = isset($request_data['description']) ? trim($request_data['description']) : null;

        if (empty($topic_name)) {
            ResponseHelper::send(400, ['error' => 'Topic name cannot be empty.']);
            return;
        }

        $sql = "INSERT INTO Topics (topic_name, subject_id, description, is_active) VALUES (:topic_name, :subject_id, :description, TRUE)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':topic_name', $topic_name);
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                ResponseHelper::send(201, ['message' => 'Topic created successfully.', 'topic_id' => $this->pdo->lastInsertId()]);
            } else {
                ResponseHelper::send(500, ['error' => 'Topic creation failed.']);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                ResponseHelper::send(409, ['error' => 'Topic name already exists for this subject.']);
            } else {
                error_log("Database Error during topic creation: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle getting all topics
    public function getAll($route_params = null, $request_data = null) {
        try {
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            $paginationData = PaginationHelper::paginate($this->pdo, 'Topics', null, [], $page, $limit);

            $sql = "SELECT t.*, s.subject_name FROM Topics t JOIN Subjects s ON t.subject_id = s.subject_id ORDER BY t.creation_date DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($topics as &$topic) {
                $topic['is_active'] = (bool)$topic['is_active'];
            }
            unset($topic);

            $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            $response_data = [
                'data' => $topics,
                'meta' => $paginationMeta['pagination']
            ];

            ResponseHelper::send(200, $response_data);

        } catch (PDOException $e) {
            error_log("Database Error during topic retrieval: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during topic retrieval.']);
        }
    }

    // Handle getting a single topic by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing topic ID.']);
            return;
        }

        $topic_id = $route_params[0];

        $sql = "SELECT t.*, s.subject_name FROM Topics t JOIN Subjects s ON t.subject_id = s.subject_id WHERE t.topic_id = :topic_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
            $stmt->execute();

            $topic = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($topic) {
                $topic['is_active'] = (bool)$topic['is_active'];
                ResponseHelper::send(200, $topic);
            } else {
                ResponseHelper::send(404, ['error' => 'Topic not found.']);
            }
        } catch (\PDOException $e) {
            error_log("Database Error fetching topic by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating a topic
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing topic ID.']);
            return;
        }

        $topic_id = $route_params[0];

        if (empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'No update data provided.']);
            return;
        }

        $set_clauses = [];
        $params_to_bind = [];

        if (isset($request_data['topic_name'])) {
            $topic_name = trim($request_data['topic_name']);
            if(empty($topic_name)){
                ResponseHelper::send(400, ['error' => 'Topic name cannot be empty.']);
                return;
            }
            $set_clauses[] = 'topic_name = :topic_name';
            $params_to_bind[':topic_name'] = $topic_name;
        }
        if (isset($request_data['subject_id'])) {
            $set_clauses[] = 'subject_id = :subject_id';
            $params_to_bind[':subject_id'] = $request_data['subject_id'];
        }
        if (array_key_exists('description', $request_data)) {
            $set_clauses[] = 'description = :description';
            $params_to_bind[':description'] = isset($request_data['description']) ? trim($request_data['description']) : null;
        }
        if (isset($request_data['is_active'])) {
            $is_active_value = filter_var($request_data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($is_active_value === null) {
                ResponseHelper::send(400, ['error' => "Invalid 'is_active' value. Must be true or false."]);
                return;
            }
            $set_clauses[] = 'is_active = :is_active';
            $params_to_bind[':is_active'] = $is_active_value;
        }

        if (empty($set_clauses)) {
             ResponseHelper::send(400, ['error' => 'No valid fields provided for update.']);
             return;
        }

        $sql = "UPDATE Topics SET " . implode(', ', $set_clauses) . " WHERE topic_id = :topic_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($params_to_bind as $key => &$value) {
                $param_type = PDO::PARAM_STR;
                if (is_int($value)) $param_type = PDO::PARAM_INT;
                else if (is_bool($value)) $param_type = PDO::PARAM_BOOL;
                else if (is_null($value)) $param_type = PDO::PARAM_NULL;
                $stmt->bindParam($key, $value, $param_type);
            }
            unset($value);

            $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Topic updated successfully.']);
                } else {
                    $checkStmt = $this->pdo->prepare("SELECT 1 FROM Topics WHERE topic_id = :topic_id");
                    $checkStmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
                    $checkStmt->execute();
                    if ($checkStmt->fetch()) {
                        ResponseHelper::send(200, ['message' => 'No changes made to the topic.']);
                    } else {
                        ResponseHelper::send(404, ['error' => 'Topic not found.']);
                    }
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Topic update failed.']);
            }
        } catch (\PDOException $e) {
             if ($e->getCode() === '23000') {
                 ResponseHelper::send(409, ['error' => 'Topic name already exists for this subject.']);
             } else {
                 error_log("Database Error during topic update: " . $e->getMessage());
                 ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
             }
         }
     }

    // Handle deleting a topic (soft delete)
    public function delete($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing topic ID.']);
            return;
        }

        $topic_id = $route_params[0];

        $sql = "UPDATE Topics SET is_active = FALSE WHERE topic_id = :topic_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Topic disabled (soft-deleted) successfully.']);
                } else {
                    ResponseHelper::send(404, ['error' => 'Topic not found or already disabled.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Topic disabling failed.']);
            }
        } catch (\PDOException $e) {
            error_log("Database Error during topic soft-deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }
}

?>