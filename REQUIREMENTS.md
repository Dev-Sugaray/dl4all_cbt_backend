# CBT Platform Backend System Requirements

## 1. Introduction

This document outlines the system requirements for the Computer-Based Testing (CBT) platform backend. The backend will be implemented using PHP with GraphQL for the API layer and MySQL for data storage. The system is designed to efficiently handle JAMB-style examinations, student sessions, and results.

## 2. System Architecture

### 2.1 Technology Stack

- **Backend Language**: PHP 8.0+
- **API Layer**: GraphQL
- **Database**: MySQL 8.0+
- **Authentication**: JWT (JSON Web Tokens)
- **Development Environment**: Composer for dependency management

### 2.2 Architecture Components

1. **GraphQL API Layer**
   - Schema definitions
   - Resolvers
   - Middleware for authentication and authorization

2. **Business Logic Layer**
   - User management
   - Exam management
   - Question management
   - Session management
   - Answer processing and grading

3. **Data Access Layer**
   - Database connection management
   - Query builders
   - Data validation

4. **Security Layer**
   - Authentication
   - Authorization
   - Input validation
   - Error handling

### 2.3 System Flow

1. Client sends GraphQL request
2. Authentication middleware validates JWT
3. Request is routed to appropriate resolver
4. Resolver interacts with business logic
5. Business logic accesses data through data access layer
6. Response is formatted and returned to client

## 3. Functional Requirements

### 3.1 User Management

- User registration and account creation
- User authentication (login/logout)
- Password reset functionality
- User profile management
- Role-based access control (student, administrator, content creator)

### 3.2 Exam Management

- Create, read, update, and delete exams
- Manage subjects within exams
- Configure exam parameters (time limits, scoring schemes)
- Activate/deactivate exams

### 3.3 Question Management

- Create, read, update, and delete questions
- Support for multiple question types (multiple choice, true/false, fill in the blanks)
- Organize questions by topics
- Set difficulty levels
- Add explanations for answers

### 3.4 Session Management

- Create and manage student exam sessions
- Track session progress and timing
- Support for practice and actual exam modes
- Handle session interruptions and resumptions

### 3.5 Answer Processing

- Record student answers
- Grade answers automatically
- Track time taken per question
- Calculate and store results

### 3.6 Results and Analytics

- Generate individual student results
- Provide performance analytics
- Support for result history

## 4. Non-Functional Requirements

### 4.1 Performance

- Support for at least 1000 concurrent users
- Response time under 500ms for most operations
- Efficient database queries optimized for performance
- Implement caching where appropriate

### 4.2 Security

- Secure authentication using JWT
- Password hashing using bcrypt
- Protection against SQL injection, XSS, and CSRF attacks
- Data encryption for sensitive information
- Rate limiting to prevent brute force attacks

### 4.3 Scalability

- Horizontal scalability to handle increased load
- Database optimization for large datasets
- Efficient resource utilization

### 4.4 Reliability

- System availability of 99.9%
- Proper error handling and logging
- Data backup and recovery procedures

### 4.5 Maintainability

- Well-documented code
- Modular architecture for easy updates
- Comprehensive logging for debugging

## 5. GraphQL Schema Design

### 5.1 Types

- User
- Exam
- Subject
- ExamSubject
- Topic
- Question
- QuestionOption
- StudentSession
- StudentAnswer
- AuthPayload

### 5.2 Queries

- User queries (me, getUser, listUsers)
- Exam queries (getExam, listExams)
- Subject queries (getSubject, listSubjects)
- Question queries (getQuestion, listQuestions, searchQuestions)
- Session queries (getSession, listSessions)
- Result queries (getResults, analyzePerformance)

### 5.3 Mutations

- User mutations (register, login, updateProfile, resetPassword)
- Exam mutations (createExam, updateExam, deleteExam)
- Question mutations (createQuestion, updateQuestion, deleteQuestion)
- Session mutations (startSession, endSession, submitAnswer)

## 6. Database Design

The database schema is already defined in the `db.sql` file and includes the following tables:

- Users
- Exams
- Subjects
- ExamSubjects
- Topics
- Questions
- QuestionOptions
- StudentSessions
- StudentAnswers

## 7. Security Considerations

### 7.1 Authentication

- JWT-based authentication
- Token expiration and refresh mechanism
- Secure storage of tokens

### 7.2 Authorization

- Role-based access control
- Permission validation in resolvers
- Field-level security in GraphQL schema

### 7.3 Data Protection

- Input validation and sanitization
- Prepared statements for database queries
- Protection against common web vulnerabilities

## 8. Implementation Plan

### 8.1 Project Structure

```
dl4all_cbt_backend/
├── config/
│   ├── database.php
│   └── jwt.php
├── src/
│   ├── GraphQL/
│   │   ├── Schema/
│   │   │   ├── schema.graphql
│   │   │   ├── types/
│   │   │   ├── queries/
│   │   │   └── mutations/
│   │   └── Resolvers/
│   ├── Models/
│   ├── Services/
│   ├── Middleware/
│   └── Utils/
├── public/
│   └── index.php
├── vendor/
├── composer.json
├── .env
├── .gitignore
└── README.md
```

### 8.2 Development Phases

1. **Phase 1: Setup and Configuration**
   - Set up project structure
   - Configure database connection
   - Implement JWT authentication

2. **Phase 2: Core GraphQL Implementation**
   - Define GraphQL schema
   - Implement basic resolvers
   - Set up middleware

3. **Phase 3: User Management**
   - Implement user registration and authentication
   - Develop user profile management
   - Set up role-based access control

4. **Phase 4: Exam and Question Management**
   - Implement exam CRUD operations
   - Develop question management
   - Set up topic organization

5. **Phase 5: Session and Answer Processing**
   - Implement session management
   - Develop answer submission and grading
   - Set up result calculation

6. **Phase 6: Testing and Optimization**
   - Perform unit and integration testing
   - Optimize performance
   - Implement caching

7. **Phase 7: Documentation and Deployment**
   - Create API documentation
   - Prepare deployment instructions
   - Finalize system documentation

## 9. Dependencies

- **webonyx/graphql-php**: GraphQL implementation for PHP
- **firebase/php-jwt**: JWT implementation for authentication
- **vlucas/phpdotenv**: Environment variable management
- **guzzlehttp/guzzle**: HTTP client for external API interactions
- **monolog/monolog**: Logging library

## 10. Conclusion

This document outlines the requirements for the CBT platform backend. The system will be implemented using PHP with GraphQL for the API layer and MySQL for data storage. The architecture is designed to be scalable, secure, and maintainable, with a focus on performance and reliability.