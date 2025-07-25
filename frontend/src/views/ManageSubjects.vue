<template>
  <div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h2 fw-bold">Manage Subjects</h1>
      <button
        @click="openAddForm"
        class="btn btn-primary btn-sm"
      >
        Add New Subject
      </button>
    </div>

    <!-- Add Subject Form Component -->
    <AddSubjectForm
      v-if="showAddForm"
      @subject-added="handleSubjectAdded"
      @cancel="closeAddForm"
      class="mb-4"
    />

    <!-- Edit Subject Modal Component -->
    <EditSubjectModal
      :show="showEditModal"
      :subject="subjectToEdit"
      @subject-updated="handleSubjectUpdated"
      @cancel="closeEditModal"
    />

    <!-- Confirm Toggle Status Modal Component -->
    <ConfirmToggleStatusModal
      :show="showConfirmToggleModal"
      :subject="subjectToToggleStatus"
      @status-changed="handleStatusChanged"
      @cancel="closeConfirmToggleModal"
    />

    <!-- Loading and Error States for Table -->
    <div v-if="loading" class="text-center text-muted mt-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading subjects...</p>
    </div>
    <div v-else-if="error" class="alert alert-danger text-center mt-3">{{ error }}</div>

    <!-- Subjects Table -->
    <div v-else>
      <div v-if="subjects.length === 0 && !showAddForm" class="text-center text-secondary py-5">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-journal-bookmark mb-3" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M6 8V1h1v6.117L8.743 6.07a.5.5 0 0 1 .514 0L11 7.117V1h1v7a.5.5 0 0 1-.757.429L9 7.083 6.757 8.43A.5.5 0 0 1 6 8z"/>
          <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
          <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z"/>
        </svg>
        <p class="h5">No subjects found.</p>
        <p>Click "Add New Subject" to create one.</p>
      </div>
      <div v-else-if="subjects.length > 0">
        <div class="table-responsive shadow-sm rounded-3">
          <table class="table table-bordered table-striped table-hover bg-white mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col" class="text-center small py-2">S/N</th>
                <th scope="col" class="small py-2">Name</th>
                <th scope="col" class="small py-2">Code</th>
                <th scope="col" class="small py-2">Description</th>
                <th scope="col" class="text-center small py-2">Active</th>
                <th scope="col" class="text-center small py-2">Created</th>
                <th scope="col" class="text-center small py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(subject, idx) in subjects" :key="subject.subject_id">
                <td class="text-center align-middle small">{{ (currentPage - 1) * pageSize + idx + 1 }}</td>
                <td class="align-middle small">{{ subject.subject_name }}</td>
                <td class="align-middle small">{{ subject.subject_code }}</td>
                <td class="align-middle small" style="max-width: 300px; white-space: pre-wrap; word-break: break-word;">{{ subject.description || '-' }}</td>
                <td class="text-center align-middle small">
                  <span :class="subject.is_active ? 'badge bg-success-subtle text-success-emphasis' : 'badge bg-secondary-subtle text-secondary-emphasis'">
                    {{ subject.is_active ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td class="text-center align-middle small">{{ new Date(subject.creation_date).toLocaleDateString() }}</td>
                <td class="text-center align-middle">
                  <button class="btn btn-xs btn-outline-primary me-1 px-2 py-1" @click="openEditModal(subject)">
                    <small>Edit</small>
                  </button>
                  <button
                    class="btn btn-xs px-2 py-1"
                    :class="subject.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                    @click="openConfirmToggleModal(subject)"
                  >
                    <small>{{ subject.is_active ? 'Disable' : 'Enable' }}</small>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="totalPages > 1" class="d-flex justify-content-center align-items-center gap-2 mt-3">
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >Prev</button>
          <span class="small text-muted">Page {{ currentPage }} of {{ totalPages }}</span>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
// Import new components
import AddSubjectForm from '../components/Subject/AddSubjectForm.vue';
import EditSubjectModal from '../components/Subject/EditSubjectModal.vue';
import ConfirmToggleStatusModal from '../components/Subject/ConfirmToggleStatusModal.vue';
// Import API functions
import { getSubjects, createSubject, updateSubject, deleteSubject as apiDeleteSubject } from '../api';


const subjects = ref([]);
const loading = ref(false);
const error = ref(null);

const currentPage = ref(1);
const pageSize = ref(10);
const totalPages = ref(1);

const showAddForm = ref(false);
const showEditModal = ref(false);
const subjectToEdit = ref(null);

const showConfirmToggleModal = ref(false);
const subjectToToggleStatus = ref(null);

const fetchSubjects = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await getSubjects(currentPage.value, pageSize.value);

    // Defensive coding: Ensure the expected data structure exists
    if (response && response.data && Array.isArray(response.data.data)) {
      subjects.value = response.data.data.map(s => ({...s, is_active: Boolean(s.is_active)}));
      totalPages.value = response.data.meta?.total_pages || 1;
    } else {
      // Handle cases where the response or its data is not as expected
      console.error('Fetch subjects error: Unexpected response structure', response);
      error.value = 'Failed to load subjects due to unexpected server response.';
      subjects.value = []; // Clear subjects
      totalPages.value = 1; // Reset pagination
    }
  } catch (err) {
    console.error('Fetch subjects error (catch block):', err); // Log the caught error
    error.value = err.response?.data?.error || err.message || 'Failed to load subjects.';
    subjects.value = [];
  } finally {
    loading.value = false;
  }
};

// Add Form Logic
const openAddForm = () => {
  showAddForm.value = true;
   window.scrollTo({ top: 0, behavior: 'smooth' });
};
const closeAddForm = () => {
  showAddForm.value = false;
};
const handleSubjectAdded = () => {
  closeAddForm();
  fetchSubjects(); // Refresh list
  alert('Subject added successfully!'); // Basic notification
};

// Edit Modal Logic
const openEditModal = (subject) => {
  subjectToEdit.value = JSON.parse(JSON.stringify(subject)); // Deep copy for editing
  showEditModal.value = true;
};
const closeEditModal = () => {
  showEditModal.value = false;
  subjectToEdit.value = null;
};
const handleSubjectUpdated = () => {
  closeEditModal();
  fetchSubjects(); // Refresh list
  alert('Subject updated successfully!'); // Basic notification
};

// Confirm Toggle Status Modal Logic
const openConfirmToggleModal = (subject) => {
  subjectToToggleStatus.value = JSON.parse(JSON.stringify(subject)); // Deep copy
  showConfirmToggleModal.value = true;
};
const closeConfirmToggleModal = () => {
  showConfirmToggleModal.value = false;
  subjectToToggleStatus.value = null;
};
const handleStatusChanged = () => {
  closeConfirmToggleModal();
  fetchSubjects(); // Refresh list
  // The modal itself will show success/error, or we can alert here too.
  // For now, assuming modal handles its notifications or parent gives a generic one.
  alert(`Subject status changed successfully!`);
};


onMounted(fetchSubjects);
watch(currentPage, fetchSubjects);

</script>

<style scoped>
.btn-xs {
  --bs-btn-padding-y: .1rem;
  --bs-btn-padding-x: .4rem;
  --bs-btn-font-size: .75rem;
}
.modal.d-block {
  display: block;
  opacity: 1;
}
.modal-dialog {
  z-index: 1050; /* Ensure modal is on top */
}
.table th.small, .table td.small {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  font-size: 0.875em;
}
</style>
