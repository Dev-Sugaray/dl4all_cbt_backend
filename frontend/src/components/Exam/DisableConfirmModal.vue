<template>
  <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Disable Exam</h5>
          <button type="button" class="btn-close" @click="cancelAction" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p v-if="exam">Are you sure you want to disable the exam: <strong>{{ exam.exam_name }}</strong>?</p>
          <p v-else>Are you sure you want to disable this exam?</p>
          <p>This will mark the exam as inactive, and it might not be accessible to users.</p>

          <div class="mb-3">
            <label for="confirmDisableText" class="form-label">To confirm, type "<strong>disable</strong>" in the box below:</label>
            <input type="text" class="form-control" id="confirmDisableText" v-model="confirmationText">
          </div>
          <div v-if="localError" class="alert alert-danger py-2">{{ localError }}</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="cancelAction" :disabled="parentLoading">Cancel</button>
          <button type="button" class="btn btn-warning" @click="confirmAction" :disabled="parentLoading || !canConfirm">
            <span v-if="parentLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ parentLoading ? 'Disabling...' : 'Disable Exam' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits, watch } from 'vue';

const props = defineProps({
  exam: {
    type: Object,
    default: null,
  },
  // Allow parent to control loading state for the button
  parentLoading: {
    type: Boolean,
    default: false,
  }
});

const emits = defineEmits(['confirm-disable', 'cancel']);

const confirmationText = ref('');
const localError = ref(''); // For errors specific to this modal, like incorrect confirmation text

// Reset confirmation text when the modal is presumably re-opened for a new exam
watch(() => props.exam, () => {
  confirmationText.value = '';
  localError.value = '';
});

const canConfirm = computed(() => confirmationText.value === 'disable');

const confirmAction = () => {
  if (canConfirm.value) {
    localError.value = '';
    emits('confirm-disable');
  } else {
    localError.value = 'Please type "disable" correctly to confirm.';
  }
};

const cancelAction = () => {
  emits('cancel');
};
</script>

<style scoped>
.modal {
  display: block; /* Ensure modal is visible when Vue component is rendered */
}
.alert {
  font-size: 0.9rem;
}
</style>
