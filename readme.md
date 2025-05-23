# CBT Platform Backend API Documentation

This document provides an overview and documentation for the backend API of the Computer-Based Testing (CBT) platform.

## Technologies Used

*   **Language:** PHP
*   **Database:** MySQL
*   **Dependency Management:** Composer
*   **API Style:** REST
*   **Authentication:** JWT (JSON Web Tokens)

## Setup Instructions

1.  **Clone the repository:**
    ```bash
    git clone <repository_url>
    cd dl4all_cbt_backend
    ```
2.  **Install dependencies:**
    Make sure you have Composer installed. Then run:
    ```bash
    composer install
    ```
3.  **Database Setup:**
    *   Ensure you have a MySQL server running.
    *   Create a database for the project.
    *   Import the database schema from the `db.sql` file located in the project root.
    *   Configure the database connection in `config/database.php`.
4.  **Web Server Configuration:**
    *   Configure your web server (e.g., Apache, Nginx) to point the document root to the `public` directory (or the directory containing `index.php`).
    *   Ensure that URL rewriting is enabled to allow the routing mechanism to work correctly.
5.  **Environment Variables:**
    *   Set up necessary environment variables for database connection and JWT secret key. (Consider using a `.env` file and a library like `vlucas/phpdotenv`).

## Database Schema

The database schema is defined in the `db.sql` file. It includes tables for Users, Exams, Subjects, Topics, Questions, QuestionOptions, StudentSessions, and StudentAnswers. Refer to the `db.sql` file for the detailed schema.

## Authentication

This API uses JWT (JSON Web Tokens) for authentication. 

*   Users can register and log in using the `/api/v1/users/register` and `/api/v1/users/login` endpoints.
*   Upon successful login, a JWT is returned.
*   For protected routes, include the JWT in the `Authorization` header as a Bearer token: `Authorization: Bearer <your_jwt_token>`.
*   The `middleware/AuthMiddleware.php` handles the JWT validation.

## API Endpoints

The API endpoints are defined in `routes/api.php`. Below is a list of the implemented endpoints:

| Method | Endpoint                     | Description                                     | Authentication Required |
| :----- | :--------------------------- | :---------------------------------------------- | :---------------------- |
| GET    | `/api/v1/test`               | Example test endpoint.                          | No                      |
| POST   | `/api/v1/users/register`     | Register a new user.                            | No                      |

**Example Request:**
```http
POST /api/v1/users/register HTTP/1.1
Host: your-api-url.com
Content-Type: application/json

{
    "user_role": "student",
    "email": "testuser@example.com",
    "password": "securepassword123",
    "full_name": "Test User"
}
```

**Example Success Response (201 Created):**
```json
{
    "status": "success",
    "message": "User registered successfully",
    "data": {
        "user_id": 1,
        "email": "testuser@example.com",
        "user_role": "student"
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."]
    }
}
```
| POST   | `/api/v1/users/login`        | Log in a user and get JWT.                      | No                      |

**Example Request:**
```http
POST /api/v1/users/login HTTP/1.1
Host: your-api-url.com
Content-Type: application/json

{
    "email": "testuser@example.com",
    "password": "securepassword123"
}
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "user_id": 1,
        "email": "testuser@example.com",
        "user_role": "student",
        "token": "your_jwt_token_here"
    }
}
```

**Example Error Response (401 Unauthorized - Invalid Credentials):**
```json
{
    "status": "error",
    "message": "Invalid email or password"
}
```
| GET    | `/api/v1/users/profile`      | Get the profile of the authenticated user.      | Yes                     |

**Example Request:**
```http
GET /api/v1/users/profile HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "User profile retrieved successfully",
    "data": {
        "user_id": 1,
        "user_role": "student",
        "registration_timestamp": "2023-10-27 10:00:00",
        "last_login": "2023-10-27 11:00:00",
        "email": "testuser@example.com",
        "full_name": "Test User",
        "date_of_birth": "2000-01-01",
        "gender": "male",
        "institution": "Example University",
        "study_level": "Undergraduate",
        "preferred_exam_types": ["JAMB", "WAEC"],
        "is_active": true,
        "profile_picture_url": null
    }
}
```

**Example Error Response (401 Unauthorized - Missing or Invalid Token):**
```json
{
    "status": "error",
    "message": "Unauthorized: Missing or invalid token"
}
```
| GET    | `/api/v1/users`              | Get all users (with pagination).                | Yes (Admin/Content Creator) |

