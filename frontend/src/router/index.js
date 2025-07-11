import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore'; // Ensure this path is correct
import HomeView from '../views/HomeView.vue';
import LoginView from '../views/Login.vue';
import RegisterView from '../views/Register.vue';
import ProfileView from '../views/Profile.vue';
import ManageExamsView from '../views/ManageExams.vue';
import ManageSubjectsView from '../views/ManageSubjects.vue'; // Import the new view

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
    name: 'manage-exams',
    component: ManageExamsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/manage-subjects',
    name: 'manage-subjects',
    component: ManageSubjectsView,
    meta: { requiresAuth: true }, // Assuming this also requires authentication
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

  if (to.meta.requiresAuth && !isAuthenticated) {
    // If route requires auth and user is not authenticated, redirect to login
    next({ name: 'login' });
  } else if (to.meta.requiresGuest && isAuthenticated) {
    // If route is for guests (login, register) and user is authenticated, redirect to profile
    next({ name: 'profile' });
  } else {
    // Otherwise, proceed as normal
    next();
  }
});

export default router;
