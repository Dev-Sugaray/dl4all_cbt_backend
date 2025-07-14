# GEMINI Instructions for AGENTS.md Creation

This document outlines the requirements and specifications for generating the `AGENTS.md` file, which will be used by GEMINI and other AI agents to build the frontend for this backend application.

## 1. Frontend Specification

- **Framework:** Vue.js 3 (Composition API)
- **State Management:** Pinia
- **Styling:** Bootstrap
- **Routing:** Use Vue Router in **hash mode** (URLs will include `#`, e.g., `/#/dashboard`). Do not use history mode.
- **Build Output:** The frontend build (`dist`) will be served from the backend. After building, move the `dist` folder into a new `frontend` directory within the backend project. Modify the backend to serve the frontend as the main index.

---

## 2. Backend API Endpoints

This section provides a detailed list of all available backend endpoints.

### User Management

#### `POST /api/v1/users/register`

- **Description:** Registers a new user.
- **Request Body:**
  ```json
  {
    "email": "test@example.com",
    "password": "password123",
    "user_role": "student"
  }
  ```
- **Success Response (201):**
  ```json
  {
    "message": "User registered successfully.",
    "user_id": 1
  }
  ```
- **Error Response (409):**
  ```json
  {
    "error": "Email already exists."
  }
  ```

#### `POST /api/v1/users/login`

- **Description:** Logs in a user and returns a JWT token.
- **Request Body:**
  ```json
  {
    "email": "test@example.com",
    "password": "password123"
  }
  ```
- **Success Response (200):**
  ```json
  {
    "message": "Login successful.",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "user_id": 1,
      "user_role": "student"
    }
  }
  ```
- **Error Response (401):**
  ```json
  {
    "error": "Invalid email or password."
  }
  ```

#### `GET /api/v1/users/profile`

- **Description:** Retrieves the profile of the currently authenticated user.
- **Authentication:** Requires `Authorization: Bearer <token>` header.
- **Success Response (200):**
  ```json
  {
    "user_id": 1,
    "user_role": "student",
    "email": "test@example.com",
    "full_name": "John Doe",
    ...
  }
  ```

#### `GET /api/v1/users`

- **Description:** Retrieves a paginated list of all users.
- **Query Parameters:** `page` (integer, default: 1), `limit` (integer, default: 10)
- **Success Response (200):**
  ```json
  {
    "data": [
      {
        "user_id": 1,
        "user_role": "student",
        "email": "test@example.com",
        ...
      }
    ],
    "meta": {
      "pagination": {
        "total_items": 1,
        "total_pages": 1,
        "current_page": 1,
        "per_page": 10,
        "next_url": null,
        "prev_url": null
      }
    }
  }
  ```

#### `GET /api/v1/users/{id}`

- **Description:** Retrieves a single user by their ID.
- **Success Response (200):**
  ```json
  {
    "user_id": 1,
    "user_role": "student",
    "email": "test@example.com",
    ...
  }
  ```

#### `PUT /api/v1/users/{id}`

- **Description:** Updates a user's information.
- **Request Body:**
  ```json
  {
    "full_name": "Jane Doe",
    "institution": "New University"
  }
  ```
- **Success Response (200):**
  ```json
  {
    "message": "User updated successfully."
  }
  ```

#### `DELETE /api/v1/users/{id}`

- **Description:** Deletes a user.
- **Success Response (200):**
  ```json
  {
    "message": "User deleted successfully."
  }
  ```

### Exam Management

#### `POST /api/v1/exams`

- **Description:** Creates a new exam.
- **Request Body:**
  ```json
  {
    "exam_name": "Final Year Exam",
    "exam_abbreviation": "FYE"
  }
  ```
- **Success Response (201):**
  ```json
  {
    "message": "Exam created successfully.",
    "exam_id": 1
  }
  ```

#### `GET /api/v1/exams`

- **Description:** Retrieves a paginated list of all exams.
- **Query Parameters:** `page`, `limit`
- **Success Response (200):**
  ```json
  {
    "data": [
      {
        "exam_id": 1,
        "exam_name": "Final Year Exam",
        ...
      }
    ],
    "meta": { ... }
  }
  ```

... (and so on for all other endpoints)

### Exam Subject Management

#### `POST /api/v1/exam-subjects`

- **Description:** Creates a new exam-subject relationship.
- **Request Body:**
  ```json
  {
    "exam_id": 1,
    "subject_id": 1,
    "number_of_questions": 50,
    "time_limit_seconds": 3600,
    "scoring_scheme": "{\"correct\": 1, \"incorrect\": 0}"
  }
  ```
- **Success Response (201):**
  ```json
  {
    "message": "Exam subject created successfully.",
    "exam_subject_id": 1
  }
  ```

#### `GET /api/v1/exam-subjects`

- **Description:** Retrieves a paginated list of all exam-subject relationships.
- **Query Parameters:** `page`, `limit`
- **Success Response (200):**
  ```json
  {
    "data": [
      {
        "exam_subject_id": 1,
        "exam_id": 1,
        "subject_id": 1,
        "exam_name": "Final Year Exam",
        "subject_name": "Mathematics",
        "number_of_questions": 50,
        "time_limit_seconds": 3600,
        "scoring_scheme": "{\"correct\": 1, \"incorrect\": 0}"
      }
    ],
    "meta": { ... }
  }
  ```

#### `GET /api/v1/exam-subjects/{id}`

- **Description:** Retrieves a single exam-subject relationship by its ID.
- **Success Response (200):**
  ```json
  {
    "exam_subject_id": 1,
    "exam_id": 1,
    "subject_id": 1,
    "exam_name": "Final Year Exam",
    "subject_name": "Mathematics",
    "number_of_questions": 50,
    "time_limit_seconds": 3600,
    "scoring_scheme": "{\"correct\": 1, \"incorrect\": 0}"
  }
  ```

