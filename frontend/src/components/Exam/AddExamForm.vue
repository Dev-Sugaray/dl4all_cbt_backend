<template>
  <div class="p-3 border rounded-lg shadow-sm bg-white mt-3">
    <h2 class="fs-4 fw-semibold mb-3">Add New Exam</h2>
    <form @submit.prevent="handleSubmit">
      <div class="mb-3">
        <label for="examName" class="form-label small">Exam Name</label>
        <input
          type="text"
          id="examName"
          v-model="examName"
          required
          class="form-control mt-1"
        />
      </div>
      <div class="mb-3">
        <label for="examAbbreviation" class="form-label small">Abbreviation</label>
        <input
          type="text"
          id="examAbbreviation"
          v-model="examAbbreviation"
          required
          class="form-control mt-1"
        />
      </div>
      <div class="mb-3">
        <label for="examDescription" class="form-label small">Description (Optional)</label>
        <textarea
          id="examDescription"
          v-model="examDescription"
          rows="3"
          class="form-control mt-1"
        ></textarea>
      </div>
      <div v-if="error" class="mb-3 small text-danger">
        {{ error }}
      </div>
      <div class="d-flex justify-content-end">
        <button
          type="button"
          @click="$emit('cancel')"
          class="btn btn-light me-2"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="btn btn-primary"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          <span v-if="loading"> Adding...</span>
          <span v-else>Add Exam</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../../api'; // Adjusted path based on typical project structure

const emit = defineEmits(['exam-added', 'cancel']);

const examName = ref('');
const examAbbreviation = ref('');
const examDescription = ref('');
const loading = ref(false);
const error = ref(null);

const handleSubmit = async () => {
  loading.value = true;
  error.value = null;
  try {
    const payload = {
      exam_name: examName.value,
      exam_abbreviation: examAbbreviation.value,
      description: examDescription.value || null, // Using 'description' as the key
    };
    await api.post('/api/v1/exams', payload);
    emit('exam-added');
    // Reset form
    examName.value = '';
    examAbbreviation.value = '';
    examDescription.value = '';
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.error || 'Failed to add exam. Please check the details and try again.';
    // Keep form data for correction if there was an error
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Add any component-specific styles here */
/* For example, to make the spinner align nicely with text in the button: */
.btn .spinner-border-sm {
  vertical-align: text-bottom;
}
</style>
