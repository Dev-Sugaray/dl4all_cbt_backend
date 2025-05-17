<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use DateTime;

class StudentSession {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // StudentSession Methods
    public function findSessionById($sessionId) {
        $stmt = $this->db->prepare("SELECT * FROM StudentSessions WHERE session_id = :session_id");
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSessionsByUserId($userId, $limit = 10, $offset = 0, $sessionType = null) {
        $sql = "SELECT * FROM StudentSessions WHERE user_id = :user_id";
        $params = [':user_id' => $userId];

        if ($sessionType) {
            $sql .= " AND session_type = :session_type";
            $params[':session_type'] = $sessionType;
        }

        $sql .= " ORDER BY start_time DESC LIMIT :limit OFFSET :offset";
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
    
    public function getSessionsByExamSubject($examSubjectId, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("SELECT * FROM StudentSessions WHERE exam_subject_id = :exam_subject_id ORDER BY start_time DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':exam_subject_id', $examSubjectId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function startSession(array $data, $userId) {
        $sql = "INSERT INTO StudentSessions (user_id, exam_subject_id, total_questions, time_allocated_seconds, session_type, settings) 
                VALUES (:user_id, :exam_subject_id, :total_questions, :time_allocated_seconds, :session_type, :settings)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':exam_subject_id', $data['examSubjectId'], PDO::PARAM_INT);
        $stmt->bindParam(':total_questions', $data['totalQuestions'], PDO::PARAM_INT);
        $stmt->bindParam(':time_allocated_seconds', $data['timeAllocatedSeconds'] ?? null, PDO::PARAM_INT);
        $stmt->bindParam(':session_type', $data['sessionType'] ?? 'practice');
        $settingsJson = isset($data['settings']) ? json_encode($data['settings']) : null;
        $stmt->bindParam(':settings', $settingsJson);

        if ($stmt->execute()) {
            return $this->findSessionById($this->db->lastInsertId());
        }
        return false;
    }

    public function endSession($sessionId) {
        $sql = "UPDATE StudentSessions SET end_time = CURRENT_TIMESTAMP WHERE session_id = :session_id AND end_time IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_INT);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return $this->findSessionById($sessionId);
        }
        // If session already ended or not found, return current state or false
        $session = $this->findSessionById($sessionId);
        return $session && $session['end_time'] ? $session : false;
    }
    
    public function updateSessionSettings($sessionId, array $settings) {
        $sql = "UPDATE StudentSessions SET settings = :settings WHERE session_id = :session_id";
        $stmt = $this->db->prepare($sql);
        $settingsJson = json_encode($settings);
        $stmt->bindParam(':settings', $settingsJson);
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $this->findSessionById($sessionId);
        }
        return false;
    }

    // StudentAnswer Methods
    public function findAnswerById($answerId) {
        $stmt = $this->db->prepare("SELECT * FROM StudentAnswers WHERE answer_id = :answer_id");
        $stmt->bindParam(':answer_id', $answerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAnswersBySessionId($sessionId) {
        $stmt = $this->db->prepare("SELECT * FROM StudentAnswers WHERE session_id = :session_id ORDER BY submission_time ASC");
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function submitAnswer(array $data, $sessionId) {
        // Fetch the question to determine correctness
        $questionModel = new Question(); // Assuming Question model exists
        $question = $questionModel->findQuestionById($data['questionId']);
        if (!$question) return false; // Question not found

        $isCorrect = ($question['correct_answer'] === $data['submittedAnswer']);

        $sql = "INSERT INTO StudentAnswers (session_id, question_id, submitted_answer, is_correct, time_taken_seconds) 
                VALUES (:session_id, :question_id, :submitted_answer, :is_correct, :time_taken_seconds)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_INT);
        $stmt->bindParam(':question_id', $data['questionId'], PDO::PARAM_INT);
        $stmt->bindParam(':submitted_answer', $data['submittedAnswer']);
        $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);
        $stmt->bindParam(':time_taken_seconds', $data['timeTakenSeconds'] ?? null, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->findAnswerById($this->db->lastInsertId());
        }
        return false;
    }
    
    public function getSessionResults($sessionId) {
        $session = $this->findSessionById($sessionId);
        if (!$session) return null;

        $answers = $this->getAnswersBySessionId($sessionId);
        
        $totalAnswered = count($answers);
        $correctAnswers = 0;
        $totalTimeTaken = 0;

        foreach ($answers as $answer) {
            if ($answer['is_correct']) {
                $correctAnswers++;
            }
            if ($answer['time_taken_seconds'] !== null) {
                $totalTimeTaken += $answer['time_taken_seconds'];
            }
        }
        
        $score = 0;
        if ($session['total_questions'] > 0) {
            $score = ($correctAnswers / $session['total_questions']) * 100;
        }

        return [
            'sessionId' => (int)$sessionId,
            'userId' => (int)$session['user_id'],
            'examSubjectId' => (int)$session['exam_subject_id'],
            'startTime' => $session['start_time'],
            'endTime' => $session['end_time'],
            'totalQuestionsInSession' => (int)$session['total_questions'],
            'totalQuestionsAnswered' => $totalAnswered,
            'correctAnswers' => $correctAnswers,
            'incorrectAnswers' => $totalAnswered - $correctAnswers,
            'scorePercentage' => round($score, 2),
            'totalTimeTakenSeconds' => $totalTimeTaken,
            'answers' => $answers // Optionally include all answers
        ];
    }
}