**Example Request:**
```http
GET /api/v1/users?page=1&limit=10 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_admin_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Users retrieved successfully",
    "data": [
        {
            "user_id": 1,
            "user_role": "student",
            "email": "student1@example.com",
            "full_name": "Student One"
        },
        {
            "user_id": 2,
            "user_role": "administrator",
            "email": "admin@example.com",
            "full_name": "Admin User"
        }
    ],
    "pagination": {
        "total": 100,
        "page": 1,
        "limit": 10
    }
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| GET    | `/api/v1/users/{id}`         | Get a user by ID.                               | Yes (Admin/Content Creator) |

**Example Request:**
```http
GET /api/v1/users/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_admin_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "User retrieved successfully",
    "data": {
        "user_id": 1,
        "user_role": "student",
        "registration_timestamp": "2023-10-27 10:00:00",
        "last_login": "2023-10-27 11:00:00",
        "email": "student1@example.com",
        "full_name": "Student One",
        "date_of_birth": "2000-01-01",
        "gender": "male",
        "institution": "Example University",
        "study_level": "Undergraduate",
        "preferred_exam_types": ["JAMB", "WAEC"],
        "is_active": true,
        "profile_picture_url": null
    }
}
```

**Example Error Response (404 Not Found - User Not Found):**
```json
{
    "status": "error",
    "message": "User not found"
}
```
| PUT    | `/api/v1/users/{id}`         | Update a user by ID.                           | Yes (Admin/Content Creator) |

**Example Request:**
```http
PUT /api/v1/users/1 HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

{
    "full_name": "Updated Student One",
    "institution": "New University"
}
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "User updated successfully",
    "data": {
        "user_id": 1,
        "full_name": "Updated Student One",
        "institution": "New University"
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "full_name": ["The full name field is required."]
    }
}
```
| DELETE | `/api/v1/users/{id}`         | Delete a user by ID (soft delete).              | Yes (Admin)             |

**Example Request:**
```http
DELETE /api/v1/users/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_admin_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "User deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/exams`              | Create a new exam.                              | Yes (Admin/Content Creator) |

**Example Request:**
```http
POST /api/v1/exams HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

{
    "exam_name": "Unified Tertiary Matriculation Examination",
    "exam_abbreviation": "JAMB",
    "description": "Joint Admissions and Matriculation Board exam"
}
```

**Example Success Response (201 Created):**
```json
{
    "status": "success",
    "message": "Exam created successfully",
    "data": {
        "exam_id": 1,
        "exam_name": "Unified Tertiary Matriculation Examination",
        "exam_abbreviation": "JAMB"
    }
}
```

**Example Error Response (409 Conflict - Duplicate Entry):**
```json
{
    "status": "error",
    "message": "Exam with this name or abbreviation already exists"
}
```
| GET    | `/api/v1/exams`              | Get all exams (with pagination).                | No                      |

**Example Request:**
```http
GET /api/v1/exams?page=1&limit=10 HTTP/1.1
Host: your-api-url.com
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exams retrieved successfully",
    "data": [
        {
            "exam_id": 1,
            "exam_name": "Unified Tertiary Matriculation Examination",
            "exam_abbreviation": "JAMB",
            "is_active": true
        },
        {
            "exam_id": 2,
            "exam_name": "West African Senior School Certificate Examination",
            "exam_abbreviation": "WAEC",
            "is_active": true
        }
    ],
    "pagination": {
        "total": 5,
        "page": 1,
        "limit": 10
    }
}
```
| GET    | `/api/v1/exams/{id}`         | Get an exam by ID.                              | No                      |

**Example Request:**
```http
GET /api/v1/exams/1 HTTP/1.1
Host: your-api-url.com
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exam retrieved successfully",
    "data": {
        "exam_id": 1,
        "exam_name": "Unified Tertiary Matriculation Examination",
        "exam_abbreviation": "JAMB",
        "description": "Joint Admissions and Matriculation Board exam",
        "is_active": true,
        "creation_date": "2023-10-27 10:00:00"
    }
}
```

**Example Error Response (404 Not Found - Exam Not Found):**
```json
{
    "status": "error",
    "message": "Exam not found"
}
```
| PUT    | `/api/v1/exams/{id}`         | Update an exam by ID.                           | Yes (Admin/Content Creator) |

