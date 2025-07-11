-- Database: cbt_platform

-- Drop the database if it exists
DROP DATABASE IF EXISTS cbt_platform;

-- Create the database
CREATE DATABASE cbt_platform;

-- Use the database
USE cbt_platform;

-- --------------------------------------------------------

-- Table structure for table `Users`
-- --------------------------------------------------------

CREATE TABLE `Users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_role` ENUM('student', 'administrator', 'content_creator') NOT NULL,
  `registration_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `hashed_password` VARCHAR(255) NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `full_name` VARCHAR(255) NULL,
  `date_of_birth` DATE NULL,
  `gender` ENUM('male', 'female', 'other') NULL,
  `institution` VARCHAR(255) NULL,
  `study_level` VARCHAR(100) NULL,
  `preferred_exam_types` SET('WAEC', 'NECO', 'JAMB', 'NABTEB') NULL,
  `admin_name` VARCHAR(255) NULL,
  `permissions` TEXT NULL,
  `creator_name` VARCHAR(255) NULL,
  `expertise_area` VARCHAR(255) NULL,
  `creation_credentials` TEXT NULL,
  `password_reset_token` VARCHAR(255) UNIQUE NULL,
  `email_verification_token` VARCHAR(255) UNIQUE NULL,
  `profile_picture_url` VARCHAR(255) NULL
);

-- --------------------------------------------------------

-- Table structure for table `Exams`
-- --------------------------------------------------------

CREATE TABLE `Exams` (
  `exam_id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_name` VARCHAR(255) UNIQUE NOT NULL,
  `exam_abbreviation` VARCHAR(50) UNIQUE NOT NULL,
  `description` TEXT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `creation_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------

-- Table structure for table `Subjects`
-- --------------------------------------------------------

CREATE TABLE `Subjects` (
  `subject_id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_name` VARCHAR(255) UNIQUE NOT NULL,
  `subject_code` VARCHAR(50) UNIQUE NULL,
  `description` TEXT NULL
);

-- --------------------------------------------------------

-- Table structure for table `ExamSubjects`
-- --------------------------------------------------------

CREATE TABLE `ExamSubjects` (
  `exam_subject_id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_id` INT,
  `subject_id` INT,
  `number_of_questions` INT NOT NULL,
  `time_limit_seconds` INT NOT NULL,
  `scoring_scheme` TEXT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (`exam_id`) REFERENCES `Exams` (`exam_id`),
  FOREIGN KEY (`subject_id`) REFERENCES `Subjects` (`subject_id`)
);

-- --------------------------------------------------------
-- Table structure for table `Topics`
-- --------------------------------------------------------
CREATE TABLE `Topics` (
  `topic_id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT,
  `topic_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  FOREIGN KEY (`subject_id`) REFERENCES `Subjects` (`subject_id`)
);

-- --------------------------------------------------------

-- Table structure for table `Questions`
-- --------------------------------------------------------

CREATE TABLE `Questions` (
  `question_id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_subject_id` INT,
  `topic_id` INT,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('multiple_choice', 'true_false', 'fill_in_the_blanks') NOT NULL,
  `correct_answer` TEXT NOT NULL,
  `explanation` TEXT NULL,
  `difficulty_level` ENUM('easy', 'medium', 'hard') NULL,
  `creation_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by_user_id` INT,
  `last_modified_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`exam_subject_id`) REFERENCES `ExamSubjects` (`exam_subject_id`),
  FOREIGN KEY (`topic_id`) REFERENCES `Topics` (`topic_id`),
  FOREIGN KEY (`created_by_user_id`) REFERENCES `Users` (`user_id`)
);

-- --------------------------------------------------------

-- Table structure for table `QuestionOptions`
-- --------------------------------------------------------

CREATE TABLE `QuestionOptions` (
  `option_id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_id` INT,
  `option_letter` VARCHAR(10) NOT NULL,
  `option_text` TEXT NOT NULL,
  `is_correct` BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (`question_id`) REFERENCES `Questions` (`question_id`)
);

-- --------------------------------------------------------

-- Table structure for table `StudentSessions`
-- --------------------------------------------------------

CREATE TABLE `StudentSessions` (
  `session_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `exam_subject_id` INT,
  `start_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `end_time` TIMESTAMP NULL,
  `total_questions` INT NOT NULL,
  `time_allocated_seconds` INT NULL,
  `session_type` ENUM('practice', 'exam') DEFAULT 'practice',
  `settings` JSON NULL,
  FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`),
  FOREIGN KEY (`exam_subject_id`) REFERENCES `ExamSubjects` (`exam_subject_id`)
);

-- --------------------------------------------------------

-- Table structure for table `StudentAnswers`
-- --------------------------------------------------------

