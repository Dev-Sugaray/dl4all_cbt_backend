<template>
  <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="p-4 p-md-5 mt-4 text-start bg-white shadow-lg rounded-3 col-11 col-sm-8 col-md-6 col-lg-4">
      <h3 class="h3 text-center fw-bold mb-4">Login to your account</h3>
      <form @submit.prevent="handleLogin">
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
            <span v-if="loading"> Logging in...</span>
            <span v-else>Login</span>
          </button>
          <router-link to="/register" class="small text-primary text-decoration-none">
            Not registered? Create an account
          </router-link>
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
    const { success, user } = await authStore.login({ email: email.value, password: password.value });
    if (success) {
      if (user.user_role === 'student') {
        router.push('/student/dashboard');
      } else {
        router.push('/profile');
      }
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