**Example Request:**
```http
PUT /api/v1/exams/1 HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

{
    "description": "Updated description for JAMB exam"
}
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exam updated successfully",
    "data": {
        "exam_id": 1,
        "description": "Updated description for JAMB exam"
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "exam_name": ["The exam name field is required."]
    }
}
```
| DELETE | `/api/v1/exams/{id}`         | Delete an exam by ID (soft delete).             | Yes (Admin)             |

**Example Request:**
```http
DELETE /api/v1/exams/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_admin_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exam deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/subjects`           | Create a new subject.                           | Yes (Admin/Content Creator) |

**Example Request:**
```http
POST /api/v1/subjects HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

{
    "subject_name": "Mathematics",
    "subject_code": "MATH",
    "description": "Core mathematics topics"
}
```

**Example Success Response (201 Created):**
```json
{
    "status": "success",
    "message": "Subject created successfully",
    "data": {
        "subject_id": 1,
        "subject_name": "Mathematics",
        "subject_code": "MATH"
    }
}
```

**Example Error Response (409 Conflict - Duplicate Entry):**
```json
{
    "status": "error",
    "message": "Subject with this name or code already exists"
}
```
| GET    | `/api/v1/subjects`           | Get all subjects (with pagination).             | No                      |

**Example Request:**
```http
GET /api/v1/subjects?page=1&limit=10 HTTP/1.1
Host: your-api-url.com
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Subjects retrieved successfully",
    "data": [
        {
            "subject_id": 1,
            "subject_name": "Mathematics",
            "subject_code": "MATH"
        },
        {
            "subject_id": 2,
            "subject_name": "English Language",
            "subject_code": "ENG"
        }
    ],
    "pagination": {
        "total": 10,
        "page": 1,
        "limit": 10
    }
}
```
| GET    | `/api/v1/subjects/{id}`      | Get a subject by ID.                            | No                      |

**Example Request:**
```http
GET /api/v1/subjects/1 HTTP/1.1
Host: your-api-url.com
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Subject retrieved successfully",
    "data": {
        "subject_id": 1,
        "subject_name": "Mathematics",
        "subject_code": "MATH",
        "description": "Core mathematics topics"
    }
}
```

**Example Error Response (404 Not Found - Subject Not Found):**
```json
{
    "status": "error",
    "message": "Subject not found"
}
```
| PUT    | `/api/v1/subjects/{id}`      | Update a subject by ID.                         | Yes (Admin/Content Creator) |

**Example Request:**
```http
PUT /api/v1/subjects/1 HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

{
    "description": "Updated description for Mathematics"
}
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Subject updated successfully",
    "data": {
        "subject_id": 1,
        "description": "Updated description for Mathematics"
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "subject_name": ["The subject name field is required."]
    }
}
```
| DELETE | `/api/v1/subjects/{id}`      | Delete a subject by ID.                         | Yes (Admin)             |

**Example Request:**
```http
DELETE /api/v1/subjects/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_admin_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Subject deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/topics`             | Create a new topic.                             | Yes (Admin/Content Creator) |
| GET    | `/api/v1/topics`             | Get all topics (with pagination).               | No                      |
| GET    | `/api/v1/topics/{id}`        | Get a topic by ID.                              | No                      |
| PUT    | `/api/v1/topics/{id}`        | Update a topic by ID.                           | Yes (Admin/Content Creator) |
| DELETE | `/api/v1/topics/{id}`        | Delete a topic by ID.                           | Yes (Admin/Content Creator) |

**Example Request:**
```http
DELETE /api/v1/topics/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_admin_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Topic deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/questions`          | Create a new question.                          | Yes (Admin/Content Creator) |
| GET    | `/api/v1/questions`          | Get all questions (with pagination).            | No                      |
| GET    | `/api/v1/questions/{id}`     | Get a question by ID.                           | No                      |
| PUT    | `/api/v1/questions/{id}`     | Update a question by ID.                        | Yes (Admin/Content Creator) |
| DELETE | `/api/v1/questions/{id}`     | Delete a question by ID.                        | Yes (Admin)             |

**Example Request:**
```http
DELETE /api/v1/questions/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_creator_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Question deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/questions/bulk`     | Create questions in bulk.                       | Yes (Admin/Content Creator) |

