<template>
  <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Exam</h5>
          <button type="button" class="btn-close" @click="cancelEdit" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitExam">
            <div class="mb-3">
              <label for="examName" class="form-label">Exam Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="examName" v-model="editableExam.exam_name" required>
            </div>
            <div class="mb-3">
              <label for="examAbbreviation" class="form-label">Abbreviation <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="examAbbreviation" v-model="editableExam.exam_abbreviation" required>
            </div>
            <div class="mb-3">
              <label for="examDescription" class="form-label">Description</label>
              <textarea class="form-control" id="examDescription" v-model="editableExam.description" rows="3"></textarea>
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="examIsActive" v-model="editableExam.is_active">
              <label class="form-check-label" for="examIsActive">Active</label>
            </div>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-secondary me-2" @click="cancelEdit">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ loading ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import api from '../../api'; // Assuming api.js is in src

const props = defineProps({
  exam: {
    type: Object,
    required: true,
  },
});

const emits = defineEmits(['exam-updated', 'cancel']);

const editableExam = ref({});
const loading = ref(false);
const error = ref(null);

// Watch for changes in the exam prop and update the local editableExam
watch(() => props.exam, (newExam) => {
  if (newExam) {
    // Create a deep copy to avoid mutating the prop directly
    editableExam.value = JSON.parse(JSON.stringify(newExam));
    // Ensure is_active is a boolean
    editableExam.value.is_active = Boolean(editableExam.value.is_active);
  } else {
    editableExam.value = { exam_name: '', exam_abbreviation: '', description: '', is_active: true };
  }
}, { immediate: true, deep: true });

const submitExam = async () => {
  if (!editableExam.value.exam_name || !editableExam.value.exam_abbreviation) {
    error.value = 'Exam Name and Abbreviation are required.';
    return;
  }
  loading.value = true;
  error.value = null;
  try {
    // The actual API call will be made from the parent ManageExams.vue
    // Here we just emit the data
    // However, for better encapsulation and if api.js is enhanced with updateExam,
    // we could call it here. For now, let's stick to the plan of parent handling API calls.
    // const response = await api.put(`/api/v1/exams/${editableExam.value.exam_id}`, editableExam.value);
    emits('exam-updated', editableExam.value);
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update exam. Please try again.';
    // If API call was made here, handle error notification
  } finally {
    loading.value = false;
  }
};

const cancelEdit = () => {
  emits('cancel');
};
</script>

<style scoped>
/* Scoped styles for the modal */
.modal {
  display: block; /* Ensure modal is visible when Vue component is rendered */
}
</style>
