<?php

// require_once APP_ROOT . '/config/database.php'; // Include database connection

class TopicController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new topic
    public function create($data) {
        // Basic input validation
        if (!isset($data['subject_id'], $data['topic_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields (subject_id, topic_name).']);
            return;
        }

        $subject_id = $data['subject_id'];
        $topic_name = $data['topic_name'];
        $description = $data['description'] ?? null; // Description is optional

        // Prepare and execute the SQL statement to insert the new topic
        $sql = "INSERT INTO Topics (subject_id, topic_name, description) VALUES (:subject_id, :topic_name, :description)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindParam(':topic_name', $topic_name);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                // Topic creation successful
                http_response_code(201); // 201 Created
                echo json_encode(['message' => 'Topic created successfully.', 'topic_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error (less likely with exceptions enabled)
                http_response_code(500);
                echo json_encode(['error' => 'Topic creation failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., foreign key constraint or duplicate topic name for a subject)
            // A unique index on (subject_id, topic_name) would enforce uniqueness.
            if ($e->getCode() === '23000') { // Integrity constraint violation
                http_response_code(409); // 409 Conflict
                echo json_encode(['error' => 'Topic name already exists for this subject or invalid subject ID.']);
            } else {
                // Log other database errors
                error_log("Database Error during topic creation: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle retrieving all topics with pagination
    public function getAll($route_params = null, $request_data = null) {
        try {
            // Get pagination parameters from request data, with defaults
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Calculate pagination data
            $paginationData = PaginationHelper::paginate($this->pdo, 'Topics', null, [], $page, $limit);

            // Fetch topics with LIMIT and OFFSET
            $sql = "SELECT t.*, s.subject_name FROM Topics t JOIN Subjects s ON t.subject_id = s.subject_id LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pagination metadata
            $baseUrl = $_SERVER['REQUEST_URI']; // Use current request URI as base URL
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            // Combine data and metadata
            $response_data = [
                'data' => $topics,
                'meta' => $paginationMeta['pagination']
            ];

            http_response_code(200); // OK
            echo json_encode($response_data);

        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during topic retrieval: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred during topic retrieval.']);
        }
    }

    // Handle retrieving a single topic by ID
    public function getById($route_params, $request_data = null) {
        // Prepare and execute the SQL statement to retrieve a single topic by ID
        $sql = "SELECT t.topic_id, t.subject_id, s.subject_name, t.topic_name, t.description FROM Topics t JOIN Subjects s ON t.subject_id = s.subject_id WHERE t.topic_id = :topic_id LIMIT 1";

        if (!isset($route_params[0])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing topic ID.']);
            return;
        }

        $topicId = $route_params[0];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);
            $stmt->execute();

            $topic = $stmt->fetch();

            if ($topic) {
                // Return topic data
                http_response_code(200); // OK
                echo json_encode($topic);
            } else {
                // Topic not found
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Topic not found.']);
            }
        } catch (PDOException $e) {
            // Log database errors
            error_log("Database Error fetching topic by ID: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating a topic
    public function update($route_params, $request_data) {
        // Basic input validation
        if (!isset($route_params[0])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing topic ID.']);
            return;
        }

        $topicId = $route_params[0];

        if (!isset($request_data['subject_id']) && !isset($request_data['topic_name']) && !isset($request_data['description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No update data provided.']);
            return;
        }

        $updates = [];
        $params = [':topic_id' => $topicId];

        if (isset($request_data['subject_id'])) {
            $updates[] = 'subject_id = :subject_id';
            $params[':subject_id'] = $request_data['subject_id'];
        }
        if (isset($request_data['topic_name'])) {
            $updates[] = 'topic_name = :topic_name';
            $params[':topic_name'] = $request_data['topic_name'];
        }
        if (isset($request_data['description'])) {
            $updates[] = 'description = :description';
            $params[':description'] = $request_data['description'];
        }

        if (empty($updates)) {
             http_response_code(400);
             echo json_encode(['error' => 'No valid fields to update.']);
             return;
        }

        $sql = "UPDATE Topics SET " . implode(', ', $updates) . " WHERE topic_id = :topic_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Topic updated successfully.']);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Topic not found or no changes made.']);
                }
            } else {
                // Handle execution error
                http_response_code(500);
                echo json_encode(['error' => 'Topic update failed.']);
            }
        } catch (PDOException $e) {
            // Handle database errors (e.g., foreign key constraint or duplicate topic name for a subject)
            if ($e->getCode() === '23000') { // Integrity constraint violation
                http_response_code(409); // 409 Conflict
                echo json_encode(['error' => 'Topic name already exists for this subject or invalid subject ID.']);
            } else {
                // Log other database errors
                error_log("Database Error during topic update: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle deleting a topic
    public function delete($route_params, $request_data = null) {
        // Basic input validation
        if (!isset($route_params[0])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing topic ID.']);
            return;
        }

        $topicId = $route_params[0];

        // Prepare and execute the SQL statement to delete the topic
        $sql = "DELETE FROM Topics WHERE topic_id = :topic_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK or 204 No Content
                    echo json_encode(['message' => 'Topic deleted successfully.']);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Topic not found.']);
                }
            } else {
                // Handle execution error
                http_response_code(500);
                echo json_encode(['error' => 'Topic deletion failed.']);
            }
        } catch (PDOException $e) {
            // Log database errors
            error_log("Database Error during topic deletion: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }
}

?>