**Example Request:**
```http
POST /api/v1/questions/bulk HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_creator_jwt_token_here

[
    {
        "exam_subject_id": 1,
        "topic_id": 1,
        "question_text": "What is the capital of Ghana?",
        "question_type": "multiple_choice",
        "correct_answer": "Accra",
        "explanation": "Accra is the capital of Ghana.",
        "difficulty_level": "easy",
        "options": [
            {"option_letter": "A", "option_text": "Kumasi", "is_correct": false},
            {"option_letter": "B", "option_text": "Accra", "is_correct": true},
            {"option_letter": "C", "option_text": "Takoradi", "is_correct": false}
        ]
    },
    {
        "exam_subject_id": 1,
        "topic_id": 2,
        "question_text": "Simplify: 5x + 3x - 2x",
        "question_type": "multiple_choice",
        "correct_answer": "6x",
        "explanation": "Combine like terms: (5+3-2)x = 6x.",
        "difficulty_level": "medium",
        "options": [
            {"option_letter": "A", "option_text": "4x", "is_correct": false},
            {"option_letter": "B", "option_text": "6x", "is_correct": true},
            {"option_letter": "C", "option_text": "8x", "is_correct": false}
        ]
    }
]
```

**Example Success Response (201 Created):**
```json
{
    "status": "success",
    "message": "Questions created successfully",
    "data": [
        {
            "question_id": 10,
            "exam_subject_id": 1,
            "topic_id": 1,
            "question_text": "What is the capital of Ghana?",
            "question_type": "multiple_choice"
        },
        {
            "question_id": 11,
            "exam_subject_id": 1,
            "topic_id": 2,
            "question_text": "Simplify: 5x + 3x - 2x",
            "question_type": "multiple_choice"
        }
    ]
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "[0].question_text": ["The question text field is required."]
    }
}
```
| PUT    | `/api/v1/questions/bulk`     | Update questions in bulk.                       | Yes (Admin/Content Creator) |

**Example Request:**
```http
PUT /api/v1/questions/bulk HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_creator_jwt_token_here

[
    {
        "question_id": 10,
        "question_text": "What is the current capital of Ghana?",
        "difficulty_level": "medium"
    },
    {
        "question_id": 11,
        "explanation": "Combine like terms: (5+3-2)x = 6x."
    }
]
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Questions updated successfully",
    "data": [
        {
            "question_id": 10,
            "question_text": "What is the current capital of Ghana?",
            "difficulty_level": "medium"
        },
        {
            "question_id": 11,
            "explanation": "Combine like terms: (5+3-2)x = 6x."
        }
    ]
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "[0].question_type": ["The question type field is required."]
    }
}
```
| DELETE | `/api/v1/questions/bulk`   | Delete questions in bulk.                       | Yes (Admin/Content Creator) |

**Example Request:**
```http
DELETE /api/v1/questions/bulk HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

[
    10,
    11
]
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Questions deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/exam-subjects`      | Create a new exam-subject association.          | Yes (Admin/Content Creator) |

**Example Request:**
```http
POST /api/v1/exam-subjects HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

{
    "exam_id": 1,
    "subject_id": 1,
    "number_of_questions": 50,
    "time_limit_seconds": 3600,
    "scoring_scheme": "{'correct': 4, 'incorrect': -1, 'unanswered': 0}"
}
```

**Example Success Response (201 Created):**
```json
{
    "status": "success",
    "message": "Exam-Subject association created successfully",
    "data": {
        "exam_subject_id": 1,
        "exam_id": 1,
        "subject_id": 1,
        "number_of_questions": 50
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "exam_id": ["The exam id field is required."]
    }
}
```
| GET    | `/api/v1/exam-subjects`      | Get all exam-subject associations (with pagination). | No                      |

**Example Request:**
```http
GET /api/v1/exam-subjects?page=1&limit=10&exam_id=1 HTTP/1.1
Host: your-api-url.com
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exam-Subject associations retrieved successfully",
    "data": [
        {
            "exam_subject_id": 1,
            "exam_id": 1,
            "subject_id": 1,
            "number_of_questions": 50,
            "time_limit_seconds": 3600
        },
        {
            "exam_subject_id": 2,
            "exam_id": 1,
            "subject_id": 2,
            "number_of_questions": 40,
            "time_limit_seconds": 2400
        }
    ],
    "pagination": {
        "total": 5,
        "page": 1,
        "limit": 10
    }
}
```
| GET    | `/api/v1/exam-subjects/{id}` | Get an exam-subject association by ID.          | No                      |

