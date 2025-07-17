<template>
  <div class="modal fade" :class="{ 'show d-block': show }" tabindex="-1" aria-labelledby="confirmToggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-warning text-dark py-2">
          <h5 class="modal-title" id="confirmToggleStatusModalLabel">Confirm Status Change</h5>
          <button type="button" class="btn-close" @click="handleCancel" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p v-if="question">
            Are you sure you want to <strong>{{ question.is_active ? 'disable' : 'enable' }}</strong> the question:
            <br>
            <strong>"{{ question.question_text }}"</strong> (ID: {{ question.question_id }})?
          </p>
          <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="handleCancel">Cancel</button>
          <button type="button" class="btn" :class="question?.is_active ? 'btn-warning' : 'btn-success'" @click="handleConfirm" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ loading ? 'Processing...' : (question?.is_active ? 'Disable' : 'Enable') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { updateQuestion, deleteQuestion } from '../../services/questionService';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  question: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['status-changed', 'cancel']);

const loading = ref(false);
const error = ref(null);

// Watch for changes in the 'show' prop to reset state when modal opens/closes
watch(() => props.show, (newVal) => {
  if (newVal) {
    error.value = null; // Clear any previous errors when modal opens
  }
});

const handleConfirm = async () => {
  if (!props.question) return;

  loading.value = true;
  error.value = null;

  try {
    let response;
    if (props.question.is_active) {
      // If currently active, we want to disable it (soft delete)
      response = await deleteQuestion(props.question.question_id);
    } else {
      // If currently inactive, we want to enable it (update is_active to true)
      response = await updateQuestion(props.question.question_id, { is_active: true });
    }

    if (response.status === 200) {
      emit('status-changed');
    } else {
      error.value = response.data?.error || 'An unexpected error occurred.';
    }
  } catch (err) {
    console.error('Error toggling question status:', err);
    error.value = err.response?.data?.error || err.message || 'Failed to change question status.';
  } finally {
    loading.value = false;
  }
};

const handleCancel = () => {
  emit('cancel');
};
</script>

<style scoped>
/* Add styles for the modal backdrop if not handled globally by Bootstrap */
.modal-backdrop.show {
  opacity: 0.5;
}
</style>