<template>
  <div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h2 fw-bold">Manage Exams</h1>
      <button
        @click="showAddForm = !showAddForm"
        class="btn btn-primary btn-sm"
      >
        {{ showAddForm ? 'Cancel' : 'Add New Exam' }}
      </button>
    </div>

    <!-- Add Exam Form -->
    <AddExamForm
      v-if="showAddForm"
      @exam-added="handleExamAdded"
      @cancel="showAddForm = false"
      class="mb-4"
    />

    <!-- Edit Exam Modal -->
    <EditExamModal
      v-if="showEditModal"
      :exam="editingExam"
      @exam-updated="handleExamUpdated"
      @cancel="showEditModal = false"
    />

    <!-- Disable Confirm Modal -->
    <DisableConfirmModal
      v-if="showDisableModal"
      :exam="disablingExam"
      :parentLoading="actionLoading"
      @confirm-disable="handleConfirmDisable"
      @cancel="showDisableModal = false"
    />

    <!-- Loading and Error States -->
    <div v-if="loading" class="text-center text-muted">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p>Loading exams...</p>
    </div>
    <div v-else-if="error" class="alert alert-danger text-center">{{ error }}</div>

    <!-- Exams Table -->
    <div v-else>
      <div v-if="exams.length === 0 && !showAddForm" class="text-center text-secondary py-5">
        <p>No exams found.</p>
        <p>Click "Add New Exam" to create one.</p>
      </div>
      <div v-else-if="exams.length > 0">
        <div class="table-responsive shadow-sm rounded-3">
          <table class="table table-bordered table-striped table-hover bg-white mb-4">
            <thead class="table-light">
              <tr>
                <th scope="col" class="text-center">S/N</th>
                <th scope="col">Name</th>
                <th scope="col">Abbreviation</th>
                <th scope="col">Description</th>
                <th scope="col" class="text-center">Active</th>
                <th scope="col" class="text-center">Created</th>
                <th scope="col" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(exam, idx) in exams" :key="exam.exam_id">
                <td class="text-center align-middle">{{ (page - 1) * pageSize + idx + 1 }}</td>
                <td class="align-middle">{{ exam.exam_name }}</td>
                <td class="align-middle">{{ exam.exam_abbreviation }}</td>
                <td class="align-middle">{{ exam.description || '-' }}</td>
                <td class="text-center align-middle">
                  <span :class="exam.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                    {{ exam.is_active ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td class="text-center align-middle">{{ new Date(exam.creation_date).toLocaleDateString() }}</td>
                <td class="text-center align-middle">
                  <button class="btn btn-sm btn-outline-primary me-1" @click="openEditModal(exam)">Edit</button>
                  <button
                    class="btn btn-sm"
                    :class="exam.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                    @click="openDisableModal(exam)"
                  >
                    {{ exam.is_active ? 'Disable' : 'Enable' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="totalPages > 1" class="d-flex justify-content-center align-items-center gap-2 mt-4">
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="page === 1"
            @click="page--"
          >Prev</button>
          <span class="small">Page {{ page }} of {{ totalPages }}</span>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="page === totalPages"
            @click="page++"
          >Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
// Import specific functions and the default api instance
import api, { getExams, updateExam, deleteExam, createExam as apiCreateExam } from '../api';
import AddExamForm from '../components/Exam/AddExamForm.vue';
import EditExamModal from '../components/Exam/EditExamModal.vue';
import DisableConfirmModal from '../components/Exam/DisableConfirmModal.vue'; // Renamed import

const exams = ref([]);
const loading = ref(false); // For main table loading
const actionLoading = ref(false); // For modal action buttons like save, disable
const error = ref(null); // For general errors like loading exams
const page = ref(1);
const pageSize = ref(10);
const totalPages = ref(1);
const showAddForm = ref(false);

const showEditModal = ref(false);
const editingExam = ref(null);
const showDisableModal = ref(false); // Renamed variable
const disablingExam = ref(null); // Renamed variable

const fetchExams = async () => {
  loading.value = true; // This is for the main table loading
  error.value = null;
  try {
    // Use the new getExams function
    const response = await getExams(page.value, pageSize.value);
    exams.value = response.data.data || response.data.exams || [];
    totalPages.value = response.data.pagination?.total_pages || response.data.totalPages || response.data.total_pages || 1;
  } catch (err) {
    console.error('Fetch exams error:', err);
    error.value = err.response?.data?.message || 'Failed to load exams. Please try again.';
  } finally {
    loading.value = false;
  }
};

const handleExamAdded = () => {
  showAddForm.value = false;
  page.value = 1;
  fetchExams();
  alert('Exam added successfully!'); // Placeholder notification
  // Note: AddExamForm.vue would also need to be updated to use apiCreateExam if it's making the call directly
};

// --- Edit Logic ---
const openEditModal = (exam) => {
  // Create a deep copy for editing to avoid mutating the original object in the list directly
  editingExam.value = JSON.parse(JSON.stringify(exam));
  showEditModal.value = true;
};

const handleExamUpdated = async (updatedExamData) => {
  showEditModal.value = false;
  try {
    // Use the new updateExam function
    await updateExam(updatedExamData.exam_id, updatedExamData);
    fetchExams();
    alert('Exam updated successfully!');
  } catch (err) {
    console.error('Update exam error:', err);
    alert(err.response?.data?.message || 'Failed to update exam.');
    // Optionally, reopen modal or handle error more gracefully
    // For now, we don't reopen, user can click edit again.
    // If EditExamModal handles its own loading/error state for submission, that would be better.
  }
};

// --- Disable/Enable Logic ---
const openDisableModal = (exam) => { // Renamed function
  disablingExam.value = JSON.parse(JSON.stringify(exam)); // Store a copy
  showDisableModal.value = true;
};

const handleConfirmDisable = async () => { // Renamed function
  if (!disablingExam.value) return;

  actionLoading.value = true;
  const examToDisable = disablingExam.value;
  // The API for soft delete actually makes it inactive.
  // To "enable" it, we would need to PUT it with is_active: true.
  // For "disable", we call the current deleteExam which makes it inactive.
  // The prompt in the modal now asks to type "disable" for the disable action.
  // If we are "enabling", we should change the is_active flag and PUT.

  if (examToDisable.is_active) { // If currently active, we want to disable it
    try {
      await deleteExam(examToDisable.exam_id); // This endpoint makes it inactive
      fetchExams();
      alert(`Exam "${examToDisable.exam_name}" disabled successfully!`);
    } catch (err) {
      console.error('Disable exam error:', err);
      alert(err.response?.data?.message || `Failed to disable exam "${examToDisable.exam_name}".`);
    }
  } else { // If currently inactive, we want to enable it
    try {
      // We need to make a PUT request to update is_active to true
      // The EditExamModal already handles this, but we are in a different flow.
      // We can call updateExam directly.
      await updateExam(examToDisable.exam_id, { ...examToDisable, is_active: true });
      fetchExams();
      alert(`Exam "${examToDisable.exam_name}" enabled successfully!`);
    } catch (err) {
      console.error('Enable exam error:', err);
      alert(err.response?.data?.message || `Failed to enable exam "${examToDisable.exam_name}".`);
    }
  }

  actionLoading.value = false;
  showDisableModal.value = false;
  disablingExam.value = null;
};

onMounted(fetchExams);
watch(page, fetchExams);
</script>

<style scoped>
/* Add any custom styles for the placeholder here if needed */
.alert {
  margin-top: 1rem;
}
</style>
