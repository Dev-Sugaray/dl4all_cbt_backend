import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore'; // Ensure this path is correct
import HomeView from '../views/HomeView.vue';
import LoginView from '../views/Login.vue';
import RegisterView from '../views/Register.vue';
import ProfileView from '../views/Profile.vue';
import ManageExamsView from '../views/ManageExams.vue';
import ManageSubjectsView from '../views/ManageSubjects.vue';
import ManageExamSubjectsView from '../views/ManageExamSubjects.vue'; // Import the new view
import ManageTopicsView from '../views/ManageTopics.vue';
import ManageQuestionsView from '../views/ManageQuestions.vue';

// Student views
import StudentLayout from '../components/StudentLayout.vue';
import StudentDashboard from '../views/student/StudentDashboard.vue';
import ExamTaking from '../views/student/ExamTaking.vue';
import ExamResults from '../views/student/ExamResults.vue';
import StudentProfile from '../views/student/StudentProfile.vue';

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true }, // For redirecting authenticated users
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { requiresGuest: true }, // For redirecting authenticated users
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/manage-exams',
    name: 'ManageExams', // Consistent naming with component if possible
    component: ManageExamsView,
    meta: { requiresAuth: true },
  },
  {
    // Route for managing subjects of a specific exam
    path: '/manage-exams/:exam_id/subjects',
    name: 'ManageExamSubjects',
    component: ManageExamSubjectsView,
    props: true, // Pass route params as props to the component
    meta: { requiresAuth: true },
  },
  {
    path: '/manage-subjects',
    name: 'ManageSubjects', // Consistent naming
    component: ManageSubjectsView,
    meta: { requiresAuth: true }, // Assuming this also requires authentication
  },
  {
    path: '/manage-topics',
    name: 'ManageTopics',
    component: ManageTopicsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/manage-questions',
    name: 'ManageQuestions',
    component: ManageQuestionsView,
    meta: { requiresAuth: true },
  },
  // Student routes with nested layout
  {
    path: '/student',
    component: StudentLayout,
    meta: { requiresAuth: true, requiresStudent: true },
    children: [
      {
        path: '',
        redirect: '/student/dashboard'
      },
      {
        path: 'dashboard',
        name: 'StudentDashboard',
        component: StudentDashboard,
        meta: { requiresAuth: true, requiresStudent: true }
      },
      {
        path: 'exam/:id',
        name: 'ExamTaking',
        component: ExamTaking,
        props: true,
        meta: { requiresAuth: true, requiresStudent: true }
      },
      {
        path: 'results',
        name: 'StudentResults',
        component: ExamResults,
        meta: { requiresAuth: true, requiresStudent: true }
      },
      {
        path: 'profile',
        name: 'StudentProfile',
        component: StudentProfile,
        meta: { requiresAuth: true, requiresStudent: true }
      }
    ]
  },
  // Add other routes here later
];

const router = createRouter({
  history: createWebHashHistory(), // AGENTS.md specifies hash mode
  routes,
});

// Navigation Guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Attempt to initialize auth state from localStorage on page load/refresh
  // This needs to be done carefully to avoid infinite loops if initAuth itself navigates
  if (!authStore.getIsAuthenticated && localStorage.getItem('token')) {
    await authStore.initAuth(); // initAuth will set isAuthenticated if token is valid
  }

  const isAuthenticated = authStore.getIsAuthenticated;
  const user = authStore.getUser;

  if (to.meta.requiresAuth && !isAuthenticated) {
    // If route requires auth and user is not authenticated, redirect to login
    next({ name: 'login' });
  } else if (to.meta.requiresGuest && isAuthenticated) {
    // If route is for guests (login, register) and user is authenticated, redirect based on user role
    if (user && user.user_role === 'student') {
      next({ name: 'StudentDashboard' });
    } else {
      next({ name: 'profile' });
    }
  } else if (to.meta.requiresStudent && isAuthenticated) {
    // Check if user is a student for student-specific routes
    if (!user || user.user_role !== 'student') {
      // If user is not a student, redirect to appropriate dashboard
      next({ name: 'profile' });
    } else {
      next();
    }
  } else {
    // Otherwise, proceed as normal
    next();
  }
});

export default router;
