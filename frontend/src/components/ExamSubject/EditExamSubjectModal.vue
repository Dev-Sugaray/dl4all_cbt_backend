<template>
  <div class="modal fade show d-block" tabindex="-1" role="dialog" v-if="show">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Subject Details for Exam</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSubmit" v-if="editableExamSubject">
            <p><strong>Exam:</strong> {{ examName }}</p>
            <p><strong>Subject:</strong> {{ subjectName }}</p>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="editNumQuestions" class="form-label">Number of Questions</label>
                <input type="number" id="editNumQuestions" class="form-control" v-model.number="editableExamSubject.number_of_questions" required min="1">
              </div>
              <div class="col-md-6 mb-3">
                <label for="editTimeLimit" class="form-label">Time Limit (seconds)</label>
                <input type="number" id="editTimeLimit" class="form-control" v-model.number="editableExamSubject.time_limit_seconds" required min="60">
              </div>
            </div>

            <div class="mb-3">
              <label for="editScoringScheme" class="form-label">Scoring Scheme (JSON or Text)</label>
              <textarea id="editScoringScheme" class="form-control" v-model="editableExamSubject.scoring_scheme" rows="2" placeholder='e.g., {"correct": 1, "incorrect": -0.25}'></textarea>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" v-model="editableExamSubject.is_active" id="editIsActive">
              <label class="form-check-label" for="editIsActive">
                Active
              </label>
            </div>

            <div v-if="error" class="alert alert-danger mt-2">{{ error }}</div>
          </form>
          <div v-else class="text-center">Loading data...</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
          <button type="submit" class="btn btn-primary" @click="handleSubmit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ loading ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" v-if="show"></div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits, computed } from 'vue';

const props = defineProps({
  show: Boolean,
  examSubject: Object, // The full examSubject object containing subject_name etc.
  examNameProp: String, // Pass exam name if available
});

const emit = defineEmits(['close', 'exam-subject-updated']);

const editableExamSubject = ref(null);
const loading = ref(false);
const error = ref(null);

const examName = computed(() => props.examNameProp || (props.examSubject ? props.examSubject.exam_name : 'N/A'));
const subjectName = computed(() => props.examSubject ? props.examSubject.subject_name : 'N/A');


watch(() => props.show, (newVal) => {
  if (newVal && props.examSubject) {
    // Deep copy the prop to avoid mutating it directly
    editableExamSubject.value = JSON.parse(JSON.stringify(props.examSubject));
    error.value = null; // Reset error when modal opens
  } else if (!newVal) {
    editableExamSubject.value = null; // Clear data when modal is hidden
  }
});

const closeModal = () => {
  if (!loading.value) { // Prevent closing if an operation is in progress
    emit('close');
  }
};

const handleSubmit = async () => {
  if (!editableExamSubject.value) return;

  loading.value = true;
  error.value = null;
  try {
    // The parent component will handle the actual API call
    // We emit an event with the updated data
    emit('exam-subject-updated', { ...editableExamSubject.value });
    // The parent will then call API and close modal on success or show error
  } catch (err) {
    // This catch block might not be strictly necessary if parent handles all errors
    console.error('Submit error in modal:', err);
    error.value = 'An unexpected error occurred in the modal.';
    loading.value = false; // Ensure loading is reset on modal-internal error
  }
  // Note: loading state will be primarily managed by the parent after emitting
};

</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}
.modal-dialog-centered {
  display: flex;
  align-items: center;
  min-height: calc(100% - 1rem);
}
</style>
