<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="px-8 py-6 mt-4 text-left bg-white shadow-lg rounded-lg sm:w-full md:w-1/2 lg:w-1/3">
      <h3 class="text-2xl font-bold text-center">Create an account</h3>
      <form @submit.prevent="handleRegister">
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
          <div class="mt-4">
            <label class="block" for="confirmPassword">Confirm Password</label>
            <input
              type="password"
              placeholder="Confirm Password"
              id="confirmPassword"
              v-model="confirmPassword"
              class="w-full px-4 py-2 mt-2 border rounded-md focus:outline-none focus:ring-1 focus:ring-blue-600"
              required
            />
          </div>
          <div class="mt-4">
            <label class="block" for="userRole">Role</label>
            <select
              id="userRole"
              v-model="userRole"
              class="w-full px-4 py-2 mt-2 border rounded-md focus:outline-none focus:ring-1 focus:ring-blue-600"
              required
            >
              <option value="student">Student</option>
              <option value="admin">Admin</option>
              <!-- Add other roles as needed -->
            </select>
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
              Register
            </button>
            <router-link to="/login" class="text-sm text-blue-600 hover:underline">
              Already have an account? Login
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
import { useAuthStore } from '@/stores/authStore';

const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const userRole = ref('student'); // Default role
const errorMessage = ref('');
const loading = ref(false);
const router = useRouter();
const authStore = useAuthStore();

const handleRegister = async () => {
  if (password.value !== confirmPassword.value) {
    errorMessage.value = 'Passwords do not match.';
    return;
  }
  loading.value = true;
  errorMessage.value = '';
  try {
    await authStore.register({
      email: email.value,
      password: password.value,
      user_role: userRole.value,
    });
    router.push('/login');
  } catch (error) {
    errorMessage.value = error.message || 'An unexpected error occurred during registration.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Scoped styles for Register.vue if needed */
</style>
