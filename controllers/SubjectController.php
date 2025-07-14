<?php

require_once APP_ROOT . '/utils/ResponseHelper.php';
require_once APP_ROOT . '/utils/PaginationHelper.php';

class SubjectController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new subject
    public function create($request_data) {
        if (!isset($request_data['subject_name'], $request_data['subject_code'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (subject_name, subject_code).']);
            return;
        }

        $subject_name = trim($request_data['subject_name']);
        $subject_code = trim($request_data['subject_code']);
        $description = isset($request_data['description']) ? trim($request_data['description']) : null;

        if (empty($subject_name) || empty($subject_code)) {
            ResponseHelper::send(400, ['error' => 'Subject name and code cannot be empty.']);
            return;
        }

        $sql = "INSERT INTO Subjects (subject_name, subject_code, description, is_active) VALUES (:subject_name, :subject_code, :description, TRUE)"; // Default is_active to TRUE

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_name', $subject_name);
            $stmt->bindParam(':subject_code', $subject_code);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                ResponseHelper::send(201, ['message' => 'Subject created successfully.', 'subject_id' => $this->pdo->lastInsertId()]);
            } else {
                ResponseHelper::send(500, ['error' => 'Subject creation failed.']);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                ResponseHelper::send(409, ['error' => 'Subject name or code already exists.']);
            } else {
                error_log("Database Error during subject creation: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle getting all subjects
    public function getAll($route_params = null, $request_data = null) {
        try {
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Add filter for active subjects if requested
            $conditions = "";
            $params = [];
            if (isset($request_data['active_only']) && $request_data['active_only'] == 'true') {
                $conditions = "is_active = TRUE";
            }

            // Corrected call to PaginationHelper::paginate
            // Pass $conditions as the $whereClause (7th argument) and null for $countQuery (3rd argument)
            // Pass $params as the $params (4th argument)
            $paginationData = PaginationHelper::paginate(
                $this->pdo,
                'Subjects',
                null, // $countQuery (let helper build it)
                $params, // $params for binding to $whereClause
                $page,
                $limit,
                $conditions // $whereClause
            );

            $sql = "SELECT * FROM Subjects";
            if (!empty($conditions)) {
                $sql .= " WHERE {$conditions}";
            }
            $sql .= " ORDER BY creation_date DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            // Bind additional params if any (for conditions) - though not used in this specific case yet
            // foreach ($params as $key => $value) {
            //    $stmt->bindValue($key, $value);
            // }
            $stmt->execute();
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ensure is_active is boolean for all fetched subjects
            foreach ($subjects as &$subject) {
                $subject['is_active'] = (bool)$subject['is_active'];
            }
            unset($subject);


            $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            $response_data = [
                'data' => $subjects,
                'meta' => $paginationMeta['pagination']
            ];

            ResponseHelper::send(200, $response_data);

        } catch (PDOException $e) {
            error_log("Database Error during subject retrieval: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during subject retrieval.']);
        }
    }

    // Handle getting a single subject by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing subject ID.']);
            return;
        }

        $subject_id = $route_params[0];

        $sql = "SELECT subject_id, subject_name, subject_code, description, is_active, creation_date FROM Subjects WHERE subject_id = :subject_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->execute();

            $subject = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($subject) {
                $subject['is_active'] = (bool)$subject['is_active']; // Ensure boolean type
                ResponseHelper::send(200, $subject);
            } else {
                ResponseHelper::send(404, ['error' => 'Subject not found.']);
            }
        } catch (\PDOException $e) {
            error_log("Database Error fetching subject by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating a subject
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing subject ID.']);
            return;
        }

        $subject_id = $route_params[0];

        if (empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'No update data provided.']);
            return;
        }

        $set_clauses = [];
        $params_to_bind = [];

        if (isset($request_data['subject_name'])) {
            $subject_name = trim($request_data['subject_name']);
            if(empty($subject_name)){
                ResponseHelper::send(400, ['error' => 'Subject name cannot be empty.']);
                return;
            }
            $set_clauses[] = 'subject_name = :subject_name';
            $params_to_bind[':subject_name'] = $subject_name;
        }
        if (isset($request_data['subject_code'])) {
            $subject_code = trim($request_data['subject_code']);
            if(empty($subject_code)){
                ResponseHelper::send(400, ['error' => 'Subject code cannot be empty.']);
                return;
            }
            $set_clauses[] = 'subject_code = :subject_code';
            $params_to_bind[':subject_code'] = $subject_code;
        }
        if (array_key_exists('description', $request_data)) { // Use array_key_exists to allow setting description to null or empty string
            $set_clauses[] = 'description = :description';
            $params_to_bind[':description'] = isset($request_data['description']) ? trim($request_data['description']) : null;
        }

        // This is the critical part for toggling active status via PUT
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

        $sql = "UPDATE Subjects SET " . implode(', ', $set_clauses) . " WHERE subject_id = :subject_id";

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

            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Subject updated successfully.']);
                } else {
                    $checkStmt = $this->pdo->prepare("SELECT 1 FROM Subjects WHERE subject_id = :subject_id");
                    $checkStmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
                    $checkStmt->execute();
                    if ($checkStmt->fetch()) {
                        ResponseHelper::send(200, ['message' => 'No changes made to the subject.']);
                    } else {
                        ResponseHelper::send(404, ['error' => 'Subject not found.']);
                    }
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Subject update failed.']);
            }
        } catch (\PDOException $e) {
             if ($e->getCode() === '23000') {
                 ResponseHelper::send(409, ['error' => 'Subject name or code already exists.']);
             } else {
                 error_log("Database Error during subject update: " . $e->getMessage());
                 ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
             }
         }
     }

    // This method is specifically for the DELETE HTTP verb to perform a soft delete (set is_active = FALSE).
    public function delete($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing subject ID.']);
            return;
        }

        $subject_id = $route_params[0];

        // We should only proceed if the subject is currently active.
        // Fetch current status first.
        $currentStatusSql = "SELECT is_active FROM Subjects WHERE subject_id = :subject_id";
        $statusStmt = $this->pdo->prepare($currentStatusSql);
        $statusStmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $statusStmt->execute();
        $subjectStatus = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$subjectStatus) {
            ResponseHelper::send(404, ['error' => 'Subject not found.']);
            return;
        }

        if ($subjectStatus['is_active'] == 0) { // Already inactive
            ResponseHelper::send(200, ['message' => 'Subject was already disabled.']);
            return;
        }

        // Proceed to set is_active to FALSE
        $sql = "UPDATE Subjects SET is_active = FALSE WHERE subject_id = :subject_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Subject disabled (soft-deleted) successfully.']);
                } else {
                    // This case should ideally not be reached if we passed the checks above.
                    // It might mean the subject was deleted/modified by another process between checks.
                    ResponseHelper::send(404, ['error' => 'Subject found but could not be disabled. It might have been modified or deleted by another process.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Subject disabling (soft-deletion) failed.']);
            }
        } catch (\PDOException $e) {
            error_log("Database Error during subject soft-deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }
}

?>
