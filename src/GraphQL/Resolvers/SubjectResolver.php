<?php

namespace App\GraphQL\Resolvers;

use App\Models\Subject;
use App\Models\User; // For potential future use if topics have created_by_user_id
use GraphQL\Error\Error;

class SubjectResolver {
    private $subjectModel;
    private $userModel; // For potential future use

    public function __construct() {
        $this->subjectModel = new Subject();
        $this->userModel = new User(); // For potential future use
    }

    // Subject Resolvers
    public function getSubject($_, array $args) {
        return $this->subjectModel->findSubjectById($args['subjectId']);
    }

    public function getSubjects($_, array $args) {
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        return $this->subjectModel->getAllSubjects($limit, $offset);
    }

    public function createSubject($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to create subjects.');
        }
        $subject = $this->subjectModel->createSubject($args['input']);
        if (!$subject) {
            throw new Error('Failed to create subject.');
        }
        return $subject;
    }

    public function updateSubject($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to update subjects.');
        }
        $subject = $this->subjectModel->updateSubject($args['input']['subjectId'], $args['input']);
        if (!$subject) {
            throw new Error('Failed to update subject or no changes made.');
        }
        return $subject;
    }

    public function deleteSubject($_, array $args, $context) {
        if (empty($context['user']) || $context['user']['user_role'] !== 'administrator') {
            throw new Error('Not authorized to delete subjects.');
        }
        // Add logic to check for dependencies (e.g., ExamSubjects, Topics, Questions) before deleting
        if ($this->subjectModel->deleteSubject($args['subjectId'])) {
            return true;
        }
        throw new Error('Failed to delete subject.');
    }

    // Topic Resolvers
    public function getTopic($_, array $args) {
        return $this->subjectModel->findTopicById($args['topicId']);
    }

    public function getTopicsBySubject($_, array $args) {
        return $this->subjectModel->getTopicsBySubjectId($args['subjectId']);
    }
    
    // Resolver for the 'topics' field within the Subject type
    public function getSubjectTopics($subject) {
        if (isset($subject['subject_id'])) {
            return $this->subjectModel->getTopicsBySubjectId($subject['subject_id']);
        }
        return [];
    }

    public function createTopic($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to create topics.');
        }
        // Validate if subjectId exists
        if (!$this->subjectModel->findSubjectById($args['input']['subjectId'])) {
            throw new Error('Invalid Subject ID.');
        }
        $topic = $this->subjectModel->createTopic($args['input']);
        if (!$topic) {
            throw new Error('Failed to create topic.');
        }
        return $topic;
    }

    public function updateTopic($_, array $args, $context) {
        if (empty($context['user']) || !in_array($context['user']['user_role'], ['administrator', 'content_creator'])) {
            throw new Error('Not authorized to update topics.');
        }
        $topic = $this->subjectModel->updateTopic($args['input']['topicId'], $args['input']);
        if (!$topic) {
            throw new Error('Failed to update topic or no changes made.');
        }
        return $topic;
    }

    public function deleteTopic($_, array $args, $context) {
        if (empty($context['user']) || $context['user']['user_role'] !== 'administrator') {
            throw new Error('Not authorized to delete topics.');
        }
        // Add logic to check for dependencies (e.g., Questions) before deleting
        if ($this->subjectModel->deleteTopic($args['topicId'])) {
            return true;
        }
        throw new Error('Failed to delete topic.');
    }

    // Field resolver for Topic type
    public function resolveTopicSubject($topic) {
        if (isset($topic['subject_id'])) {
            return $this->subjectModel->findSubjectById($topic['subject_id']);
        }
        return null;
    }
}