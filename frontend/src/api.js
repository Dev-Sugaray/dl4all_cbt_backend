import axios from 'axios';
// No need to import useAuthStore directly here if we read from localStorage
// However, if we wanted to react to store changes or use getters, we might.
// For simplicity and to avoid circular dependencies if api.js is imported by store,
// reading from localStorage is a common pattern for interceptors.

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/', // Ensure this matches your backend prefix if it includes /api/v1
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add JWT token to headers
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor (optional, but good for global error handling like 401)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Handle 401 Unauthorized: e.g., redirect to login, refresh token, etc.
      // For now, just log it. The authStore might also handle this.
      console.error('API request Unauthorized (401):', error.response.data.message || error.message);
      // Potentially, you could emit an event or call a global logout function
      // import { useAuthStore } from './stores/authStore'; // Be cautious with circular deps
      // const authStore = useAuthStore();
      // authStore.logout();
      // window.location.href = '/login'; // Or use router.push
    }
    return Promise.reject(error);
  }
);


// Specific API functions for Exams
export const getExams = (page = 1, limit = 10) => {
  return api.get('/api/v1/exams', { params: { page, limit } });
};

export const createExam = (examData) => {
  return api.post('/api/v1/exams', examData);
};

export const updateExam = (examId, examData) => {
  return api.put(`/api/v1/exams/${examId}`, examData);
};

export const deleteExam = (examId) => {
  return api.delete(`/api/v1/exams/${examId}`);
};

// You can add other entity API functions here following the same pattern

export default api; // Export the configured axios instance for direct use if needed
