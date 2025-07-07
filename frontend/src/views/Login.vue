<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="px-8 py-6 mt-4 text-left bg-white shadow-lg rounded-lg sm:w-full md:w-1/2 lg:w-1/3">
      <h3 class="text-2xl font-bold text-center">Login to your account</h3>
      <form @submit.prevent="handleLogin">
        <div class="mt-4">
          <div>
            <label class="block" for="email">Email</label>
            <input
              type="email"
              placeholder="Email"
              id="email"
              v-model="email"
              class="w-full px-4 py-2 mt-2 border rounded-md focus:outline-none focus:ring-1 focus:ring-blue-600"
              required
            />
          </div>
          <div class="mt-4">
            <label class="block" for="password">Password</label>
            <input
              type="password"
              placeholder="Password"
              id="password"
              v-model="password"
              class="w-full px-4 py-2 mt-2 border rounded-md focus:outline-none focus:ring-1 focus:ring-blue-600"
              required
            />
          </div>
          <div v-if="errorMessage" class="mt-4 text-red-500">
            {{ errorMessage }}
          </div>
          <div class="flex items-baseline justify-between">
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 mt-4 text-white bg-blue-600 rounded-lg hover:bg-blue-900 disabled:bg-gray-400"
            >
              Login
            </button>
            <router-link to="/register" class="text-sm text-blue-600 hover:underline">
              Not registered? Create an account
            </router-link>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore'; // Assuming @ refers to /src

const email = ref('');
const password = ref('');
const errorMessage = ref('');
const loading = ref(false);
const router = useRouter();
const authStore = useAuthStore();

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const success = await authStore.login({ email: email.value, password: password.value });
    if (success) {
      // Attempt to initialize auth state by fetching user, then redirect
      await authStore.initAuth(); // Ensure user profile is loaded after login
      router.push('/profile'); // Or to a dashboard, or a route from query param
    }
  } catch (error) {
    errorMessage.value = error.message || 'An unexpected error occurred.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Scoped styles for Login.vue if needed */
</style>
