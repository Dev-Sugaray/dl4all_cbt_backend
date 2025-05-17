<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Question {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Question Methods
    public function findQuestionById($questionId) {
        $stmt = $this->db->prepare("SELECT * FROM Questions WHERE question_id = :question_id");
        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getQuestionsByExamSubject($examSubjectId, $limit = 10, $offset = 0, $difficulty = null, $topicId = null) {
        $sql = "SELECT * FROM Questions WHERE exam_subject_id = :exam_subject_id";
        $params = [':exam_subject_id' => $examSubjectId];

        if ($difficulty) {
            $sql .= " AND difficulty_level = :difficulty_level";
            $params[':difficulty_level'] = $difficulty;
        }
        if ($topicId) {
            $sql .= " AND topic_id = :topic_id";
            $params[':topic_id'] = $topicId;
        }

        $sql .= " ORDER BY creation_date DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($key, $val, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getQuestionsByTopic($topicId, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("SELECT * FROM Questions WHERE topic_id = :topic_id ORDER BY creation_date DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createQuestion(array $data, $userId) {
        $sql = "INSERT INTO Questions (exam_subject_id, topic_id, question_text, question_type, correct_answer, explanation, difficulty_level, created_by_user_id) 
                VALUES (:exam_subject_id, :topic_id, :question_text, :question_type, :correct_answer, :explanation, :difficulty_level, :created_by_user_id)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':exam_subject_id', $data['examSubjectId'], PDO::PARAM_INT);
        $stmt->bindParam(':topic_id', $data['topicId'] ?? null, PDO::PARAM_INT);
        $stmt->bindParam(':question_text', $data['questionText']);
        $stmt->bindParam(':question_type', $data['questionType']);
        $stmt->bindParam(':correct_answer', $data['correctAnswer']);
        $stmt->bindParam(':explanation', $data['explanation'] ?? null);
        $stmt->bindParam(':difficulty_level', $data['difficultyLevel'] ?? null);
        $stmt->bindParam(':created_by_user_id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $questionId = $this->db->lastInsertId();
            if (isset($data['options']) && is_array($data['options'])) {
                foreach ($data['options'] as $optionData) {
                    $this->createQuestionOption($questionId, $optionData);
                }
            }
            return $this->findQuestionById($questionId);
        }
        return false;
    }

    public function updateQuestion($questionId, array $data) {
        $fields = [];
        $params = [':question_id' => $questionId];

        if (isset($data['questionText'])) { $fields[] = "question_text = :question_text"; $params[':question_text'] = $data['questionText']; }
        if (isset($data['questionType'])) { $fields[] = "question_type = :question_type"; $params[':question_type'] = $data['questionType']; }
        if (isset($data['correctAnswer'])) { $fields[] = "correct_answer = :correct_answer"; $params[':correct_answer'] = $data['correctAnswer']; }
        if (isset($data['explanation'])) { $fields[] = "explanation = :explanation"; $params[':explanation'] = $data['explanation']; }
        if (isset($data['difficultyLevel'])) { $fields[] = "difficulty_level = :difficulty_level"; $params[':difficulty_level'] = $data['difficultyLevel']; }
        if (isset($data['topicId'])) { $fields[] = "topic_id = :topic_id"; $params[':topic_id'] = $data['topicId']; }
        // exam_subject_id is typically not changed after creation, but can be added if needed.

        if (empty($fields)) { // If only options are updated
             if (isset($data['options']) && is_array($data['options'])) {
                // Clear existing options and add new ones, or implement more granular update
                $this->deleteOptionsByQuestionId($questionId);
                foreach ($data['options'] as $optionData) {
                    $this->createQuestionOption($questionId, $optionData);
                }
                return $this->findQuestionById($questionId); // Return the question with updated options
            }
            return false; // No fields to update and no options provided
        }

        $sql = "UPDATE Questions SET " . implode(', ', $fields) . " WHERE question_id = :question_id";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => &$val) {
            $type = ($key === ':question_id' || $key === ':topic_id') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($key, $val, $type);
        }

        if ($stmt->execute()) {
            if (isset($data['options']) && is_array($data['options'])) {
                // Clear existing options and add new ones, or implement more granular update
                $this->deleteOptionsByQuestionId($questionId);
                foreach ($data['options'] as $optionData) {
                    $this->createQuestionOption($questionId, $optionData);
                }
            }
            return $this->findQuestionById($questionId);
        }
        return false;
    }

    public function deleteQuestion($questionId) {
        // First delete associated options
        $this->deleteOptionsByQuestionId($questionId);
        // Then delete the question
        $stmt = $this->db->prepare("DELETE FROM Questions WHERE question_id = :question_id");
        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // QuestionOption Methods
    public function findQuestionOptionById($optionId) {
        $stmt = $this->db->prepare("SELECT * FROM QuestionOptions WHERE option_id = :option_id");
        $stmt->bindParam(':option_id', $optionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOptionsByQuestionId($questionId) {
        $stmt = $this->db->prepare("SELECT * FROM QuestionOptions WHERE question_id = :question_id ORDER BY option_letter ASC");
        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createQuestionOption($questionId, array $optionData) {
        $sql = "INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) 
                VALUES (:question_id, :option_letter, :option_text, :is_correct)";
        $stmt = $this->db->prepare($sql);
        $isCorrect = isset($optionData['isCorrect']) ? (bool)$optionData['isCorrect'] : false;

        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $stmt->bindParam(':option_letter', $optionData['optionLetter']);
        $stmt->bindParam(':option_text', $optionData['optionText']);
        $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);
        
        return $stmt->execute(); // Returns true on success, false on failure
    }
    
    // updateQuestionOption might be needed if granular option updates are required
    // public function updateQuestionOption($optionId, array $optionData) { ... }

    public function deleteQuestionOption($optionId) {
        $stmt = $this->db->prepare("DELETE FROM QuestionOptions WHERE option_id = :option_id");
        $stmt->bindParam(':option_id', $optionId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteOptionsByQuestionId($questionId) {
        $stmt = $this->db->prepare("DELETE FROM QuestionOptions WHERE question_id = :question_id");
        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}