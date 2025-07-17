import api from '../api';

export const getQuestions = (page = 1, limit = 10, examSubjectId = null, topicId = null, isActive = null) => {
  const params = { page, limit };
  if (examSubjectId) {
    params.exam_subject_id = examSubjectId;
  }
  if (topicId) {
    params.topic_id = topicId;
  }
  if (isActive !== null) {
    params.is_active = isActive ? 'true' : 'false';
  }
  return api.get('/api/v1/questions', { params });
};

export const getQuestionById = (questionId) => {
  return api.get(`/api/v1/questions/${questionId}`);
};

export const createQuestion = (questionData) => {
  return api.post('/api/v1/questions', questionData);
};

export const updateQuestion = (questionId, questionData) => {
  return api.put(`/api/v1/questions/${questionId}`, questionData);
};

export const deleteQuestion = (questionId) => {
  // This will trigger the soft-delete (setting is_active to FALSE) on the backend
  return api.delete(`/api/v1/questions/${questionId}`);
};

export const bulkCreateQuestions = (questionsData) => {
  return api.post('/api/v1/questions/bulk', questionsData);
};

export const bulkUpdateQuestions = (questionsData) => {
  return api.put('/api/v1/questions/bulk', questionsData);
};

export const bulkDeleteQuestions = (questionIds) => {
  // This will trigger the soft-delete (setting is_active to FALSE) on the backend
  return api.delete('/api/v1/questions/bulk', { data: questionIds });
};

export const uploadQuestionsCsv = (formData) => {
  return api.post('/api/v1/questions/upload-csv', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
};

export const getExamSubjects = (page = 1, limit = 100) => {
  return api.get('/api/v1/exam-subjects', { params: { page, limit } });
};

export const getTopics = (page = 1, limit = 100, subjectId = null) => {
  const params = { page, limit };
  if (subjectId) {
    params.subject_id = subjectId;
  }
  return api.get('/api/v1/topics', { params });
};