<?php

namespace App\GraphQL\Resolvers;

use App\Models\StudentSession;
use App\Models\User;
use App\Models\Exam; // For ExamSubject
use App\Models\Question;
use GraphQL\Error\Error;

class StudentSessionResolver {
    private $studentSessionModel;
    private $userModel;
    private $examModel;
    private $questionModel;

    public function __construct() {
        $this->studentSessionModel = new StudentSession();
        $this->userModel = new User();
        $this->examModel = new Exam();
        $this->questionModel = new Question();
    }

    // StudentSession Resolvers
    public function getStudentSession($_, array $args, $context) {
        $session = $this->studentSessionModel->findSessionById($args['sessionId']);
        if (!$session) {
            throw new Error('Session not found.');
        }
        // Authorization: User can only access their own sessions, or admin can access any
        if ($context['user']['user_role'] !== 'administrator' && $session['user_id'] != $context['user']['user_id']) {
            throw new Error('Not authorized to access this session.');
        }
        return $session;
    }

    public function getStudentSessionsByUser($_, array $args, $context) {
        // Authorization: User can only access their own sessions list
        if ($args['userId'] != $context['user']['user_id'] && $context['user']['user_role'] !== 'administrator') {
            throw new Error('Not authorized to access sessions for this user.');
        }
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        $sessionType = $args['sessionType'] ?? null;
        return $this->studentSessionModel->getSessionsByUserId($args['userId'], $limit, $offset, $sessionType);
    }
    
    public function getStudentSessionsByExamSubject($_, array $args, $context) {
        // Authorization: Admins or content creators might need this
        if (!in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to access sessions by exam subject.');
        }
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        return $this->studentSessionModel->getSessionsByExamSubject($args['examSubjectId'], $limit, $offset);
    }

    public function startStudentSession($_, array $args, $context) {
        if (empty($context['user'])) {
            throw new Error('Authentication required to start a session.');
        }
        // Validate examSubjectId
        if (!$this->examModel->findExamSubjectById($args['input']['examSubjectId'])) {
            throw new Error('Invalid ExamSubject ID.');
        }
        // Potentially add logic to fetch actual number of questions for the exam subject
        // For now, totalQuestions is provided by input

        $session = $this->studentSessionModel->startSession($args['input'], $context['user']['user_id']);
        if (!$session) {
            throw new Error('Failed to start session.');
        }
        return $session;
    }

    public function endStudentSession($_, array $args, $context) {
        $session = $this->studentSessionModel->findSessionById($args['sessionId']);
        if (!$session) {
            throw new Error('Session not found.');
        }
        if ($context['user']['user_role'] !== 'administrator' && $session['user_id'] != $context['user']['user_id']) {
            throw new Error('Not authorized to end this session.');
        }
        if ($session['end_time'] !== null) {
             throw new Error('Session has already ended.');
        }

        $endedSession = $this->studentSessionModel->endSession($args['sessionId']);
        if (!$endedSession) {
            throw new Error('Failed to end session.');
        }
        return $endedSession;
    }
    
    public function updateStudentSessionSettings($_, array $args, $context) {
        $session = $this->studentSessionModel->findSessionById($args['input']['sessionId']);
        if (!$session) {
            throw new Error('Session not found.');
        }
        if ($context['user']['user_role'] !== 'administrator' && $session['user_id'] != $context['user']['user_id']) {
            throw new Error('Not authorized to update settings for this session.');
        }
        if ($session['end_time'] !== null) {
             throw new Error('Cannot update settings for an ended session.');
        }

        $updatedSession = $this->studentSessionModel->updateSessionSettings($args['input']['sessionId'], $args['input']['settings']);
        if (!$updatedSession) {
            throw new Error('Failed to update session settings.');
        }
        return $updatedSession;
    }

    // StudentAnswer Resolvers
    public function submitStudentAnswer($_, array $args, $context) {
        $session = $this->studentSessionModel->findSessionById($args['input']['sessionId']);
        if (!$session) {
            throw new Error('Session not found.');
        }
        if ($context['user']['user_role'] !== 'administrator' && $session['user_id'] != $context['user']['user_id']) {
            throw new Error('Not authorized to submit answers for this session.');
        }
        if ($session['end_time'] !== null) {
            throw new Error('Cannot submit answers for an ended session.');
        }
        // Validate questionId
        if (!$this->questionModel->findQuestionById($args['input']['questionId'])) {
            throw new Error('Invalid Question ID.');
        }

        $answer = $this->studentSessionModel->submitAnswer($args['input'], $args['input']['sessionId']);
        if (!$answer) {
            throw new Error('Failed to submit answer.');
        }
        return $answer;
    }

    public function getStudentSessionResults($_, array $args, $context) {
        $session = $this->studentSessionModel->findSessionById($args['sessionId']);
        if (!$session) {
            throw new Error('Session not found.');
        }
        if ($context['user']['user_role'] !== 'administrator' && $session['user_id'] != $context['user']['user_id']) {
            throw new Error('Not authorized to view results for this session.');
        }
        // Ensure session has ended before showing full results, or adapt logic
        // if ($session['end_time'] === null && $context['user']['user_role'] !== 'administrator') {
        //     throw new Error('Session has not ended yet. Results are not available.');
        // }

        return $this->studentSessionModel->getSessionResults($args['sessionId']);
    }

    // Field resolvers for StudentSession type
    public function resolveSessionUser($session) {
        if (isset($session['user_id'])) {
            return $this->userModel->findById($session['user_id']);
        }
        return null;
    }

    public function resolveSessionExamSubject($session) {
        if (isset($session['exam_subject_id'])) {
            return $this->examModel->findExamSubjectById($session['exam_subject_id']);
        }
        return null;
    }

    public function resolveSessionAnswers($session) {
        if (isset($session['session_id'])) {
            return $this->studentSessionModel->getAnswersBySessionId($session['session_id']);
        }
        return [];
    }

    // Field resolvers for StudentAnswer type
    public function resolveAnswerSession($answer) {
        if (isset($answer['session_id'])) {
            return $this->studentSessionModel->findSessionById($answer['session_id']);
        }
        return null;
    }

    public function resolveAnswerQuestion($answer) {
        if (isset($answer['question_id'])) {
            return $this->questionModel->findQuestionById($answer['question_id']);
        }
        return null;
    }
    
    // Field resolver for StudentSessionResult type (if it needs specific resolvers)
    // For example, if User or ExamSubject objects are needed directly in StudentSessionResult
    public function resolveResultUser($result) {
        if (isset($result['userId'])) {
            return $this->userModel->findById($result['userId']);
        }
        return null;
    }

    public function resolveResultExamSubject($result) {
        if (isset($result['examSubjectId'])) {
            return $this->examModel->findExamSubjectById($result['examSubjectId']);
        }
        return null;
    }
}