CREATE TABLE `StudentAnswers` (
  `answer_id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT,
  `question_id` INT,
  `submitted_answer` TEXT NULL,
  `is_correct` BOOLEAN NULL,
  `submission_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `time_taken_seconds` INT NULL,
  FOREIGN KEY (`session_id`) REFERENCES `StudentSessions` (`session_id`),
  FOREIGN KEY (`question_id`) REFERENCES `Questions` (`question_id`)
);

-- --------------------------------------------------------

--  Sample Data Inserts
-- --------------------------------------------------------
-- Insert sample data into the Users table
INSERT INTO Users (user_role, email, hashed_password, full_name, is_active)
VALUES
('student', 'student1@example.com', '$2a$10$passwordhash1', 'John Doe', TRUE),
('administrator', 'admin1@example.com', '$2a$10$adminpasswordhash', 'Jane Smith', TRUE),
('content_creator', 'creator1@example.com', '$2a$10$creatorpasswordhash', 'Mike Johnson', TRUE);

-- Insert sample data into the Exams table
INSERT INTO Exams (exam_name, exam_abbreviation, description)
VALUES
('West African Examinations Council', 'WAEC', 'Secondary School Certificate Examination'),
('National Examination Council', 'NECO', 'Senior School Certificate Examination'),
('Joint Admissions and Matriculation Board', 'JAMB', 'University Tertiary Matriculation Examination');

-- Insert sample data into the Subjects table
INSERT INTO Subjects (subject_name, subject_code, description)
VALUES
('Mathematics', 'MAT', 'Core Mathematics'),
('English Language', 'ENG', 'English Language'),
('Physics', 'PHY', 'Physics');

-- Insert sample data into the ExamSubjects table
INSERT INTO ExamSubjects (exam_id, subject_id, number_of_questions, time_limit_seconds)
VALUES
(1, 1, 50, 3600), -- WAEC Mathematics, 1 hour
(1, 2, 60, 3600), -- WAEC English, 1 hour
(2, 1, 50, 3600), -- NECO Mathematics, 1 hour
(3, 1, 40, 2400),  -- JAMB Math, 40 mins
(3, 2, 60, 2400);  -- JAMB English 40 mins

-- Insert sample data into the Topics table
INSERT INTO Topics (subject_id, topic_name, description)
VALUES
(1, 'Algebra', 'Basic algebraic operations'),
(1, 'Calculus', 'Differential and integral calculus'),
(2, 'Grammar', 'English grammar rules'),
(2, 'Literature', 'Analysis of literary texts'),
(3, 'Mechanics', 'Classical mechanics principles'),
(3, 'Optics', 'The study of light and vision');

-- Insert sample data into the Questions table
INSERT INTO Questions (exam_subject_id, topic_id, question_text, question_type, correct_answer, explanation, difficulty_level, created_by_user_id) VALUES
(1, 1, 'Solve for x: 2x + 5 = 15', 'multiple_choice', '5', 'Subtract 5 from both sides, then divide by 2.', 'easy', 3),
(1, 1, 'What is the value of pi to two decimal places?', 'fill_in_the_blanks', '3.14', NULL, 'easy', 3),
(2, 3, 'Choose the correct tense: I ___ to the store yesterday.', 'multiple_choice', 'went', 'The word yesterday indicates past tense.', 'easy', 3),
(3, 5, 'What is Newton\'s first law of motion?', 'true_false', 'An object at rest stays at rest and an object in motion stays in motion with a constant velocity, unless acted upon by a net external force.', NULL, 'medium', 3),
(5, 3, 'Identify the adverb in the sentence: "He ran quickly."', 'multiple_choice', 'quickly', 'Quickly describes how he ran.', 'easy', 3);

-- Insert sample data into the QuestionOptions
INSERT INTO QuestionOptions (question_id, option_letter, option_text, is_correct) VALUES
(1, 'A', '2', FALSE),
(1, 'B', '5', TRUE),
(1, 'C', '10', FALSE),
(1, 'D', '20', FALSE),
(3, 'A', 'go', FALSE),
(3, 'B', 'gone', FALSE),
(3, 'C', 'went', TRUE),
(3, 'D', 'going', FALSE);

-- Insert sample data into StudentSessions
INSERT INTO StudentSessions (user_id, exam_subject_id, start_time, total_questions, time_allocated_seconds, session_type)
VALUES
(1, 1, NOW(), 50, 3600, 'practice'),
(1, 3, NOW(), 50, 3600, 'exam');

-- Insert sample data into StudentAnswers
INSERT INTO StudentAnswers (session_id, question_id, submitted_answer, is_correct, submission_time, time_taken_seconds)
VALUES
(1, 1, '5', TRUE, NOW(), 60),
(1, 2, '3.14', TRUE, NOW(), 120),
(2, 4, 'True', NULL, NOW(), 180);
