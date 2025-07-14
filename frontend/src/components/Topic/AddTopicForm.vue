<template>
  <div class="card shadow-sm mb-4">
    <div class="card-header">
      <h5 class="mb-0">Add New Topic</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="handleSubmit">
        <div class="mb-3">
          <label for="topicName" class="form-label">Topic Name</label>
          <input
            id="topicName"
            v-model="topic.topic_name"
            type="text"
            class="form-control"
            required
          />
        </div>
        <div class="mb-3">
          <label for="subject" class="form-label">Subject</label>
          <select
            id="subject"
            v-model="topic.subject_id"
            class="form-select"
            required
          >
            <option disabled value="">Please select a subject</option>
            <option
              v-for="subject in subjects"
              :key="subject.subject_id"
              :value="subject.subject_id"
            >
              {{ subject.subject_name }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea
            id="description"
            v-model="topic.description"
            class="form-control"
            rows="3"
          ></textarea>
        </div>
        <div class="d-flex justify-content-end">
          <button
            type="button"
            class="btn btn-secondary me-2"
            @click="$emit('cancel')"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="isSubmitting"
          >
            <span
              v-if="isSubmitting"
              class="spinner-border spinner-border-sm"
              role="status"
              aria-hidden="true"
            ></span>
            {{ isSubmitting ? 'Adding...' : 'Add Topic' }}
          </button>
        </div>
        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { getSubjects, createTopic } from '../../api';

const emit = defineEmits(['topic-added', 'cancel']);

const topic = ref({
  topic_name: '',
  subject_id: '',
  description: '',
});

const subjects = ref([]);
const isSubmitting = ref(false);
const error = ref(null);

const fetchSubjects = async () => {
  try {
    const response = await getSubjects(1, 1000, true); // Fetch all active subjects
    if (response && response.data && Array.isArray(response.data.data)) {
      subjects.value = response.data.data;
    }
  } catch (err) {
    console.error('Failed to fetch subjects:', err);
    error.value = 'Failed to load subjects for selection.';
  }
};

const handleSubmit = async () => {
  isSubmitting.value = true;
  error.value = null;
  try {
    await createTopic(topic.value);
    emit('topic-added');
  } catch (err) {
    error.value = err.response?.data?.error || 'An unexpected error occurred.';
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(fetchSubjects);
</script>

<style scoped>
.card {
  border: none;
}
</style>
