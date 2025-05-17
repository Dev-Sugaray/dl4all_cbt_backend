<?php

namespace App\GraphQL\Resolvers;

use App\GraphQL\Resolvers\UserResolver;
use App\GraphQL\Resolvers\ExamResolver;
use App\GraphQL\Resolvers\SubjectResolver;
use App\GraphQL\Resolvers\QuestionResolver;
use App\GraphQL\Resolvers\StudentSessionResolver; // Corrected from SessionResolver

class ResolverRegistry {
    public static function getResolvers() {
        return [
            'Query' => [
                // User Queries
                'me' => [UserResolver::class, 'me'],
                'user' => [UserResolver::class, 'getUser'],
                'users' => [UserResolver::class, 'getUsers'],

                // Exam Queries
                'exam' => [ExamResolver::class, 'getExam'],
                'exams' => [ExamResolver::class, 'getExams'],
                'examSubject' => [ExamResolver::class, 'getExamSubject'],
                'examSubjectsByExam' => [ExamResolver::class, 'getExamSubjectsByExam'],
                'examSubjectsBySubject' => [ExamResolver::class, 'getExamSubjectsBySubject'],

                // Subject Queries
                'subject' => [SubjectResolver::class, 'getSubject'],
                'subjects' => [SubjectResolver::class, 'getSubjects'],
                'topic' => [SubjectResolver::class, 'getTopic'],
                'topicsBySubject' => [SubjectResolver::class, 'getTopicsBySubject'],

                // Question Queries
                'question' => [QuestionResolver::class, 'getQuestion'],
                'questionsByExamSubject' => [QuestionResolver::class, 'getQuestionsByExamSubject'],
                'questionsByTopic' => [QuestionResolver::class, 'getQuestionsByTopic'],
                // 'searchQuestions' => [QuestionResolver::class, 'searchQuestions'], // Assuming this might be added later or was a placeholder

                // StudentSession Queries
                'studentSession' => [StudentSessionResolver::class, 'getStudentSession'],
                'studentSessionsByUser' => [StudentSessionResolver::class, 'getStudentSessionsByUser'],
                'studentSessionsByExamSubject' => [StudentSessionResolver::class, 'getStudentSessionsByExamSubject'],
                'studentSessionResults' => [StudentSessionResolver::class, 'getStudentSessionResults'],
                // 'activeSession' => [StudentSessionResolver::class, 'getActiveSession'], // Assuming these might be added or were placeholders
                // 'sessionStats' => [StudentSessionResolver::class, 'getSessionStats'],
                // 'userPerformance' => [StudentSessionResolver::class, 'getUserPerformance'],
            ],
            
            'Mutation' => [
                // User Mutations
                'login' => [UserResolver::class, 'login'],
                'register' => [UserResolver::class, 'register'],
                'refreshToken' => [UserResolver::class, 'refreshToken'],
                'requestPasswordReset' => [UserResolver::class, 'requestPasswordReset'],
                'resetPassword' => [UserResolver::class, 'resetPassword'],
                'updateUserProfile' => [UserResolver::class, 'updateUserProfile'],
                'updateUserStatus' => [UserResolver::class, 'updateUserStatus'],

                // Exam Mutations
                'createExam' => [ExamResolver::class, 'createExam'],
                'updateExam' => [ExamResolver::class, 'updateExam'],
                'deleteExam' => [ExamResolver::class, 'deleteExam'],
                'createExamSubject' => [ExamResolver::class, 'createExamSubject'],
                'updateExamSubject' => [ExamResolver::class, 'updateExamSubject'],
                'deleteExamSubject' => [ExamResolver::class, 'deleteExamSubject'],

                // Subject Mutations
                'createSubject' => [SubjectResolver::class, 'createSubject'],
                'updateSubject' => [SubjectResolver::class, 'updateSubject'],
                'deleteSubject' => [SubjectResolver::class, 'deleteSubject'],
                'createTopic' => [SubjectResolver::class, 'createTopic'],
                'updateTopic' => [SubjectResolver::class, 'updateTopic'],
                'deleteTopic' => [SubjectResolver::class, 'deleteTopic'],

                // Question Mutations
                'createQuestion' => [QuestionResolver::class, 'createQuestion'],
                'updateQuestion' => [QuestionResolver::class, 'updateQuestion'],
                'deleteQuestion' => [QuestionResolver::class, 'deleteQuestion'],

                // StudentSession Mutations
                'startStudentSession' => [StudentSessionResolver::class, 'startStudentSession'],
                'endStudentSession' => [StudentSessionResolver::class, 'endStudentSession'],
                'updateStudentSessionSettings' => [StudentSessionResolver::class, 'updateStudentSessionSettings'],
                'submitStudentAnswer' => [StudentSessionResolver::class, 'submitStudentAnswer'],
            ],
            
            // Type resolvers
            'User' => [
                'userId' => function ($user) { return $user['user_id'] ?? null; },
                'userRole' => function ($user) { return $user['user_role'] ?? null; },
                'email' => function ($user) { return $user['email'] ?? null; },
                'fullName' => function ($user) { return $user['full_name'] ?? null; },
                'registrationTimestamp' => function ($user) { return $user['registration_timestamp'] ?? null; },
                'lastLogin' => function ($user) { return $user['last_login'] ?? null; },
                'isActive' => function ($user) { return isset($user['is_active']) ? (bool)$user['is_active'] : null; },
                'dateOfBirth' => function ($user) { return $user['date_of_birth'] ?? null; },
                'gender' => function ($user) { return $user['gender'] ?? null; },
                'institution' => function ($user) { return $user['institution'] ?? null; },
                'studyLevel' => function ($user) { return $user['study_level'] ?? null; },
                'preferredExamTypes' => function ($user) { 
                    return !empty($user['preferred_exam_types']) ? explode(',', $user['preferred_exam_types']) : []; 
                },
                'profilePictureUrl' => function ($user) { return $user['profile_picture_url'] ?? null; },
                'adminName' => function ($user) { return $user['admin_name'] ?? null; },
                'permissions' => function ($user) { return !empty($user['permissions']) ? json_decode($user['permissions'], true) : null; },
                'creatorName' => function ($user) { return $user['creator_name'] ?? null; },
                'expertiseArea' => function ($user) { return $user['expertise_area'] ?? null; },
                'creationCredentials' => function ($user) { return $user['creation_credentials'] ?? null; },
            ],
            'AuthPayload' => [
                'user' => [UserResolver::class, 'resolveAuthPayloadUser'], // Assumes this method exists in UserResolver
            ],
            'Exam' => [
                'examId' => function ($exam) { return $exam['exam_id'] ?? null; },
                'examName' => function ($exam) { return $exam['exam_name'] ?? null; },
                'examAbbreviation' => function ($exam) { return $exam['exam_abbreviation'] ?? null; },
                'description' => function ($exam) { return $exam['description'] ?? null; },
                'isActive' => function ($exam) { return isset($exam['is_active']) ? (bool)$exam['is_active'] : null; },
                'creationDate' => function ($exam) { return $exam['creation_date'] ?? null; },
                'subjects' => [ExamResolver::class, 'getExamSubjects'],
            ],
            'Subject' => [
                'subjectId' => function ($subject) { return $subject['subject_id'] ?? null; },
                'subjectName' => function ($subject) { return $subject['subject_name'] ?? null; },
                'subjectCode' => function ($subject) { return $subject['subject_code'] ?? null; },
                'description' => function ($subject) { return $subject['description'] ?? null; },
                'topics' => [SubjectResolver::class, 'getSubjectTopics'],
            ],
            'ExamSubject' => [
                'examSubjectId' => function ($es) { return $es['exam_subject_id'] ?? null; },
                'exam' => [ExamResolver::class, 'resolveExamSubjectExam'],
                'subject' => [ExamResolver::class, 'resolveExamSubjectSubject'],
                'numberOfQuestions' => function ($es) { return $es['number_of_questions'] ?? null; },
                'timeLimitSeconds' => function ($es) { return $es['time_limit_seconds'] ?? null; },
                'scoringScheme' => function ($es) { return $es['scoring_scheme'] ?? null; },
            ],
            'Topic' => [
                'topicId' => function ($topic) { return $topic['topic_id'] ?? null; },
                'subject' => [SubjectResolver::class, 'resolveTopicSubject'],
                'topicName' => function ($topic) { return $topic['topic_name'] ?? null; },
                'description' => function ($topic) { return $topic['description'] ?? null; },
            ],
            'Question' => [
                'questionId' => function ($q) { return $q['question_id'] ?? null; },
                'examSubject' => [QuestionResolver::class, 'resolveQuestionExamSubject'],
                'topic' => [QuestionResolver::class, 'resolveQuestionTopic'],
                'questionText' => function ($q) { return $q['question_text'] ?? null; },
                'questionType' => function ($q) { return $q['question_type'] ?? null; },
                'correctAnswer' => function ($q) { return $q['correct_answer'] ?? null; },
                'explanation' => function ($q) { return $q['explanation'] ?? null; },
                'difficultyLevel' => function ($q) { return $q['difficulty_level'] ?? null; },
                'creationDate' => function ($q) { return $q['creation_date'] ?? null; },
                'createdBy' => [QuestionResolver::class, 'resolveQuestionCreatedByUser'],
                'lastModifiedDate' => function ($q) { return $q['last_modified_date'] ?? null; },
                'options' => [QuestionResolver::class, 'resolveQuestionOptions'],
            ],
            'QuestionOption' => [
                'optionId' => function ($opt) { return $opt['option_id'] ?? null; },
                'question' => [QuestionResolver::class, 'resolveQuestionOptionQuestion'],
                'optionLetter' => function ($opt) { return $opt['option_letter'] ?? null; },
                'optionText' => function ($opt) { return $opt['option_text'] ?? null; },
                'isCorrect' => function ($opt) { return isset($opt['is_correct']) ? (bool)$opt['is_correct'] : null; },
            ],
            'StudentSession' => [
                'sessionId' => function ($s) { return $s['session_id'] ?? null; },
                'user' => [StudentSessionResolver::class, 'resolveSessionUser'],
                'examSubject' => [StudentSessionResolver::class, 'resolveSessionExamSubject'],
                'startTime' => function ($s) { return $s['start_time'] ?? null; },
                'endTime' => function ($s) { return $s['end_time'] ?? null; },
                'totalQuestions' => function ($s) { return $s['total_questions'] ?? null; },
                'timeAllocatedSeconds' => function ($s) { return $s['time_allocated_seconds'] ?? null; },
                'sessionType' => function ($s) { return $s['session_type'] ?? null; },
                'settings' => function ($s) { return !empty($s['settings']) ? json_decode($s['settings'], true) : null; },
                'answers' => [StudentSessionResolver::class, 'resolveSessionAnswers'],
            ],
            'StudentAnswer' => [
                'answerId' => function ($ans) { return $ans['answer_id'] ?? null; },
                'session' => [StudentSessionResolver::class, 'resolveAnswerSession'],
                'question' => [StudentSessionResolver::class, 'resolveAnswerQuestion'],
                'submittedAnswer' => function ($ans) { return $ans['submitted_answer'] ?? null; },
                'isCorrect' => function ($ans) { return isset($ans['is_correct']) ? (bool)$ans['is_correct'] : null; },
                'submissionTime' => function ($ans) { return $ans['submission_time'] ?? null; },
                'timeTakenSeconds' => function ($ans) { return $ans['time_taken_seconds'] ?? null; },
            ],
            'StudentSessionResult' => [
                'sessionId' => function ($res) { return $res['sessionId'] ?? null; },
                'user' => [StudentSessionResolver::class, 'resolveResultUser'],
                'examSubject' => [StudentSessionResolver::class, 'resolveResultExamSubject'],
                'startTime' => function ($res) { return $res['startTime'] ?? null; },
                'endTime' => function ($res) { return $res['endTime'] ?? null; },
                'totalQuestionsInSession' => function ($res) { return $res['totalQuestionsInSession'] ?? null; },
                'totalQuestionsAnswered' => function ($res) { return $res['totalQuestionsAnswered'] ?? null; },
                'correctAnswers' => function ($res) { return $res['correctAnswers'] ?? null; },
                'incorrectAnswers' => function ($res) { return $res['incorrectAnswers'] ?? null; },
                'scorePercentage' => function ($res) { return $res['scorePercentage'] ?? null; },
                'totalTimeTakenSeconds' => function ($res) { return $res['totalTimeTakenSeconds'] ?? null; },
                'answers' => function ($res) { return $res['answers'] ?? []; }, // Ensure it returns an array
            ],
        ];
    }
}