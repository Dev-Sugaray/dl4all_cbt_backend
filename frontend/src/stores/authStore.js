import { defineStore } from 'pinia';
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
    isLoading: false,
    error: null,
  }),
  actions: {
    setLoading(loading) {
      this.isLoading = loading;
    },
    setError(error) {
      this.error = error;
    },
    clearError() {
      this.error = null;
    },
    async login(credentials) {
      this.setLoading(true);
      this.clearError();
      try {
        const response = await axios.post(`${API_BASE_URL}/users/login`, credentials);
        const { token, user } = response.data;

        this.token = token;
        this.user = user;
        this.isAuthenticated = true;

        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));

        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

        // Fetch full user profile immediately after successful login
        await this.fetchUser();

        return { success: true, user: this.user };
      } catch (error) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || 'Login failed';
        this.setError(errorMessage);
        this.logout();
        throw new Error(errorMessage);
      } finally {
        this.setLoading(false);
      }
    },
    logout() {
      this.token = null;
      this.user = null;
      this.isAuthenticated = false;
      this.error = null;

      localStorage.removeItem('token');
      localStorage.removeItem('user');

      delete axios.defaults.headers.common['Authorization'];
    },
    async fetchUser() {
      if (!this.token) {
        this.logout();
        return null;
      }

      try {
        const response = await axios.get(`${API_BASE_URL}/users/profile`);
        this.user = response.data;
        this.isAuthenticated = true;
        localStorage.setItem('user', JSON.stringify(response.data));
        return this.user;
      } catch (error) {
        if (error.response && (error.response.status === 401 || error.response.status === 403)) {
          this.logout();
        }
        return null;
      }
    },
    async initAuth() {
      const token = localStorage.getItem('token');
      if (token) {
        this.token = token;
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        this.isAuthenticated = true;
        await this.fetchUser();
      } else {
        this.logout();
      }
    },
    isStudent() {
      return this.user && this.user.user_role === 'student';
    },
    getStudentId() {
      return this.isStudent() ? this.user.user_id : null;
    }
  },
  getters: {
    getUser: (state) => state.user,
    getToken: (state) => state.token,
    getIsAuthenticated: (state) => state.isAuthenticated,
  },
});