#### `PUT /api/v1/exam-subjects/{id}`

- **Description:** Updates an exam-subject relationship.
- **Request Body:**
  ```json
  {
    "number_of_questions": 60,
    "time_limit_seconds": 3000
  }
  ```
- **Success Response (200):**
  ```json
  {
    "message": "Exam subject updated successfully."
  }
  ```

#### `DELETE /api/v1/exam-subjects/{id}`

- **Description:** Deletes an exam-subject relationship.
- **Success Response (200):**
  ```json
  {
    "message": "Exam subject deleted successfully."
  }
  ```

---

## 3. Authentication & Authorization

- **Type:** JWT-based authentication (see `utils/JwtHelper.php`)
- **How to Authenticate:**
  - Obtain JWT token via login endpoint (e.g., `/api/login`)
  - Include token in `Authorization: Bearer <token>` header for protected endpoints
- **User Roles:**
  - Specify any roles (e.g., admin, student) and their permissions
- **Error Handling:**
  - 401 Unauthorized for missing/invalid token
  - 403 Forbidden for insufficient permissions

## 4. System Requirements

- Node.js (version >= 16)
- npm or yarn
- PHP (version compatible with backend)
- Composer (for backend dependencies)

## 5. Coding Style & Naming Conventions

- **JavaScript/TypeScript:**
  - Use ES6+ syntax
  - Use PascalCase for components, camelCase for variables and functions
  - Use clear, descriptive names for files and folders
- **Vue Components:**
  - Use `.vue` single-file components
  - Organize components by feature/domain
- **Pinia Stores:**
  - Store files in a `stores/` directory
  - Use `useXStore` naming convention
- **CSS:**
  - Use Bootstrap utility classes and components.
  - Minimize custom CSS; prefer Bootstrap customization options if needed.

## 6. Directory Structure Example

```
frontend/
  ├── src/
  │   ├── components/
  │   ├── views/
  │   ├── stores/
  │   ├── router/
  │   ├── assets/
  │   └── App.vue
  ├── public/
  ├── package.json
  ├── package.json
  └── ...
```

## 7. Environment Variables & Config

- Document all required environment variables (e.g., API base URL, JWT secret if needed for dev)
- Example `.env`:

```
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

## 8. API Error Handling Conventions

- All API errors return JSON with `error` and `message` fields
- Example error response:

```json
{
  "error": true,
  "message": "Invalid credentials."
}
```

- Frontend should display user-friendly error messages

## 9. Onboarding Steps

1. Clone the repository
2. Install backend dependencies: `composer install`
3. Install frontend dependencies: `npm install` (in `frontend/`)
4. Set up environment variables for backend and frontend
5. Run backend server: `php -S localhost:8000`
6. Run frontend dev server: `npm run dev` (in `frontend/`)
7. Build frontend: `npm run build` (in `frontend/`)
8. Move `dist/` to backend's `frontend/` directory
9. Ensure backend serves frontend for all non-API routes
10. Run tests: `npm run test` (frontend), backend tests as applicable

## 10. Testing Procedures

- **Unit Testing:**
  - Use [Vitest](https://vitest.dev/) or [Jest](https://jestjs.io/) for unit tests
  - Place tests in a `__tests__/` directory or alongside components as `.spec.js`/`.spec.ts` files
- **E2E Testing:**
  - Use [Cypress](https://www.cypress.io/) for end-to-end tests
  - Place E2E tests in an `e2e/` directory
- **Test Coverage:**
  - Ensure at least 80% code coverage for critical components and stores

## 11. Build & Deployment

- Provide scripts for building and serving the frontend
- After building, move the `dist` folder to the backend's `frontend/` directory
- Update backend routing to serve the frontend dist index for all non-API routes

## 12. Documentation

- The `AGENTS.md` file must include:
  - All backend endpoints and their specifications
  - Example frontend API calls
  - State management structure (Pinia stores overview)
  - Component structure and directory layout
  - Testing instructions
  - Build and deployment steps
  - Onboarding steps
  - Authentication and error handling details
  - Environment variable requirements

## 13. Linting and Formatting

- Use ESLint for code linting and Prettier for code formatting.
- Configuration files (`.eslintrc`, `.prettierrc`) should be included in the project root.
- Run `npm run lint` and `npm run format` before committing code.

## 14. Commit Message and PR Guidelines

- Use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages (e.g., `feat: add login page`).
- Pull requests should include a summary of changes and reference related issues.

## 15. Naming Examples

- Components: `UserProfile.vue`
- Pinia Stores: `useUserStore.ts`
- Tests: `UserProfile.spec.ts`

## 16. Accessibility and Responsiveness

- All components must follow accessibility (a11y) best practices.
- Use semantic HTML and ARIA attributes where appropriate.
- Ensure the UI is responsive using Bootstrap's grid system and responsive utilities.

## 17. API Versioning

- If backend endpoints are versioned (e.g., `/api/v1/`), ensure the frontend uses the correct version in all API calls.

## 18. Error Logging and Monitoring

- Use `console.error` for error logging during development.
- For production, integrate with an error monitoring service if required.

## 19. Contact and Support

- For questions or issues, contact the project maintainer or post in the team communication channel (e.g., Slack, Teams).
- Refer to the project README for additional resources and support links.

---

> **Note:**
> The `AGENTS.md` file is the single source of truth for AI agents to generate, test, and deploy the frontend. Keep it up to date with any backend or API changes.
