<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($userId) {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data) {
        $sql = "INSERT INTO Users (email, hashed_password, full_name, user_role, date_of_birth, gender, institution, study_level, preferred_exam_types, email_verification_token) 
                VALUES (:email, :hashed_password, :full_name, :user_role, :date_of_birth, :gender, :institution, :study_level, :preferred_exam_types, :email_verification_token)";
        
        $stmt = $this->db->prepare($sql);
        
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $emailVerificationToken = bin2hex(random_bytes(32)); // Generate a verification token

        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':hashed_password', $hashedPassword);
        $stmt->bindParam(':full_name', $data['fullName']);
        $stmt->bindParam(':user_role', $data['userRole']);
        $stmt->bindParam(':date_of_birth', $data['dateOfBirth'] ?? null);
        $stmt->bindParam(':gender', $data['gender'] ?? null);
        $stmt->bindParam(':institution', $data['institution'] ?? null);
        $stmt->bindParam(':study_level', $data['studyLevel'] ?? null);
        $preferredExamTypes = isset($data['preferredExamTypes']) ? implode(',', $data['preferredExamTypes']) : null;
        $stmt->bindParam(':preferred_exam_types', $preferredExamTypes);
        $stmt->bindParam(':email_verification_token', $emailVerificationToken);
        
        if ($stmt->execute()) {
            return $this->findById($this->db->lastInsertId());
        }
        return false;
    }

    public function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE Users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updatePasswordResetToken($userId, $token) {
        $stmt = $this->db->prepare("UPDATE Users SET password_reset_token = :token WHERE user_id = :user_id");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function findByPasswordResetToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE password_reset_token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE Users SET hashed_password = :hashed_password, password_reset_token = NULL WHERE user_id = :user_id");
        $stmt->bindParam(':hashed_password', $hashedPassword);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function updateUserProfile($userId, array $data) {
        $fields = [];
        $params = [':user_id' => $userId];

        if (isset($data['fullName'])) {
            $fields[] = "full_name = :full_name";
            $params[':full_name'] = $data['fullName'];
        }
        if (isset($data['dateOfBirth'])) {
            $fields[] = "date_of_birth = :date_of_birth";
            $params[':date_of_birth'] = $data['dateOfBirth'];
        }
        if (isset($data['gender'])) {
            $fields[] = "gender = :gender";
            $params[':gender'] = $data['gender'];
        }
        if (isset($data['institution'])) {
            $fields[] = "institution = :institution";
            $params[':institution'] = $data['institution'];
        }
        if (isset($data['studyLevel'])) {
            $fields[] = "study_level = :study_level";
            $params[':study_level'] = $data['studyLevel'];
        }
        if (isset($data['preferredExamTypes'])) {
            $fields[] = "preferred_exam_types = :preferred_exam_types";
            $params[':preferred_exam_types'] = implode(',', $data['preferredExamTypes']);
        }

        if (empty($fields)) {
            return false; // No fields to update
        }

        $sql = "UPDATE Users SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            return $this->findById($userId);
        }
        return false;
    }

    public function updateUserStatus($userId, $isActive) {
        $stmt = $this->db->prepare("UPDATE Users SET is_active = :is_active WHERE user_id = :user_id");
        $stmt->bindParam(':is_active', $isActive, PDO::PARAM_BOOL);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $this->findById($userId);
        }
        return false;
    }

    public function getAllUsers($limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("SELECT * FROM Users ORDER BY registration_timestamp DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}