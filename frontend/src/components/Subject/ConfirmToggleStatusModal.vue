<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ subject?.is_active ? 'Disable' : 'Enable' }} Subject</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p v-if="subject">
            Are you sure you want to {{ subject.is_active ? 'disable' : 'enable' }} the subject
            <strong>"{{ subject.subject_name }}"</strong> (Code: {{ subject.subject_code }})?
          </p>
          <p v-if="subject?.is_active" class="small text-muted">Disabling a subject will mark it as inactive. It can be re-enabled later.</p>
          <p v-else-if="subject && !subject.is_active" class="small text-muted">Enabling a subject will make it active and available for use.</p>
          <div v-if="error" class="alert alert-danger mt-3 p-2 small">{{ error }}</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeModal">Cancel</button>
          <button
            type="button"
            class="btn btn-sm"
            :class="subject?.is_active ? 'btn-warning' : 'btn-success'"
            @click="confirmAction"
            :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ subject?.is_active ? 'Disable' : 'Enable' }} Subject
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import { updateSubject, deleteSubject } from '../../api'; // Assuming api.js is in src

const props = defineProps({
  show: Boolean,
  subject: Object // The subject whose status is to be toggled
});

const emit = defineEmits(['status-changed', 'cancel']);

const loading = ref(false);
const error = ref(null);

const confirmAction = async () => {
  if (!props.subject) return;

  loading.value = true;
  error.value = null;
  const subjectToUpdate = props.subject;
  const newStatus = !subjectToUpdate.is_active;

  try {
    if (subjectToUpdate.is_active) {
      // Action is to disable (soft delete)
      await deleteSubject(subjectToUpdate.subject_id);
    } else {
      // Action is to enable
      await updateSubject(subjectToUpdate.subject_id, { is_active: newStatus });
    }
    emit('status-changed');
    closeModal();
  } catch (err) {
    console.error(`Failed to ${newStatus ? 'enable' : 'disable'} subject:`, err);
    error.value = err.response?.data?.error || err.message || `Failed to change subject status.`;
  } finally {
    loading.value = false;
  }
};

const closeModal = () => {
  error.value = null; // Clear error when closing
  loading.value = false;
  emit('cancel');
};
</script>

<style scoped>
.modal.d-block {
  display: block;
  opacity: 1;
}
.modal-dialog {
  z-index: 1050;
}
</style>
