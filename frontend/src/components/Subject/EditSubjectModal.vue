<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Subject</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <form @submit.prevent="submitForm">
          <div class="modal-body">
            <div class="mb-3">
              <label for="editSubjectName" class="form-label">Subject Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="editSubjectName" v-model="editableSubject.subject_name" required>
            </div>
            <div class="mb-3">
              <label for="editSubjectCode" class="form-label">Subject Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="editSubjectCode" v-model="editableSubject.subject_code" required>
            </div>
            <div class="mb-3">
              <label for="editSubjectDescription" class="form-label">Description</label>
              <textarea class="form-control form-control-sm" id="editSubjectDescription" v-model="editableSubject.description" rows="3"></textarea>
            </div>
             <div v-if="error" class="alert alert-danger mt-3 p-2 small">{{ error }}</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeModal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, defineProps, defineEmits } from 'vue';
import { updateSubject } from '../../api';

const props = defineProps({
  show: Boolean,
  subject: Object // The subject to edit
});

const emit = defineEmits(['subject-updated', 'cancel']);

const editableSubject = reactive({
  subject_id: null,
  subject_name: '',
  subject_code: '',
  description: ''
});
const loading = ref(false);
const error = ref(null);

// Moved resetForm definition before the watch that uses it
const resetForm = () => {
  editableSubject.subject_id = null;
  editableSubject.subject_name = '';
  editableSubject.subject_code = '';
  editableSubject.description = '';
  error.value = null;
  loading.value = false;
};

watch(() => props.subject, (newVal) => {
  if (newVal) {
    editableSubject.subject_id = newVal.subject_id;
    editableSubject.subject_name = newVal.subject_name;
    editableSubject.subject_code = newVal.subject_code;
    editableSubject.description = newVal.description || '';
    error.value = null; // Clear previous errors when a new subject is loaded
    loading.value = false;
  } else {
    resetForm(); // Reset if subject becomes null (e.g. modal closed and prop cleared)
  }
}, { immediate: true });


const submitForm = async () => {
  if (!editableSubject.subject_id) return;
  loading.value = true;
  error.value = null;
  try {
    // Only send fields that can be updated
    const payload = {
      subject_name: editableSubject.subject_name,
      subject_code: editableSubject.subject_code,
      description: editableSubject.description
    };
    await updateSubject(editableSubject.subject_id, payload);
    emit('subject-updated', { ...editableSubject }); // Emit a copy
    closeModal();
  } catch (err) {
    console.error('Update subject error:', err);
    error.value = err.response?.data?.error || err.message || 'Failed to update subject.';
  } finally {
    loading.value = false;
  }
};

const closeModal = () => {
  emit('cancel');
  // resetForm is called by the watch when props.subject becomes null if modal is closed by setting show=false
  // If closeModal is called directly (e.g. by X button), ensure reset happens too.
  // However, the watch with immediate:true and dependency on props.subject becoming null
  // should already handle this when the parent sets :subject="null" upon closing.
  // To be safe, explicitly calling it here is fine, or rely on the watcher.
  // For now, let's ensure it's clean for direct calls.
  if (props.show) { // Only reset if it was actually shown and then closed by this action
      resetForm();
  }
};

// resetForm is defined above the watch function now.

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
