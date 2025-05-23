<?php

class SubjectController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new subject
    public function create($data) {
        // Basic input validation
        if (!isset($data['subject_name'])) {
            ResponseHelper::send(400, ['error' => 'Missing required field (subject_name).']);
            return;
        }

        $subject_name = $data['subject_name'];
        $subject_code = $data['subject_code'] ?? null; // subject_code is optional
        $description = $data['description'] ?? null; // Description is optional

        // Prepare and execute the SQL statement to insert the new subject
        $sql = "INSERT INTO Subjects (subject_name, subject_code, description) VALUES (:subject_name, :subject_code, :description)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_name', $subject_name);
            $stmt->bindParam(':subject_code', $subject_code);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                // Subject creation successful
                ResponseHelper::send(201, ['message' => 'Subject created successfully.', 'subject_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error (less likely with exceptions enabled)
                ResponseHelper::send(500, ['error' => 'Subject creation failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate subject name/code)
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                ResponseHelper::send(409, ['error' => 'Subject name or code already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during subject creation: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle getting all subjects
    public function getAll($route_params = null, $request_data = null) {
        try {
            // Get pagination parameters from request data, with defaults
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Calculate pagination data
            $paginationData = PaginationHelper::paginate($this->pdo, 'Subjects', null, [], $page, $limit);

            // Fetch subjects with LIMIT and OFFSET
            $sql = "SELECT * FROM Subjects LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pagination metadata
            $baseUrl = $_SERVER['REQUEST_URI']; // Use current request URI as base URL
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            // Combine data and metadata
            $response_data = [
                'data' => $subjects,
                'meta' => $paginationMeta['pagination']
            ];

            ResponseHelper::send(200, $response_data);

        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during subject retrieval: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during subject retrieval.']);
        }
    }

    // Handle retrieving a single subject by ID
    public function getById($route_params, $request_data = null) {
        // Prepare and execute the SQL statement to retrieve a single subject by ID
        $sql = "SELECT subject_id, subject_name, subject_code, description FROM Subjects WHERE subject_id = :subject_id LIMIT 1";

        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing subject ID.']);
            return;
        }

        $subjectId = $route_params[0];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);
            $stmt->execute();

            $subject = $stmt->fetch();

            if ($subject) {
                // Return subject data
                ResponseHelper::send(200, $subject);
            } else {
                // Subject not found
                ResponseHelper::send(404, ['error' => 'Subject not found.']);
            }
        } catch (PDOException $e) {
            // Log database errors
            error_log("Database Error fetching subject by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an existing subject
    public function update($route_params, $request_data) {
        // Basic input validation
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing subject ID.']);
            return;
        }

        $subjectId = $route_params[0];

        if (!isset($request_data['subject_name']) && !isset($request_data['subject_code']) && !isset($request_data['description'])) {
            ResponseHelper::send(400, ['error' => 'No update data provided.']);
            return;
        }

        $updates = [];
        $params = [':subject_id' => $subjectId];

        if (isset($request_data['subject_name'])) {
            $updates[] = 'subject_name = :subject_name';
            $params[':subject_name'] = $request_data['subject_name'];
        }
        if (isset($request_data['subject_code'])) {
            $updates[] = 'subject_code = :subject_code';
            $params[':subject_code'] = $request_data['subject_code'];
        }
        if (isset($request_data['description'])) {
            $updates[] = 'description = :description';
            $params[':description'] = $request_data['description'];
        }

        if (empty($updates)) {
             ResponseHelper::send(400, ['error' => 'No valid fields to update.']);
             return;
        }

        $sql = "UPDATE Subjects SET " . implode(', ', $updates) . " WHERE subject_id = :subject_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Subject updated successfully.']);
                } else {
                    ResponseHelper::send(404, ['error' => 'Subject not found or no changes made.']);
                }
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'Subject update failed.']);
            }
        } catch (PDOException $e) {
            // Handle database errors (e.g., duplicate subject name/code)
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                ResponseHelper::send(409, ['error' => 'Subject name or code already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during subject update: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle deleting an existing subject
    public function delete($route_params, $request_data = null) {
        // Basic input validation
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing subject ID.']);
            return;
        }

        $subjectId = $route_params[0];

        // Prepare and execute the SQL statement to delete the subject
        $sql = "DELETE FROM Subjects WHERE subject_id = :subject_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Subject deleted successfully.']);
                } else {
                    ResponseHelper::send(404, ['error' => 'Subject not found.']);
                }
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'Subject deletion failed.']);
            }
        } catch (PDOException $e) {
            // Log database errors
            error_log("Database Error during subject deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

?>