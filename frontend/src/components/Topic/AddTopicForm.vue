<template>
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
      <h5 class="mb-0">Add New Topic</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="handleSubmit">
        <div class="mb-3">
          <label for="topicName" class="form-label">Topic Name <span class="text-danger">*</span></label>
          <input
            type="text"
            class="form-control"
            :class="{ 'is-invalid': errors.topic_name }"
            id="topicName"
            v-model="topic.topic_name"
            required
          >
          <div class="invalid-feedback" v-if="errors.topic_name">{{ errors.topic_name }}</div>
        </div>

        <div class="mb-3">
          <label for="subjectSelect" class="form-label">Subject <span class="text-danger">*</span></label>
          <select
            class="form-select"
            :class="{ 'is-invalid': errors.subject_id }"
            id="subjectSelect"
            v-model="topic.subject_id"
            required
          >
            <option value="" disabled>Select a Subject</option>
            <option v-for="subject in subjects" :key="subject.subject_id" :value="subject.subject_id">
              {{ subject.subject_name }} ({{ subject.subject_code }})
            </option>
          </select>
          <div class="invalid-feedback" v-if="errors.subject_id">{{ errors.subject_id }}</div>
        </div>

        <div class="mb-3">
          <label for="topicDescription" class="form-label">Description (Optional)</label>
          <textarea
            class="form-control"
            :class="{ 'is-invalid': errors.description }"
            id="topicDescription"
            rows="3"
            v-model="topic.description"
          ></textarea>
          <div class="invalid-feedback" v-if="errors.description">{{ errors.description }}</div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="button" class="btn btn-secondary me-2" @click="handleCancel">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ loading ? 'Adding...' : 'Add Topic' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { createTopic } from '../../services/topicService'; // Import from new service
import { getSubjects } from '../../api'; // Assuming getSubjects is available

const emit = defineEmits(['topic-added', 'cancel']);

const topic = reactive({
  topic_name: '',
  subject_id: '',
  description: ''
});

const subjects = ref([]);
const loading = ref(false);
const errors = reactive({});

const fetchSubjects = async () => {
  try {
    // Fetch only active subjects for selection
    const response = await getSubjects(1, 100, true); // Assuming getSubjects supports active_only filter
    if (response && response.data && Array.isArray(response.data.data)) {
      subjects.value = response.data.data.filter(s => s.is_active);
    }
  } catch (error) {
    console.error('Error fetching subjects:', error);
    // Optionally, display an error message to the user
  }
};

onMounted(fetchSubjects);

const validateForm = () => {
  let isValid = true;
  errors.topic_name = '';
  errors.subject_id = '';
  errors.description = ''; // Clear previous errors

  if (!topic.topic_name.trim()) {
    errors.topic_name = 'Topic name is required.';
    isValid = false;
  }
  if (!topic.subject_id) {
    errors.subject_id = 'Subject is required.';
    isValid = false;
  }
  // No specific validation for description other than trimming

  return isValid;
};

const handleSubmit = async () => {
  if (!validateForm()) {
    return;
  }

  loading.value = true;
  try {
    const response = await createTopic(topic);
    if (response.status === 201) {
      emit('topic-added');
      // Reset form
      topic.topic_name = '';
      topic.subject_id = '';
      topic.description = '';
      Object.keys(errors).forEach(key => errors[key] = ''); // Clear errors
    } else {
      // Handle other successful but unexpected statuses if necessary
      console.warn('Unexpected response status:', response.status, response.data);
      // Attempt to display a generic error or specific message from backend
      errors.general = response.data?.error || 'An unexpected error occurred.';
    }
  } catch (err) {
    console.error('Error adding topic:', err);
    if (err.response && err.response.data) {
      // Backend validation errors
      if (err.response.data.error) {
        if (err.response.data.error.includes('Topic name already exists')) {
          errors.topic_name = err.response.data.error;
        } else if (err.response.data.error.includes('Subject not found')) {
          errors.subject_id = err.response.data.error;
        } else {
          errors.general = err.response.data.error;
        }
      }
    } else {
      errors.general = 'Network error or server is unreachable.';
    }
  } finally {
    loading.value = false;
  }
};

const handleCancel = () => {
  emit('cancel');
  // Optionally reset form on cancel
  topic.topic_name = '';
  topic.subject_id = '';
  topic.description = '';
  Object.keys(errors).forEach(key => errors[key] = '');
};
</script>

<style scoped>
/* Add any specific styles for the form here if needed */
</style>