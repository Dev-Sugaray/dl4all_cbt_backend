import api from '../api'; // Import the configured axios instance

export const getTopics = (page = 1, limit = 10, subjectId = null, activeOnly = false) => {
  const params = { page, limit };
  if (subjectId) {
    params.subject_id = subjectId;
  }
  if (activeOnly) {
    params.active_only = 'true';
  }
  return api.get('/api/v1/topics', { params });
};

export const getTopicById = (topicId) => {
  return api.get(`/api/v1/topics/${topicId}`);
};

export const createTopic = (topicData) => {
  return api.post('/api/v1/topics', topicData);
};

export const updateTopic = (topicId, topicData) => {
  return api.put(`/api/v1/topics/${topicId}`, topicData);
};

export const deleteTopic = (topicId) => {
  return api.delete(`/api/v1/topics/${topicId}`);
};