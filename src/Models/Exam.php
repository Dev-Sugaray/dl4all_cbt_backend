<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Exam {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Exam Methods
    public function findExamById($examId) {
        $stmt = $this->db->prepare("SELECT * FROM Exams WHERE exam_id = :exam_id");
        $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllExams($limit = 10, $offset = 0, $isActive = null) {
        $sql = "SELECT * FROM Exams";
        $params = [];
        if ($isActive !== null) {
            $sql .= " WHERE is_active = :is_active";
            $params[':is_active'] = (bool)$isActive;
        }
        $sql .= " ORDER BY creation_date DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : (is_bool($val) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
            $stmt->bindParam($key, $val, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createExam(array $data) {
        $sql = "INSERT INTO Exams (exam_name, exam_abbreviation, description, is_active) 
                VALUES (:exam_name, :exam_abbreviation, :description, :is_active)";
        $stmt = $this->db->prepare($sql);
        $isActive = isset($data['isActive']) ? (bool)$data['isActive'] : true;
        $stmt->bindParam(':exam_name', $data['examName']);
        $stmt->bindParam(':exam_abbreviation', $data['examAbbreviation']);
        $stmt->bindParam(':description', $data['description'] ?? null);
        $stmt->bindParam(':is_active', $isActive, PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            return $this->findExamById($this->db->lastInsertId());
        }
        return false;
    }

    public function updateExam($examId, array $data) {
        $fields = [];
        $params = [':exam_id' => $examId];

        if (isset($data['examName'])) { $fields[] = "exam_name = :exam_name"; $params[':exam_name'] = $data['examName']; }
        if (isset($data['examAbbreviation'])) { $fields[] = "exam_abbreviation = :exam_abbreviation"; $params[':exam_abbreviation'] = $data['examAbbreviation']; }
        if (isset($data['description'])) { $fields[] = "description = :description"; $params[':description'] = $data['description']; }
        if (isset($data['isActive'])) { $fields[] = "is_active = :is_active"; $params[':is_active'] = (bool)$data['isActive']; }

        if (empty($fields)) return false;

        $sql = "UPDATE Exams SET " . implode(', ', $fields) . " WHERE exam_id = :exam_id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => &$val) {
            $type = ($key === ':is_active') ? PDO::PARAM_BOOL : (is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
             if ($key !== ':exam_id' || $key !== ':is_active') $type = PDO::PARAM_STR; // Default to string for non-boolean/int fields
             if ($key === ':exam_id') $type = PDO::PARAM_INT;
             if ($key === ':is_active') $type = PDO::PARAM_BOOL;
            $stmt->bindParam($key, $val, $type);
        }

        if ($stmt->execute()) {
            return $this->findExamById($examId);
        }
        return false;
    }

    public function deleteExam($examId) {
        // Consider cascading deletes or soft deletes based on requirements
        $stmt = $this->db->prepare("DELETE FROM Exams WHERE exam_id = :exam_id");
        $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ExamSubject Methods
    public function findExamSubjectById($examSubjectId) {
        $stmt = $this->db->prepare("SELECT * FROM ExamSubjects WHERE exam_subject_id = :exam_subject_id");
        $stmt->bindParam(':exam_subject_id', $examSubjectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getExamSubjectsByExamId($examId) {
        $stmt = $this->db->prepare("SELECT * FROM ExamSubjects WHERE exam_id = :exam_id");
        $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getExamSubjectsBySubjectId($subjectId) {
        $stmt = $this->db->prepare("SELECT * FROM ExamSubjects WHERE subject_id = :subject_id");
        $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createExamSubject(array $data) {
        $sql = "INSERT INTO ExamSubjects (exam_id, subject_id, number_of_questions, time_limit_seconds, scoring_scheme) 
                VALUES (:exam_id, :subject_id, :number_of_questions, :time_limit_seconds, :scoring_scheme)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':exam_id', $data['examId'], PDO::PARAM_INT);
        $stmt->bindParam(':subject_id', $data['subjectId'], PDO::PARAM_INT);
        $stmt->bindParam(':number_of_questions', $data['numberOfQuestions'], PDO::PARAM_INT);
        $stmt->bindParam(':time_limit_seconds', $data['timeLimitSeconds'], PDO::PARAM_INT);
        $stmt->bindParam(':scoring_scheme', $data['scoringScheme'] ?? null);
        
        if ($stmt->execute()) {
            return $this->findExamSubjectById($this->db->lastInsertId());
        }
        return false;
    }

    public function updateExamSubject($examSubjectId, array $data) {
        $fields = [];
        $params = [':exam_subject_id' => $examSubjectId];

        if (isset($data['numberOfQuestions'])) { $fields[] = "number_of_questions = :number_of_questions"; $params[':number_of_questions'] = $data['numberOfQuestions']; }
        if (isset($data['timeLimitSeconds'])) { $fields[] = "time_limit_seconds = :time_limit_seconds"; $params[':time_limit_seconds'] = $data['timeLimitSeconds']; }
        if (isset($data['scoringScheme'])) { $fields[] = "scoring_scheme = :scoring_scheme"; $params[':scoring_scheme'] = $data['scoringScheme']; }

        if (empty($fields)) return false;

        $sql = "UPDATE ExamSubjects SET " . implode(', ', $fields) . " WHERE exam_subject_id = :exam_subject_id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($key, $val, $type);
        }

        if ($stmt->execute()) {
            return $this->findExamSubjectById($examSubjectId);
        }
        return false;
    }

    public function deleteExamSubject($examSubjectId) {
        $stmt = $this->db->prepare("DELETE FROM ExamSubjects WHERE exam_subject_id = :exam_subject_id");
        $stmt->bindParam(':exam_subject_id', $examSubjectId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}