**Example Request:**
```http
GET /api/v1/exam-subjects/1 HTTP/1.1
Host: your-api-url.com
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exam-Subject association retrieved successfully",
    "data": {
        "exam_subject_id": 1,
        "exam_id": 1,
        "subject_id": 1,
        "number_of_questions": 50,
        "time_limit_seconds": 3600,
        "scoring_scheme": "{'correct': 4, 'incorrect': -1, 'unanswered': 0}"
    }
}
```

**Example Error Response (404 Not Found - Association Not Found):**
```json
{
    "status": "error",
    "message": "Exam-Subject association not found"
}
```
| PUT    | `/api/v1/exam-subjects/{id}` | Update an exam-subject association by ID.       | Yes (Admin/Content Creator) |

**Example Request:**
```http
PUT /api/v1/exam-subjects/1 HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_admin_jwt_token_here

{
    "number_of_questions": 60,
    "time_limit_seconds": 4200
}
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exam-Subject association updated successfully",
    "data": {
        "exam_subject_id": 1,
        "number_of_questions": 60,
        "time_limit_seconds": 4200
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "number_of_questions": ["The number of questions field is required."]
    }
}
```
| DELETE | `/api/v1/exam-subjects/{id}` | Delete an exam-subject association by ID.       | Yes (Admin/Content Creator) |

**Example Request:**
```http
DELETE /api/v1/exam-subjects/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_admin_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Exam-Subject association deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/student-sessions`   | Create a new student session.                   | Yes (Student)           |

**Example Request:**
```http
POST /api/v1/student-sessions HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_student_jwt_token_here

{
    "user_id": 1,
    "exam_subject_id": 1,
    "total_questions": 50,
    "time_allocated_seconds": 3600,
    "session_type": "exam",
    "settings": {"shuffle_questions": true, "show_answers": false}
}
```

**Example Success Response (201 Created):**
```json
{
    "status": "success",
    "message": "Student session created successfully",
    "data": {
        "session_id": 1,
        "user_id": 1,
        "exam_subject_id": 1,
        "start_time": "2023-10-27 12:00:00",
        "session_type": "exam"
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "exam_subject_id": ["The exam subject id field is required."]
    }
}
```
| GET    | `/api/v1/student-sessions`   | Get all student sessions (with pagination).     | Yes (Student/Admin)     |

**Example Request:**
```http
GET /api/v1/student-sessions?page=1&limit=10&user_id=1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_student_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student sessions retrieved successfully",
    "data": [
        {
            "session_id": 1,
            "user_id": 1,
            "exam_subject_id": 1,
            "start_time": "2023-10-27 12:00:00",
            "end_time": null,
            "session_type": "exam"
        },
        {
            "session_id": 2,
            "user_id": 1,
            "exam_subject_id": 2,
            "start_time": "2023-10-27 13:00:00",
            "end_time": "2023-10-27 13:30:00",
            "session_type": "practice"
        }
    ],
    "pagination": {
        "total": 15,
        "page": 1,
        "limit": 10
    }
}
```
| GET    | `/api/v1/student-sessions/{id}`| Get a student session by ID.                    | Yes (Student/Admin)     |

**Example Request:**
```http
GET /api/v1/student-sessions/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_student_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student session retrieved successfully",
    "data": {
        "session_id": 1,
        "user_id": 1,
        "exam_subject_id": 1,
        "start_time": "2023-10-27 12:00:00",
        "end_time": null,
        "total_questions": 50,
        "time_allocated_seconds": 3600,
        "session_type": "exam",
        "settings": {"shuffle_questions": true, "show_answers": false}
    }
}
```

**Example Error Response (404 Not Found - Session Not Found):**
```json
{
    "status": "error",
    "message": "Student session not found"
}
```
| PUT    | `/api/v1/student-sessions/{id}`| Update a student session by ID.                 | Yes (Student)           |

**Example Request:**
```http
PUT /api/v1/student-sessions/1 HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_student_jwt_token_here

{
    "end_time": "2023-10-27 13:00:00"
}
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student session updated successfully",
    "data": {
        "session_id": 1,
        "end_time": "2023-10-27 13:00:00"
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "end_time": ["The end time field must be a valid timestamp."]
    }
}
```
| DELETE | `/api/v1/student-sessions/{id}`| Delete a student session by ID.                 | Yes (Student)           |

