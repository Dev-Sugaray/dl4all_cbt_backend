import { defineStore } from 'pinia';
import axios from 'axios';

// Get API base URL from environment variables
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
  }),
  actions: {
    async login(credentials) {
      try {
        const response = await axios.post(`${API_BASE_URL}/users/login`, credentials);
        const { token, user } = response.data;
        this.token = token;
        this.user = user;
        this.isAuthenticated = true;
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        // Set Authorization header for future Axios requests
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        return true;
      } catch (error) {
        console.error('Login failed:', error.response?.data?.error || error.message);
        this.logout(); // Ensure clean state on error
        throw error.response?.data?.error || new Error('Login failed');
      }
    },
    async register(userData) {
      try {
        const response = await axios.post(`${API_BASE_URL}/users/register`, userData);
        // Assuming registration does not automatically log in the user
        // Or if it does, handle token and user data like in login()
        console.log('Registration successful:', response.data.message);
        return true;
      } catch (error) {
        console.error('Registration failed:', error.response?.data?.error || error.message);
        throw error.response?.data?.error || new Error('Registration failed');
      }
    },
    logout() {
      this.token = null;
      this.user = null;
      this.isAuthenticated = false;
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      // Remove Authorization header
      delete axios.defaults.headers.common['Authorization'];
    },
    async fetchUser() {
      if (!this.token) {
        // Attempt to load token from localStorage if not already set (e.g., on page refresh)
        const token = localStorage.getItem('token');
        if (token) {
          this.token = token;
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        } else {
          this.logout(); // Ensure clean state if no token
          return null;
        }
      }

      // If after attempting to load, token is still not available, or user is already fetched
      if (!this.token) return null;
      // if (this.user && Object.keys(this.user).length > 0) return this.user; // Optional: return cached user

      try {
        const response = await axios.get(`${API_BASE_URL}/users/profile`);
        this.user = response.data;
        this.isAuthenticated = true; // Reinforce auth status
        localStorage.setItem('user', JSON.stringify(response.data)); // Update user in localStorage
        return this.user;
      } catch (error) {
        console.error('Failed to fetch user profile:', error.response?.data?.error || error.message);
        // If fetching user fails (e.g. token expired), log out the user
        if (error.response && (error.response.status === 401 || error.response.status === 403)) {
          this.logout();
        }
        return null;
      }
    },
    // Initialize the store by trying to fetch the user if a token exists
    // This is useful for when the application loads and there's a token in localStorage
    async initAuth() {
        const token = localStorage.getItem('token');
        if (token && !this.isAuthenticated) {
            this.token = token;
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            await this.fetchUser();
        } else if (!token) {
            this.logout(); // Clean up if no token found
        }
    }
  },
  getters: {
    getUser: (state) => state.user,
    getToken: (state) => state.token,
    getIsAuthenticated: (state) => state.isAuthenticated,
  },
});
