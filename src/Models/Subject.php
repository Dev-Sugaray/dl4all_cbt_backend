<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Subject {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Subject Methods
    public function findSubjectById($subjectId) {
        $stmt = $this->db->prepare("SELECT * FROM Subjects WHERE subject_id = :subject_id");
        $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllSubjects($limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("SELECT * FROM Subjects ORDER BY subject_name ASC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSubject(array $data) {
        $sql = "INSERT INTO Subjects (subject_name, subject_code, description) 
                VALUES (:subject_name, :subject_code, :description)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':subject_name', $data['subjectName']);
        $stmt->bindParam(':subject_code', $data['subjectCode'] ?? null);
        $stmt->bindParam(':description', $data['description'] ?? null);
        
        if ($stmt->execute()) {
            return $this->findSubjectById($this->db->lastInsertId());
        }
        return false;
    }

    public function updateSubject($subjectId, array $data) {
        $fields = [];
        $params = [':subject_id' => $subjectId];

        if (isset($data['subjectName'])) { $fields[] = "subject_name = :subject_name"; $params[':subject_name'] = $data['subjectName']; }
        if (isset($data['subjectCode'])) { $fields[] = "subject_code = :subject_code"; $params[':subject_code'] = $data['subjectCode']; }
        if (isset($data['description'])) { $fields[] = "description = :description"; $params[':description'] = $data['description']; }

        if (empty($fields)) return false;

        $sql = "UPDATE Subjects SET " . implode(', ', $fields) . " WHERE subject_id = :subject_id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => &$val) {
            $type = ($key === ':subject_id') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($key, $val, $type);
        }

        if ($stmt->execute()) {
            return $this->findSubjectById($subjectId);
        }
        return false;
    }

    public function deleteSubject($subjectId) {
        // Consider cascading deletes or soft deletes
        $stmt = $this->db->prepare("DELETE FROM Subjects WHERE subject_id = :subject_id");
        $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Topic Methods
    public function findTopicById($topicId) {
        $stmt = $this->db->prepare("SELECT * FROM Topics WHERE topic_id = :topic_id");
        $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTopicsBySubjectId($subjectId) {
        $stmt = $this->db->prepare("SELECT * FROM Topics WHERE subject_id = :subject_id ORDER BY topic_name ASC");
        $stmt->bindParam(':subject_id', $subjectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTopic(array $data) {
        $sql = "INSERT INTO Topics (subject_id, topic_name, description) 
                VALUES (:subject_id, :topic_name, :description)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':subject_id', $data['subjectId'], PDO::PARAM_INT);
        $stmt->bindParam(':topic_name', $data['topicName']);
        $stmt->bindParam(':description', $data['description'] ?? null);
        
        if ($stmt->execute()) {
            return $this->findTopicById($this->db->lastInsertId());
        }
        return false;
    }

    public function updateTopic($topicId, array $data) {
        $fields = [];
        $params = [':topic_id' => $topicId];

        if (isset($data['topicName'])) { $fields[] = "topic_name = :topic_name"; $params[':topic_name'] = $data['topicName']; }
        if (isset($data['description'])) { $fields[] = "description = :description"; $params[':description'] = $data['description']; }
        // subject_id is generally not updated for a topic, but can be added if needed

        if (empty($fields)) return false;

        $sql = "UPDATE Topics SET " . implode(', ', $fields) . " WHERE topic_id = :topic_id";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => &$val) {
            $type = ($key === ':topic_id') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($key, $val, $type);
        }

        if ($stmt->execute()) {
            return $this->findTopicById($topicId);
        }
        return false;
    }

    public function deleteTopic($topicId) {
        $stmt = $this->db->prepare("DELETE FROM Topics WHERE topic_id = :topic_id");
        $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}