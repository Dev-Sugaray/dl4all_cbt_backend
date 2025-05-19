<?php

// require_once APP_ROOT . '/config/database.php'; // Include database connection

class SubjectController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new subject
    public function create($data) {
        // Basic input validation
        if (!isset($data['subject_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field (subject_name).']);
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
                http_response_code(201); // 201 Created
                echo json_encode(['message' => 'Subject created successfully.', 'subject_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error (less likely with exceptions enabled)
                http_response_code(500);
                echo json_encode(['error' => 'Subject creation failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate subject name/code)
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                http_response_code(409); // 409 Conflict
                echo json_encode(['error' => 'Subject name or code already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during subject creation: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle getting all subjects
    public function getAll() {
        // Prepare and execute the SQL statement to retrieve all subjects
        $sql = "SELECT subject_id, subject_name, subject_code, description FROM Subjects";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $subjects = $stmt->fetchAll();

            // Return list of subjects
            http_response_code(200); // OK
            echo json_encode($subjects);
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching all subjects: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }

    // Handle getting a single subject by ID
    public function getById($subjectId) {
        // Prepare and execute the SQL statement to retrieve a single subject by ID
        $sql = "SELECT subject_id, subject_name, subject_code, description FROM Subjects WHERE subject_id = :subject_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);
            $stmt->execute();

            $subject = $stmt->fetch();

            if ($subject) {
                // Return subject data
                http_response_code(200); // OK
                echo json_encode($subject);
            } else {
                // Subject not found
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Subject not found.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching subject by ID: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating a subject
    public function update($subjectId, $data) {
        // Basic input validation - check if any data is provided for update
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No update data provided.']);
            return;
        }

        // Build the SQL query dynamically based on provided data
        $set_clauses = [];
        $params = [':subject_id' => $subjectId];

        if (isset($data['subject_name'])) {
            $set_clauses[] = 'subject_name = :subject_name';
            $params[':subject_name'] = $data['subject_name'];
        }
        if (isset($data['subject_code'])) {
            $set_clauses[] = 'subject_code = :subject_code';
            $params[':subject_code'] = $data['subject_code'];
        }
        if (isset($data['description'])) {
            $set_clauses[] = 'description = :description';
            $params[':description'] = $data['description'];
        }

        // If no valid fields to update, return error
        if (empty($set_clauses)) {
             http_response_code(400);
             echo json_encode(['error' => 'No valid fields provided for update.']);
             return;
        }

        $sql = "UPDATE Subjects SET " . implode(', ', $set_clauses) . " WHERE subject_id = :subject_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind parameters
            foreach ($params as $key => $value) {
                // Determine parameter type (simplified, could be more robust)
                $param_type = PDO::PARAM_STR;
                if (is_int($value)) $param_type = PDO::PARAM_INT;
                if (is_null($value)) $param_type = PDO::PARAM_NULL;

                $stmt->bindParam($key, $params[$key], $param_type);
            }

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Subject updated successfully.']);
                } else {
                    // No rows affected, likely subject_id not found
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Subject not found.']);
                }
            } else {
                // Handle execution error
                http_response_code(500);
                echo json_encode(['error' => 'Subject update failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate subject name/code)
             if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                http_response_code(409); // 409 Conflict
                echo json_encode(['error' => 'Subject name or code already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during subject update: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle deleting a subject
    public function delete($subjectId) {
        // Prepare and execute the SQL statement to delete the subject
        $sql = "DELETE FROM Subjects WHERE subject_id = :subject_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                 // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK (or 204 No Content)
                    echo json_encode(['message' => 'Subject deleted successfully.']);
                } else {
                    // No rows affected, likely subject_id not found
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Subject not found.']);
                }
            } else {
                // Handle execution error
                http_response_code(500);
                echo json_encode(['error' => 'Subject deletion failed.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error during subject deletion: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }
}

?>