**Example Request:**
```http
DELETE /api/v1/student-sessions/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_student_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student session deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```
| POST   | `/api/v1/student-answers`    | Submit a student answer.                        | Yes (Student)           |

**Example Request:**
```http
POST /api/v1/student-answers HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_student_jwt_token_here

{
    "session_id": 1,
    "question_id": 1,
    "submitted_answer": "Abuja",
    "is_correct": true,
    "time_taken_seconds": 30
}
```

**Example Success Response (201 Created):**
```json
{
    "status": "success",
    "message": "Student answer submitted successfully",
    "data": {
        "answer_id": 1,
        "session_id": 1,
        "question_id": 1,
        "submitted_answer": "Abuja"
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "session_id": ["The session id field is required."]
    }
}
```
| GET    | `/api/v1/student-answers`    | Get all student answers (with pagination).      | Yes (Student/Admin)     |

**Example Request:**
```http
GET /api/v1/student-answers?page=1&limit=10&session_id=1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_student_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student answers retrieved successfully",
    "data": [
        {
            "answer_id": 1,
            "session_id": 1,
            "question_id": 1,
            "submitted_answer": "Abuja",
            "is_correct": true
        },
        {
            "answer_id": 2,
            "session_id": 1,
            "question_id": 2,
            "submitted_answer": "6x",
            "is_correct": true
        }
    ],
    "pagination": {
        "total": 50,
        "page": 1,
        "limit": 10
    }
}
```
| GET    | `/api/v1/student-answers/{id}` | Get a student answer by ID.                     | Yes (Student/Admin)     |

**Example Request:**
```http
GET /api/v1/student-answers/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_student_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student answer retrieved successfully",
    "data": {
        "answer_id": 1,
        "session_id": 1,
        "question_id": 1,
        "submitted_answer": "Abuja",
        "is_correct": true,
        "submission_time": "2023-10-27 12:00:30",
        "time_taken_seconds": 30
    }
}
```

**Example Error Response (404 Not Found - Answer Not Found):**
```json
{
    "status": "error",
    "message": "Student answer not found"
}
```
| PUT    | `/api/v1/student-answers/{id}` | Update a student answer by ID.                  | Yes (Student)           |

**Example Request:**
```http
PUT /api/v1/student-answers/1 HTTP/1.1
Host: your-api-url.com
Content-Type: application/json
Authorization: Bearer your_student_jwt_token_here

{
    "submitted_answer": "Lagos",
    "is_correct": false
}
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student answer updated successfully",
    "data": {
        "answer_id": 1,
        "submitted_answer": "Lagos",
        "is_correct": false
    }
}
```

**Example Error Response (400 Bad Request - Validation Error):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "submitted_answer": ["The submitted answer field is required."]
    }
}
```
| DELETE | `/api/v1/student-answers/{id}` | Delete a student answer by ID.                  | Yes (Student)           |

**Example Request:**
```http
DELETE /api/v1/student-answers/1 HTTP/1.1
Host: your-api-url.com
Authorization: Bearer your_student_jwt_token_here
```

**Example Success Response (200 OK):**
```json
{
    "status": "success",
    "message": "Student answer deleted successfully"
}
```

**Example Error Response (403 Forbidden - Insufficient Permissions):**
```json
{
    "status": "error",
    "message": "Forbidden: Insufficient permissions"
}
```

## Error Handling

The API returns JSON responses with appropriate HTTP status codes for errors. Common error codes include:

*   `400 Bad Request`: Invalid input data.
*   `401 Unauthorized`: Authentication failed (missing or invalid JWT).
*   `403 Forbidden`: Authorization failed (user does not have necessary permissions).
*   `404 Not Found`: Resource not found.
*   `409 Conflict`: Resource already exists (e.g., duplicate entry).
*   `500 Internal Server Error`: Unexpected server error.

## Security

*   Prepared statements are used for all database interactions to prevent SQL injection.
*   Passwords should be hashed using a strong algorithm like bcrypt.
*   Input sanitization should be implemented (ideally on the frontend, but can also be done on the backend).
*   JWTs are used for stateless authentication.

## Contributing

(Add contributing guidelines here if applicable)

## License

(Add license information here)