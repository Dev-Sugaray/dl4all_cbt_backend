import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/',
  headers: {
    'Content-Type': 'application/json',
  },
});

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

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      console.error('API request Unauthorized (401):', error.response.data.message || error.message);
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
  return api.put(`/api/v1/subjects/${subjectId}`, subjectData);
};

export const deleteSubject = (subjectId) => {
  return api.delete(`/api/v1/subjects/${subjectId}`);
};

// Specific API functions for ExamSubjects
export const getExamSubjects = (params = {}) => {
  return api.get('/api/v1/exam-subjects', { params });
};

export const createExamSubject = (examSubjectData) => {
  return api.post('/api/v1/exam-subjects', examSubjectData);
};

export const updateExamSubject = (examSubjectId, examSubjectData) => {
  return api.put(`/api/v1/exam-subjects/${examSubjectId}`, examSubjectData);
};

export const deleteExamSubject = (examSubjectId) => {
  return api.delete(`/api/v1/exam-subjects/${examSubjectId}`);
};

export default api;