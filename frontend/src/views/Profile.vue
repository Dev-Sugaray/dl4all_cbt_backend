<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold">User Profile</h1>
    <div v-if="loading && !userProfile" class="mt-4">Loading profile...</div>
    <div v-if="!loading && userProfile" class="mt-4 p-4 border rounded shadow-md">
      <p><strong>Email:</strong> {{ userProfile.email }}</p>
      <p><strong>Role:</strong> {{ userProfile.user_role }}</p>
      <p v-if="userProfile.full_name"><strong>Full Name:</strong> {{ userProfile.full_name }}</p>
      <!-- Add more user details here as needed -->
      <button @click="handleLogout" class="mt-4 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-700">
        Logout
      </button>
    </div>
    <div v-if="!loading && !userProfile && authStore.getIsAuthenticated" class="mt-4 text-red-500">
      Could not load user profile data. Please try again later.
    </div>
     <div v-if="!loading && !userProfile && !authStore.getIsAuthenticated" class="mt-4 text-orange-500">
      You are not logged in or your session has expired. Please <router-link to="/login" class="underline">login</router-link>.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore();
const router = useRouter();
const loading = ref(false);

// Directly use a computed property for the user profile from the store
const userProfile = computed(() => authStore.getUser);

onMounted(async () => {
  // The navigation guard (router.beforeEach) should call initAuth.
  // initAuth attempts to load the user profile if a token exists.
  // This onMounted hook can serve as a secondary check or for cases where
  // the component is mounted and data might need a refresh, though typically
  // the guard handles the initial load.

  if (!authStore.getIsAuthenticated && localStorage.getItem('token')) {
    // This case should ideally be handled by the router guard's initAuth call
    // but as a fallback:
    loading.value = true;
    await authStore.initAuth(); // This will try to fetch the user
    loading.value = false;
  } else if (authStore.getIsAuthenticated && !authStore.getUser) {
    // If authenticated but user object is missing (should be rare if initAuth worked)
    loading.value = true;
    await authStore.fetchUser();
    loading.value = false;
  }

  // If still not authenticated after checks, the guard should have redirected.
  // If it's a protected route, this component might not even render or might flash.
  // However, an explicit check can be useful.
  if (!authStore.getIsAuthenticated && router.currentRoute.value.meta.requiresAuth) {
     // The guard should handle this, but as a failsafe:
     // router.push('/login'); // This might be redundant if guard is effective
  }
});

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>
