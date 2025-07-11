<template>
  <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Delete</h5>
          <button type="button" class="btn-close" @click="cancelDelete" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p v-if="exam">Are you sure you want to delete the exam: <strong>{{ exam.exam_name }}</strong>?</p>
          <p v-else>Are you sure you want to delete this exam?</p>
          <p class="text-danger">This action cannot be undone.</p>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="cancelDelete" :disabled="loading">Cancel</button>
          <button type="button" class="btn btn-danger" @click="confirmDelete" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ loading ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, ref } from 'vue';

const props = defineProps({
  exam: {
    type: Object,
    default: null,
  },
});

const emits = defineEmits(['confirm-delete', 'cancel']);

// Error and loading states can be managed by the parent component which calls the API
// However, if an error specific to this modal's operation (not API related) occurs,
// it can be set here. For now, API errors will be handled by the parent.
const loading = ref(false); // Parent will control actual loading state during API call
const error = ref(null);   // Parent will display API errors

const confirmDelete = () => {
  emits('confirm-delete');
};

const cancelDelete = () => {
  emits('cancel');
};
</script>

<style scoped>
.modal {
  display: block; /* Ensure modal is visible when Vue component is rendered */
}
</style>
