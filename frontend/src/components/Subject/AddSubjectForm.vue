<template>
  <div class="card p-3 mb-4 shadow-sm">
    <h5 class="card-title mb-3">Add New Subject</h5>
    <form @submit.prevent="submitForm">
      <div class="mb-3">
        <label for="subjectName" class="form-label">Subject Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm" id="subjectName" v-model="subject.subject_name" required>
      </div>
      <div class="mb-3">
        <label for="subjectCode" class="form-label">Subject Code <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm" id="subjectCode" v-model="subject.subject_code" required>
      </div>
      <div class="mb-3">
        <label for="subjectDescription" class="form-label">Description</label>
        <textarea class="form-control form-control-sm" id="subjectDescription" v-model="subject.description" rows="3"></textarea>
      </div>
      <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-outline-secondary btn-sm me-2" @click="cancelForm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          Add Subject
        </button>
      </div>
      <div v-if="error" class="alert alert-danger mt-3 p-2 small">{{ error }}</div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { createSubject } from '../../api'; // Assuming api.js is in src

const emit = defineEmits(['subject-added', 'cancel']);

const subject = reactive({
  subject_name: '',
  subject_code: '',
  description: ''
});
const loading = ref(false);
const error = ref(null);

const submitForm = async () => {
  loading.value = true;
  error.value = null;
  try {
    await createSubject(subject);
    emit('subject-added');
    resetForm();
    // Optionally, show a success message here or let parent handle it
  } catch (err) {
    console.error('Add subject error:', err);
    error.value = err.response?.data?.error || err.message || 'Failed to add subject.';
  } finally {
    loading.value = false;
  }
};

const cancelForm = () => {
  emit('cancel');
  resetForm();
};

const resetForm = () => {
  subject.subject_name = '';
  subject.subject_code = '';
  subject.description = '';
  error.value = null;
};
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
