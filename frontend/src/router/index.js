import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

// Layouts
import AppLayout from '../components/AppLayout.vue';
import StudentLayout from '../components/StudentLayout.vue';

// Views
import HomeView from '../views/HomeView.vue';
import LoginView from '../views/Login.vue';
import RegisterView from '../views/Register.vue';
import ProfileView from '../views/Profile.vue';
import ManageExamsView from '../views/ManageExams.vue';
import ManageSubjectsView from '../views/ManageSubjects.vue';
import ManageExamSubjectsView from '../views/ManageExamSubjects.vue';
import ManageTopicsView from '../views/ManageTopics.vue';
import ManageQuestionsView from '../views/ManageQuestions.vue';

// Student Views
import StudentDashboard from '../views/student/StudentDashboard.vue';
import ExamTaking from '../views/student/ExamTaking.vue';
import ExamResults from '../views/student/ExamResults.vue';
import StudentProfile from '../views/student/StudentProfile.vue';

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { requiresGuest: true },
  },
  {
    path: '/',
    component: AppLayout, // This will be the layout for admin/general routes
    meta: { requiresAuth: true }, // All children will require auth
    children: [
      {
        path: '',
        name: 'home',
        component: HomeView,
      },
      {
        path: 'profile',
        name: 'profile',
        component: ProfileView,
      },
      {
        path: 'manage-exams',
        name: 'ManageExams',
        component: ManageExamsView,
      },
      {
        path: 'manage-exams/:exam_id/subjects',
        name: 'ManageExamSubjects',
        component: ManageExamSubjectsView,
        props: true,
      },
      {
        path: 'manage-subjects',
        name: 'ManageSubjects',
        component: ManageSubjectsView,
      },
      {
        path: 'manage-topics',
        name: 'ManageTopics',
        component: ManageTopicsView,
      },
      {
        path: 'manage-questions',
        name: 'ManageQuestions',
        component: ManageQuestionsView,
      },
    ],
  },
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
      },
      {
        path: 'exam/:id',
        name: 'ExamTaking',
        component: ExamTaking,
        props: true,
      },
      {
        path: 'results',
        name: 'StudentResults',
        component: ExamResults,
      },
      {
        path: 'profile',
        name: 'StudentProfile',
        component: StudentProfile,
      }
    ]
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

// Navigation Guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  if (!authStore.getIsAuthenticated && localStorage.getItem('token')) {
    await authStore.initAuth();
  }

  const isAuthenticated = authStore.getIsAuthenticated;
  const user = authStore.getUser;

  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login' });
  } else if (to.meta.requiresGuest && isAuthenticated) {
    if (user && user.user_role === 'student') {
      next({ name: 'StudentDashboard' });
    } else {
      next({ name: 'profile' });
    }
  } else if (to.meta.requiresStudent && isAuthenticated) {
    if (!user || user.user_role !== 'student') {
      next({ name: 'profile' });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;