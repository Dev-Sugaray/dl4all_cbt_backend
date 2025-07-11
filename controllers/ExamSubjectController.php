<?php

class ExamSubjectController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Handle creating a new ExamSubject
    public function create($data) {
        // Basic input validation
        if (!isset($data['exam_id'], $data['subject_id'], $data['number_of_questions'], $data['time_limit_seconds'])) {
            ResponseHelper::send(400, ['error' => 'Missing required fields (exam_id, subject_id, number_of_questions, time_limit_seconds).']);
            return;
        }

        $exam_id = $data['exam_id'];
        $subject_id = $data['subject_id'];
        $number_of_questions = $data['number_of_questions'];
        $time_limit_seconds = $data['time_limit_seconds'];
        $scoring_scheme = $data['scoring_scheme'] ?? null; // scoring_scheme is optional

        // Prepare and execute the SQL statement to insert the new exam subject
        $sql = "INSERT INTO ExamSubjects (exam_id, subject_id, number_of_questions, time_limit_seconds, scoring_scheme) VALUES (:exam_id, :subject_id, :number_of_questions, :time_limit_seconds, :scoring_scheme)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $exam_id, PDO::PARAM_INT);
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindParam(':number_of_questions', $number_of_questions, PDO::PARAM_INT);
            $stmt->bindParam(':time_limit_seconds', $time_limit_seconds, PDO::PARAM_INT);
            $stmt->bindParam(':scoring_scheme', $scoring_scheme);

            if ($stmt->execute()) {
                // Exam subject creation successful
                ResponseHelper::send(201, ['message' => 'Exam subject created successfully.', 'exam_subject_id' => $this->pdo->lastInsertId()]);
            } else {
                // Handle execution error (less likely with exceptions enabled)
                ResponseHelper::send(500, ['error' => 'Exam subject creation failed.']);
            }
        } catch (\PDOException $e) {
            // Handle database errors (e.g., duplicate exam_id, subject_id combination)
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate entry or foreign key constraint)
                 // Check if it's a duplicate entry for exam_id and subject_id
                 // A more specific check might be needed depending on the exact error message or a unique index
                ResponseHelper::send(409, ['error' => 'Exam subject combination already exists or invalid exam/subject ID.']);
            } else {
                // Log other database errors
                error_log("Database Error during exam subject creation: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Handle retrieving all ExamSubjects with pagination
    public function getAll($route_params = null, $request_data = null) {
        try {
            // Get pagination parameters from request data, with defaults
            $page = isset($request_data['page']) ? (int) $request_data['page'] : 1;
            $limit = isset($request_data['limit']) ? (int) $request_data['limit'] : 10;

            // Calculate pagination data
            $paginationData = PaginationHelper::paginate($this->pdo, 'ExamSubjects', null, [], $page, $limit);

            // Fetch ExamSubjects with LIMIT and OFFSET
            $sql = "SELECT es.*, e.exam_name, s.subject_name FROM ExamSubjects es JOIN Exams e ON es.exam_id = e.exam_id JOIN Subjects s ON es.subject_id = s.subject_id LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $paginationData['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $paginationData['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $examSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pagination metadata
            $baseUrl = $_SERVER['REQUEST_URI']; // Use current request URI as base URL
            $paginationMeta = PaginationHelper::getPaginationMeta($paginationData, $baseUrl);

            // Combine data and metadata
            $response_data = [
                'data' => $examSubjects,
                'meta' => $paginationMeta['pagination']
            ];

            ResponseHelper::send(200, $response_data);

        } catch (PDOException $e) {
            // Handle database errors
            error_log("Database Error during ExamSubject retrieval: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred during ExamSubject retrieval.']);
        }
    }

    // Handle retrieving a single ExamSubject by ID
    public function getById($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing exam subject ID.']);
            return;
        }

        $exam_subject_id = $route_params[0];

        $sql = "SELECT es.*, e.exam_name, s.subject_name FROM ExamSubjects es\n                JOIN Exams e ON es.exam_id = e.exam_id\n                JOIN Subjects s ON es.subject_id = s.subject_id\n                WHERE es.exam_subject_id = :exam_subject_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);
            $stmt->execute();

            $exam_subject = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exam_subject) {
                ResponseHelper::send(200, $exam_subject);
            } else {
                ResponseHelper::send(404, ['message' => 'Exam subject not found.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error fetching exam subject by ID: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }

    // Method to update an Exam Subject by ID
    public function update($route_params, $request_data) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing exam subject ID.']);
            return;
        }

        $exam_subject_id = $route_params[0];

        // Build the update query dynamically based on provided data
        $update_fields = [];
        $allowed_fields = ['exam_id', 'subject_id', 'number_of_questions', 'time_limit_seconds', 'scoring_scheme'];
        $bind_params = [':exam_subject_id' => $exam_subject_id];

        foreach ($request_data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $update_fields[] = "`{$key}` = :{$key}";
                $bind_params[":{$key}"] = $value;
            }
        }

        if (empty($update_fields)) {
            ResponseHelper::send(400, ['error' => 'No valid fields provided for update.']);
            return;
        }

        $sql = "UPDATE ExamSubjects SET " . implode(', ', $update_fields) . " WHERE exam_subject_id = :exam_subject_id";

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($bind_params as $param => $value) {
                // Determine parameter type
                $param_type = PDO::PARAM_STR;
                if (is_int($value)) {
                    $param_type = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                     $param_type = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $param_type = PDO::PARAM_NULL;
                }

                $stmt->bindParam($param, $bind_params[$param], $param_type);
            }

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Exam subject updated successfully.']);
                } else {
                    ResponseHelper::send(404, ['message' => 'Exam subject not found or no changes made.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Exam subject update failed.']);
            }
        } catch (PDOException $e) {
            // Check for duplicate entry error
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                ResponseHelper::send(409, ['error' => 'Duplicate entry. This exam-subject combination already exists.']);
            } else {
                error_log("Database Error during exam subject update: " . $e->getMessage());
                ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
            }
        }
    }

    // Method to delete an Exam Subject by ID
    public function delete($route_params, $request_data = null) {
        if (!isset($route_params[0])) {
            ResponseHelper::send(400, ['error' => 'Missing exam subject ID.']);
            return;
        }

        $exam_subject_id = $route_params[0];

        $sql = "DELETE FROM ExamSubjects WHERE exam_subject_id = :exam_subject_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':exam_subject_id', $exam_subject_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    ResponseHelper::send(200, ['message' => 'Exam subject deleted successfully.']);
                } else {
                    ResponseHelper::send(404, ['message' => 'Exam subject not found.']);
                }
            } else {
                ResponseHelper::send(500, ['error' => 'Exam subject deletion failed.']);
            }
        } catch (PDOException $e) {
            error_log("Database Error during exam subject deletion: " . $e->getMessage());
            ResponseHelper::send(500, ['error' => 'An internal server error occurred.']);
        }
    }
}