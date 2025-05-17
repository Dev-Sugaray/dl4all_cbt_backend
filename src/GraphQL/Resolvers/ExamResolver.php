<?php

namespace App\GraphQL\Resolvers;

use App\Models\Exam;
use App\Models\Subject; // For resolving Subject type within ExamSubject
use App\Models\User;    // For resolving User type for createdBy
use GraphQL\Error\Error;

class ExamResolver {
    private $examModel;
    private $subjectModel; // To fetch subject details
    private $userModel;    // To fetch user details

    public function __construct() {
        $this->examModel = new Exam();
        $this->subjectModel = new Subject();
        $this->userModel = new User();
    }

    // Exam Resolvers
    public function getExam($_, array $args) {
        return $this->examModel->findExamById($args['examId']);
    }

    public function getExams($_, array $args) {
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        $isActive = $args['isActive'] ?? null;
        return $this->examModel->getAllExams($limit, $offset, $isActive);
    }

    public function createExam($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to create exams.');
        }
        $exam = $this->examModel->createExam($args['input']);
        if (!$exam) {
            throw new Error('Failed to create exam.');
        }
        return $exam;
    }

    public function updateExam($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to update exams.');
        }
        $exam = $this->examModel->updateExam($args['input']['examId'], $args['input']);
        if (!$exam) {
            throw new Error('Failed to update exam or no changes made.');
        }
        return $exam;
    }

    public function deleteExam($_, array $args, $context) {
        if (empty($context['user']) || $context['user']['user_role'] !== 'administrator') {
            throw new Error('Not authorized to delete exams.');
        }
        // Add logic to check for dependencies (e.g., ExamSubjects, Questions) before deleting
        // For now, direct delete:
        if ($this->examModel->deleteExam($args['examId'])) {
            return true;
        }
        throw new Error('Failed to delete exam.');
    }

    // ExamSubject Resolvers
    public function getExamSubject($_, array $args) {
        return $this->examModel->findExamSubjectById($args['examSubjectId']);
    }

    public function getExamSubjectsByExam($_, array $args) {
        return $this->examModel->getExamSubjectsByExamId($args['examId']);
    }

    public function getExamSubjectsBySubject($_, array $args) {
        return $this->examModel->getExamSubjectsBySubjectId($args['subjectId']);
    }
    
    // Resolver for the 'subjects' field within the Exam type
    public function getExamSubjects($exam) {
        if (isset($exam['exam_id'])) {
            return $this->examModel->getExamSubjectsByExamId($exam['exam_id']);
        }
        return [];
    }

    public function createExamSubject($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to create exam subjects.');
        }
        // Validate if examId and subjectId exist
        if (!$this->examModel->findExamById($args['input']['examId']) || !$this->subjectModel->findSubjectById($args['input']['subjectId'])) {
            throw new Error('Invalid Exam ID or Subject ID.');
        }
        $examSubject = $this->examModel->createExamSubject($args['input']);
        if (!$examSubject) {
            throw new Error('Failed to create exam subject.');
        }
        return $examSubject;
    }

    public function updateExamSubject($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to update exam subjects.');
        }
        $examSubject = $this->examModel->updateExamSubject($args['input']['examSubjectId'], $args['input']);
        if (!$examSubject) {
            throw new Error('Failed to update exam subject or no changes made.');
        }
        return $examSubject;
    }

    public function deleteExamSubject($_, array $args, $context) {
        if (empty($context['user']) || $context['user']['user_role'] !== 'administrator') {
            throw new Error('Not authorized to delete exam subjects.');
        }
        // Add logic to check for dependencies (e.g., Questions) before deleting
        if ($this->examModel->deleteExamSubject($args['examSubjectId'])) {
            return true;
        }
        throw new Error('Failed to delete exam subject.');
    }

    // Field resolvers for ExamSubject type
    public function resolveExamSubjectExam($examSubject) {
        if (isset($examSubject['exam_id'])) {
            return $this->examModel->findExamById($examSubject['exam_id']);
        }
        return null;
    }

    public function resolveExamSubjectSubject($examSubject) {
        if (isset($examSubject['subject_id'])) {
            return $this->subjectModel->findSubjectById($examSubject['subject_id']);
        }
        return null;
    }
}