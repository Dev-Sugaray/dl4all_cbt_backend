<?php

// require_once APP_ROOT . '/config/database.php'; // Include database connection

class ExamController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new exam
    public function create($data) {
        // Basic input validation
        if (!isset($data['exam_name'], $data['exam_abbreviation'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields (exam_name, exam_abbreviation).']);
            return;
        }

        $exam_name = $data['exam_name'];
        $exam_abbreviation = $data['exam_abbreviation'];
        $description = $data['description'] ?? null; // Description is optional

        // Prepare and execute the SQL statement to insert the new exam
        $sql = "INSERT INTO Exams (exam_name, exam_abbreviation, description) VALUES (:exam_name, :exam_abbreviation, :description)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_name', $exam_name);
            $stmt->bindParam(':exam_abbreviation', $exam_abbreviation);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                // Exam creation successful
                http_response_code(201); // 201 Created
                echo json_encode(['message' => 'Exam created successfully.', 'exam_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error (less likely with exceptions enabled)
                http_response_code(500);
                echo json_encode(['error' => 'Exam creation failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate exam name/abbreviation)
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                http_response_code(409); // 409 Conflict
                echo json_encode(['error' => 'Exam name or abbreviation already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during exam creation: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle getting all exams
    public function getAll() {
        // Prepare and execute the SQL statement to retrieve all active exams
        $sql = "SELECT exam_id, exam_name, exam_abbreviation, description, is_active, creation_date FROM Exams WHERE is_active = TRUE";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $exams = $stmt->fetchAll();

            // Return list of exams
            http_response_code(200); // OK
            echo json_encode($exams);
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching all exams: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }

    // Handle getting a single exam by ID
    public function getById($examId) {
        // Prepare and execute the SQL statement to retrieve a single exam by ID
        $sql = "SELECT exam_id, exam_name, exam_abbreviation, description, is_active, creation_date FROM Exams WHERE exam_id = :exam_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->execute();

            $exam = $stmt->fetch();

            if ($exam) {
                // Return exam data
                http_response_code(200); // OK
                echo json_encode($exam);
            } else {
                // Exam not found
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Exam not found.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching exam by ID: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an exam
    public function update($examId, $data) {
        // Basic input validation - check if any data is provided for update
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No update data provided.']);
            return;
        }

        // Build the SQL query dynamically based on provided data
        $set_clauses = [];
        $params = [':exam_id' => $examId];

        if (isset($data['exam_name'])) {
            $set_clauses[] = 'exam_name = :exam_name';
            $params[':exam_name'] = $data['exam_name'];
        }
        if (isset($data['exam_abbreviation'])) {
            $set_clauses[] = 'exam_abbreviation = :exam_abbreviation';
            $params[':exam_abbreviation'] = $data['exam_abbreviation'];
        }
        if (isset($data['description'])) {
            $set_clauses[] = 'description = :description';
            $params[':description'] = $data['description'];
        }
        if (isset($data['is_active'])) {
             // Ensure is_active is a boolean
            $set_clauses[] = 'is_active = :is_active';
            $params[':is_active'] = (bool) $data['is_active'];
        }

        // If no valid fields to update, return error
        if (empty($set_clauses)) {
             http_response_code(400);
             echo json_encode(['error' => 'No valid fields provided for update.']);
             return;
        }

        $sql = "UPDATE Exams SET " . implode(', ', $set_clauses) . " WHERE exam_id = :exam_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind parameters
            foreach ($params as $key => $value) {
                // Determine parameter type (simplified, could be more robust)
                $param_type = PDO::PARAM_STR;
                if (is_int($value)) $param_type = PDO::PARAM_INT;
                if (is_bool($value)) $param_type = PDO::PARAM_BOOL;
                if (is_null($value)) $param_type = PDO::PARAM_NULL;

                $stmt->bindParam($key, $params[$key], $param_type);
            }

            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Exam updated successfully.']);
                } else {
                    // No rows affected, likely exam_id not found
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Exam not found.']);
                }
            } else {
                // Handle execution error
                http_response_code(500);
                echo json_encode(['error' => 'Exam update failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate exam name/abbreviation)
             if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                http_response_code(409); // 409 Conflict
                echo json_encode(['error' => 'Exam name or abbreviation already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during exam update: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle deleting an exam (soft delete)
    public function delete($examId) {
        // Prepare and execute the SQL statement to soft delete the exam
        $sql = "UPDATE Exams SET is_active = FALSE WHERE exam_id = :exam_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                 // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK (or 204 No Content)
                    echo json_encode(['message' => 'Exam soft-deleted successfully.']);
                } else {
                    // No rows affected, likely exam_id not found
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Exam not found.']);
                }
            } else {
                // Handle execution error
                http_response_code(500);
                echo json_encode(['error' => 'Exam soft-deletion failed.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error during exam soft-deletion: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.']);
        }
    }
}

?>