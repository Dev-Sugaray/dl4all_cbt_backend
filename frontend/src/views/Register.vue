<template>
  <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="p-4 p-md-5 mt-4 text-start bg-white shadow-lg rounded-3 col-11 col-sm-8 col-md-6 col-lg-4">
      <h3 class="h3 text-center fw-bold mb-4">Create an account</h3>
      <form @submit.prevent="handleRegister">
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input
            type="email"
            placeholder="Email"
            id="email"
            v-model="email"
            class="form-control mt-1"
            required
          />
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input
            type="password"
            placeholder="Password"
            id="password"
            v-model="password"
            class="form-control mt-1"
            required
          />
        </div>
        <div class="mb-3">
          <label class="form-label" for="confirmPassword">Confirm Password</label>
          <input
            type="password"
            placeholder="Confirm Password"
            id="confirmPassword"
            v-model="confirmPassword"
            class="form-control mt-1"
            required
          />
        </div>
        <div class="mb-3">
          <label class="form-label" for="userRole">Role</label>
          <select
            id="userRole"
            v-model="userRole"
            class="form-select mt-1"
            required
          >
            <option value="student">Student</option>
            <option value="admin">Admin</option>
            <!-- Add other roles as needed -->
          </select>
        </div>
        <div v-if="errorMessage" class="mt-3 mb-3 text-danger small">
          {{ errorMessage }}
        </div>
        <div class="d-flex align-items-baseline justify-content-between mt-4">
          <button
            type="submit"
            :disabled="loading"
            class="btn btn-primary px-4 py-2"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span v-if="loading"> Registering...</span>
            <span v-else>Register</span>
          </button>
          <router-link to="/login" class="small text-primary text-decoration-none">
            Already have an account? Login
          </router-link>
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
