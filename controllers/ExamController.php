<?php

// require_once APP_ROOT . '/config/database.php'; // Include database connection
require_once APP_ROOT . '/utils/ResponseHelper.php';

class ExamController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new exam
    public function create($request_data) {
        // Basic input validation
        if (!isset($request_data['exam_name'], $request_data['exam_abbreviation'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (exam_name, exam_abbreviation).']);
            return;
        }

        $exam_name = $request_data['exam_name'];
        $exam_abbreviation = $request_data['exam_abbreviation'];
        $description = $request_data['description'] ?? null; // Description is optional

        // Prepare and execute the SQL statement to insert the new exam
        $sql = "INSERT INTO Exams (exam_name, exam_abbreviation, description) VALUES (:exam_name, :exam_abbreviation, :description)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_name', $exam_name);
            $stmt->bindParam(':exam_abbreviation', $exam_abbreviation);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                // Exam creation successful
                ResponseHelper::send(201, ['message' => 'Exam created successfully.', 'exam_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error (less likely with exceptions enabled)
                ResponseHelper::send(500, ['error' => 'Exam creation failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate exam name/abbreviation)
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                ResponseHelper::send(409, ['error' => 'Exam name or abbreviation already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during exam creation: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
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

            $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Return list of exams
            ResponseHelper::send(200, $exams);
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching all exams: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle getting a single exam by ID
    public function getById($route_params) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing exam ID.']);
            return;
        }

        $exam_id = $route_params[0];
        
        // Prepare and execute the SQL statement to retrieve a single exam by ID
        $sql = "SELECT exam_id, exam_name, exam_abbreviation, description, is_active, creation_date FROM Exams WHERE exam_id = :exam_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $exam_id, PDO::PARAM_INT);
            $stmt->execute();

            $exam = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exam) {
                // Return exam data
                ResponseHelper::send(200, $exam);
            } else {
                // Exam not found
                ResponseHelper::send(404, ['error' => 'Exam not found.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error fetching exam by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Handle updating an exam
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing exam ID.']);
            return;
        }

        $exam_id = $route_params[0];
        
        // Basic input validation - check if any data is provided for update
        if (empty($request_data)) {
            ResponseHelper::send(400, ['error' => 'No update data provided.']);
            return;
        }

        // Build the SQL query dynamically based on provided data
        $set_clauses = [];
        $params = [':exam_id' => $exam_id];

        if (isset($request_data['exam_name'])) {
            $set_clauses[] = 'exam_name = :exam_name';
            $params[':exam_name'] = $request_data['exam_name'];
        }
        if (isset($request_data['exam_abbreviation'])) {
            $set_clauses[] = 'exam_abbreviation = :exam_abbreviation';
            $params[':exam_abbreviation'] = $request_data['exam_abbreviation'];
        }
        if (isset($request_data['description'])) {
            $set_clauses[] = 'description = :description';
            $params[':description'] = $request_data['description'];
        }
        if (isset($request_data['is_active'])) {
             // Ensure is_active is a boolean
            $set_clauses[] = 'is_active = :is_active';
            $params[':is_active'] = (bool) $request_data['is_active'];
        }

        // If no valid fields to update, return error
        if (empty($set_clauses)) {
             ResponseHelper::send(400, ['error' => 'No valid fields provided for update.']);
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
                    ResponseHelper::send(200, ['message' => 'Exam updated successfully.']);
                } else {
                    // No rows affected, likely exam_id not found
                    ResponseHelper::send(404, ['error' => 'Exam not found.']);
                }
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'Exam update failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate exam name/abbreviation)
             if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry)
                ResponseHelper::send(409, ['error' => 'Exam name or abbreviation already exists.']);
            } else {
                // Log other database errors
                error_log("Database Error during exam update: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle deleting an exam (soft delete)
    public function delete($route_params) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing exam ID.']);
            return;
        }

        $exam_id = $route_params[0];
        
        // Prepare and execute the SQL statement to soft delete the exam
        $sql = "UPDATE Exams SET is_active = FALSE WHERE exam_id = :exam_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $exam_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                 // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Exam soft-deleted successfully.']);
                } else {
                    // No rows affected, likely exam_id not found
                    ResponseHelper::send(404, ['error' => 'Exam not found.']);
                }
            } else {
                // Handle execution error
                ResponseHelper::send(500, ['error' => 'Exam soft-deletion failed.']);
            }
        } catch (\PDOException $e) {
            // Log database errors
            error_log("Database Error during exam soft-deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }
}

?>