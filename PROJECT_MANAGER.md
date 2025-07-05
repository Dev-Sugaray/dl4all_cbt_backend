# Frontend Development Project Plan: dl4all CBT App

## Project Overview

The goal of this project is to build a complete, responsive, and user-friendly frontend application for the dl4all Computer-Based Testing (CBT) platform. The frontend will be built using Vue.js 3, Pinia for state management, and Tailwind CSS for styling, following the specifications outlined in the `AGENTS.md` document.

This document provides a structured project plan with milestones and detailed task checklists to guide the development process and track progress.

---

## Milestone 1: Project Setup & Foundation

**Goal:** Establish the foundational structure of the Vue.js application, including all necessary libraries, configuration, and basic layout.

### Tasks Checklist

- [ ] **Environment Setup:**

  - [ ] Install Node.js and npm/yarn.
  - [ ] Create a new Vue.js 3 project (`npm init vue@latest`).
- [ ] **Dependency Installation:**

  - [ ] Install Vue Router for routing (`npm install vue-router@4`).
  - [ ] Install Pinia for state management (`npm install pinia`).
  - [ ] Install Tailwind CSS and its dependencies (`npm install -D tailwindcss postcss autoprefixer`).
  - [ ] Install Axios for making API requests (`npm install axios`).
- [ ] **Configuration:**

  - [ ] Configure Tailwind CSS by creating `tailwind.config.js` and `postcss.config.js`.
  - [ ] Set up Vue Router with hash mode.
  - [ ] Integrate Pinia into the main application instance.
  - [ ] Create a `.env` file and configure the `VITE_API_BASE_URL`.
- [ ] **Project Structure:**

  - [ ] Create the directory structure as specified in `AGENTS.md` (`src/components`, `src/views`, `src/stores`, `src/router`, etc.).
  - [ ] Create a main layout component (e.g., `AppLayout.vue`) that includes a header, sidebar, and main content area.

---

## Milestone 2: Core User Authentication

**Goal:** Implement all features related to user authentication, including registration, login, and profile management.

### Tasks Checklist

- [ ] **Authentication State (Pinia):**

  - [ ] Create a `useAuthStore` Pinia store to manage user state, token, and authentication status.
- [ ] **Login & Registration:**

  - [ ] Create a `Login.vue` view with a form for email and password.
  - [ ] Implement the login functionality, calling the `/api/v1/users/login` endpoint and storing the token.
  - [ ] Create a `Register.vue` view with a form for email, password, and user role.
  - [ ] Implement the registration functionality, calling the `/api/v1/users/register` endpoint.
- [ ] **Routing & Guards:**

  - [ ] Create protected routes that require authentication using Vue Router's navigation guards.
  - [ ] Implement logic to redirect unauthenticated users to the login page.
- [ ] **User Profile:**

  - [ ] Create a `Profile.vue` view to display user information.
  - [ ] Implement the functionality to fetch and display the user's profile from `/api/v1/users/profile`.
  - [ ] (Optional) Add a feature to update the user's profile information.

---

## Milestone 3: Exam & Subject Management (Admin)

**Goal:** Build the administrative interface for managing exams and subjects.

### Tasks Checklist

- [ ] **Exam Management:**

  - [ ] Create a `ManageExams.vue` view.
  - [ ] Display a list of all exams with pagination.
  - [ ] Implement a form to create new exams.
  - [ ] Implement functionality to update existing exams.
  - [ ] Implement functionality to delete exams.
- [ ] **Subject Management:**

  - [ ] Create a `ManageSubjects.vue` view.
  - [ ] Display a list of all subjects with pagination.
  - [ ] Implement a form to create new subjects.
  - [ ] Implement functionality to update existing subjects.
  - [ ] Implement functionality to delete subjects.

---

## Milestone 4: Topic & Question Management (Admin/Content Creator)

**Goal:** Develop the interface for managing topics and questions, including complex features like bulk operations.

### Tasks Checklist

- [ ] **Topic Management:**

  - [ ] Create a `ManageTopics.vue` view.
  - [ ] Display a list of all topics with pagination.
  - [ ] Implement a form to create new topics, linking them to subjects.
  - [ ] Implement functionality to update and delete topics.
- [ ] **Question Management:**

  - [ ] Create a `ManageQuestions.vue` view.
  - [ ] Display a list of all questions with their options.
  - [ ] Implement a form to create a new question with multiple options.
  - [ ] Implement functionality to update and delete questions.
- [ ] **Bulk Operations:**

  - [ ] Implement an interface for bulk creating questions from a JSON structure.
  - [ ] Implement an interface for bulk updating and deleting questions.

---

## Milestone 5: Student Experience - Taking Exams

**Goal:** Create the student-facing interface for selecting and taking an exam.

### Tasks Checklist

- [ ] **Exam Selection:**

  - [ ] Create a `Dashboard.vue` or `SelectExam.vue` view for students.
  - [ ] Display a list of available exams and subjects.
- [ ] **Exam Session:**

  - [ ] Implement the logic to start a new student session when an exam is selected.
  - [ ] Create an `Exam.vue` view to display one question at a time.
  - [ ] Implement navigation between questions (next, previous).
  - [ ] Implement a timer for the exam session.
- [ ] **Answering Questions:**

  - [ ] Implement the functionality to submit answers for each question.
  - [ ] Store the student's answers in the application's state.
- [ ] **Results:**

  - [ ] Create a `Results.vue` view to display the student's score and a summary of their answers after the exam is completed.

---

## Milestone 6: Finalization & Deployment

**Goal:** Ensure the application is production-ready by conducting thorough testing, building the final assets, and integrating with the backend.

### Tasks Checklist

- [ ] **Testing:**

  - [ ] Write unit tests for all critical components and Pinia stores.
  - [ ] Conduct end-to-end testing for all major user flows (registration, login, taking an exam, etc.).
  - [ ] Perform responsive design testing on various screen sizes.
- [ ] **Build & Integration:**

  - [ ] Run the production build command (`npm run build`).
  - [ ] Move the generated `dist` folder to the `frontend/` directory in the backend project.
  - [ ] Configure the backend to serve the frontend's `index.html` for all non-API routes.
- [ ] **Final Review:**

  - [ ] Conduct a final review of the application to ensure all requirements from `AGENTS.md` have been met.
  - [ ] Deploy the integrated application to a staging or production environment.
