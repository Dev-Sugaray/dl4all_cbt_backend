# DL4ALL CBT Platform Backend

A Computer-Based Testing (CBT) platform backend implemented using PHP with GraphQL for the API layer and MySQL for data storage. This system is designed to efficiently handle JAMB-style examinations, student sessions, and results.

## System Overview

This backend provides a comprehensive API for managing:
- User authentication and management
- Exam and subject management
- Question creation and organization
- Student session management
- Answer submission and grading
- Results and analytics

## Technology Stack

- **Backend Language**: PHP 8.0+
- **API Layer**: GraphQL
- **Database**: MySQL 8.0+
- **Authentication**: JWT (JSON Web Tokens)
- **Dependency Management**: Composer

## Project Structure

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

## Getting Started

### Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Composer

### Installation

1. Clone the repository
   ```
   git clone https://github.com/your-organization/dl4all_cbt_backend.git
   cd dl4all_cbt_backend
   ```

2. Install dependencies
   ```
   composer install
   ```

3. Create a `.env` file based on `.env.example` and configure your environment variables

4. Set up the database
   ```
   mysql -u your_username -p < db.sql
   ```

5. Start the development server
   ```
   php -S localhost:8000 -t public
   ```

## Documentation

For detailed information about the system requirements and implementation plan, please refer to the [REQUIREMENTS.md](REQUIREMENTS.md) file.

## GraphQL API

The API is accessible at `/graphql` endpoint. A GraphQL playground will be available in development mode for testing queries and mutations.

## Authentication

The system uses JWT (JSON Web Tokens) for authentication. To access protected endpoints, include the JWT token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## License

This project is proprietary and confidential.

## Contact

For any inquiries, please contact info@dl4all.com.