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

// Specific API functions for Subjects
export const getSubjects = (page = 1, limit = 10, activeOnly = false) => {
  const params = { page, limit };
  if (activeOnly) {
    params.active_only = 'true';
  }
  return api.get('/api/v1/subjects', { params });
};

export const getSubjectById = (subjectId) => {
  return api.get(`/api/v1/subjects/${subjectId}`);
};

export const createSubject = (subjectData) => {
  return api.post('/api/v1/subjects', subjectData);
};

export const updateSubject = (subjectId, subjectData) => {
  // Ensure is_active is explicitly sent if present in subjectData
  // The backend expects a boolean or something that evaluates to it.
  // Our ManageSubjects.vue component handles the logic for what to send for is_active.
  return api.put(`/api/v1/subjects/${subjectId}`, subjectData);
};

// This function will perform a soft delete (set is_active to false)
export const deleteSubject = (subjectId) => {
  return api.delete(`/api/v1/subjects/${subjectId}`);
};

// Specific API functions for ExamSubjects
export const getExamSubjects = (params = {}) => {
  // params can include exam_id, subject_id, page, limit, etc.
  // The backend controller's getAll method for ExamSubjects already filters by is_active = 1
  return api.get('/api/v1/exam-subjects', { params });
};

export const createExamSubject = (examSubjectData) => {
  return api.post('/api/v1/exam-subjects', examSubjectData);
};

export const updateExamSubject = (examSubjectId, examSubjectData) => {
  return api.put(`/api/v1/exam-subjects/${examSubjectId}`, examSubjectData);
};

// Soft deletes by setting is_active to false (handled by backend controller)
export const deleteExamSubject = (examSubjectId) => {
  return api.delete(`/api/v1/exam-subjects/${examSubjectId}`);
};


// You can add other entity API functions here following the same pattern

export default api; // Export the configured axios instance for direct use if needed
