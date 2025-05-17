<?php

namespace App\GraphQL\Resolvers;

use App\Models\Question;
use App\Models\Exam; // For resolving ExamSubject
use App\Models\Subject; // For resolving Topic
use App\Models\User;   // For resolving User (created_by_user_id)
use GraphQL\Error\Error;

class QuestionResolver {
    private $questionModel;
    private $examModel;
    private $subjectModel;
    private $userModel;

    public function __construct() {
        $this->questionModel = new Question();
        $this->examModel = new Exam();
        $this->subjectModel = new Subject();
        $this->userModel = new User();
    }

    // Question Resolvers
    public function getQuestion($_, array $args) {
        return $this->questionModel->findQuestionById($args['questionId']);
    }

    public function getQuestionsByExamSubject($_, array $args) {
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        $difficulty = $args['difficultyLevel'] ?? null;
        $topicId = $args['topicId'] ?? null;
        return $this->questionModel->getQuestionsByExamSubject($args['examSubjectId'], $limit, $offset, $difficulty, $topicId);
    }

    public function getQuestionsByTopic($_, array $args) {
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        return $this->questionModel->getQuestionsByTopic($args['topicId'], $limit, $offset);
    }

    public function createQuestion($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to create questions.');
        }
        // Validate examSubjectId and topicId (if provided)
        if (!$this->examModel->findExamSubjectById($args['input']['examSubjectId'])) {
            throw new Error('Invalid ExamSubject ID.');
        }
        if (isset($args['input']['topicId']) && !$this->subjectModel->findTopicById($args['input']['topicId'])) {
            throw new Error('Invalid Topic ID.');
        }

        $question = $this->questionModel->createQuestion($args['input'], $context['user']['user_id']);
        if (!$question) {
            throw new Error('Failed to create question.');
        }
        return $question;
    }

    public function updateQuestion($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to update questions.');
        }
        // Add validation: ensure the user updating is the creator or an admin
        $existingQuestion = $this->questionModel->findQuestionById($args['input']['questionId']);
        if (!$existingQuestion) {
            throw new Error('Question not found.');
        }
        if ($context['user']['user_role'] !== 'administrator' && $existingQuestion['created_by_user_id'] != $context['user']['user_id']) {
            throw new Error('Not authorized to update this question.');
        }
        if (isset($args['input']['topicId']) && !$this->subjectModel->findTopicById($args['input']['topicId'])) {
            throw new Error('Invalid Topic ID.');
        }

        $question = $this->questionModel->updateQuestion($args['input']['questionId'], $args['input']);
        if (!$question) {
            throw new Error('Failed to update question or no changes made.');
        }
        return $question;
    }

    public function deleteQuestion($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to delete questions.');
        }
        // Add validation: ensure the user deleting is the creator or an admin
        $existingQuestion = $this->questionModel->findQuestionById($args['questionId']);
        if (!$existingQuestion) {
            throw new Error('Question not found.');
        }
        if ($context['user']['user_role'] !== 'administrator' && $existingQuestion['created_by_user_id'] != $context['user']['user_id']) {
            throw new Error('Not authorized to delete this question.');
        }

        if ($this->questionModel->deleteQuestion($args['questionId'])) {
            return true;
        }
        throw new Error('Failed to delete question.');
    }

    // QuestionOption Resolvers (if needed for direct manipulation, otherwise handled by Question mutations)
    // public function createQuestionOption(...) { ... }
    // public function updateQuestionOption(...) { ... }
    // public function deleteQuestionOption(...) { ... }

    // Field resolvers for Question type
    public function resolveQuestionExamSubject($question) {
        if (isset($question['exam_subject_id'])) {
            return $this->examModel->findExamSubjectById($question['exam_subject_id']);
        }
        return null;
    }

    public function resolveQuestionTopic($question) {
        if (isset($question['topic_id'])) {
            return $this->subjectModel->findTopicById($question['topic_id']);
        }
        return null;
    }

    public function resolveQuestionCreatedByUser($question) {
        if (isset($question['created_by_user_id'])) {
            return $this->userModel->findById($question['created_by_user_id']);
        }
        return null;
    }

    public function resolveQuestionOptions($question) {
        if (isset($question['question_id'])) {
            return $this->questionModel->getOptionsByQuestionId($question['question_id']);
        }
        return [];
    }

    // Field resolver for QuestionOption type (if needed for resolving parent Question)
    public function resolveQuestionOptionQuestion($option) {
        if (isset($option['question_id'])) {
            return $this->questionModel->findQuestionById($option['question_id']);
        }
        return null;
    }
}