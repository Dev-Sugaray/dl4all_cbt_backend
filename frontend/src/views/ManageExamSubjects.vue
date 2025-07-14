<template>
  <div class="p-4">
    <h1 class="h2 fw-bold mb-4">Manage Subjects for Exam: {{ examId }}</h1>
    <!-- Breadcrumb or link back to ManageExams -->
    <router-link :to="{ name: 'ManageExams' }" class="btn btn-outline-secondary btn-sm mb-4">
      &laquo; Back to Exams
    </router-link>

    <div v-if="loading" class="text-center text-muted">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p>Loading exam subjects...</p>
    </div>
    <div v-else-if="error" class="alert alert-danger text-center">{{ error }}</div>

    <div v-else>
      <!-- Edit Exam Subject Modal -->
      <EditExamSubjectModal
        :show="showEditModal"
        :exam-subject="editingExamSubject"
        :exam-name-prop="examName || `Exam ID: ${examId}`"
        @close="closeEditModal"
        @exam-subject-updated="handleUpdateExamSubject"
      />

      <!-- Add Subject to Exam Form -->
      <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h2 class="h5 mb-0">Add Subject to Exam</h2>
          <button class="btn btn-sm btn-primary" @click="showAddForm = !showAddForm">
            {{ showAddForm ? 'Cancel' : 'Add New Subject Link' }}
          </button>
        </div>
        <div v-if="showAddForm" class="card-body">
          <form @submit.prevent="handleAddExamSubject">
            <div class="mb-3">
              <label for="subjectSelect" class="form-label">Subject</label>
              <select id="subjectSelect" class="form-select" v-model="newExamSubject.subject_id" required>
                <option :value="null" disabled>-- Select a Subject --</option>
                <option v-for="subject in availableSubjects" :key="subject.subject_id" :value="subject.subject_id">
                  {{ subject.subject_name }}
                </option>
              </select>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="numQuestions" class="form-label">Number of Questions</label>
                <input type="number" id="numQuestions" class="form-control" v-model.number="newExamSubject.number_of_questions" required min="1">
              </div>
              <div class="col-md-6 mb-3">
                <label for="timeLimit" class="form-label">Time Limit (seconds)</label>
                <input type="number" id="timeLimit" class="form-control" v-model.number="newExamSubject.time_limit_seconds" required min="60">
              </div>
            </div>
            <div class="mb-3">
              <label for="scoringScheme" class="form-label">Scoring Scheme (JSON or Text)</label>
              <textarea id="scoringScheme" class="form-control" v-model="newExamSubject.scoring_scheme" rows="2" placeholder='e.g., {"correct": 1, "incorrect": -0.25}'></textarea>
            </div>
             <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" v-model="newExamSubject.is_active" id="addIsActive">
              <label class="form-check-label" for="addIsActive">
                Active
              </label>
            </div>
            <button type="submit" class="btn btn-success me-2" :disabled="addLoading">
                <span v-if="addLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ addLoading ? 'Adding...' : 'Add Subject to Exam' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="cancelAddForm">Cancel</button>
             <p v-if="addError" class="text-danger mt-2 small">{{ addError }}</p>
          </form>
        </div>
      </div>

      <!-- List of Associated Subjects -->
      <div class="card shadow-sm mt-4">
        <div class="card-header">
          <h2 class="h5 mb-0">Associated Subjects</h2>
        </div>
        <div class="card-body">
          <div v-if="examSubjects.length === 0" class="text-center text-secondary py-3">
            <p>No subjects are currently associated with this exam.</p>
          </div>
          <div v-else class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
              <thead class="table-light">
                <tr>
                  <th>Subject Name</th>
                  <th># Questions</th>
                  <th>Time Limit (s)</th>
                  <th>Scoring Scheme</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="es in examSubjects" :key="es.exam_subject_id">
                  <td>{{ es.subject_name }}</td>
                  <td>{{ es.number_of_questions }}</td>
                  <td>{{ es.time_limit_seconds }}</td>
                  <td>{{ es.scoring_scheme || '-' }}</td>
                  <td>
                    <span :class="es.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                      {{ es.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1 mb-1" @click="openEditModal(es)">Edit</button>
                    <button class="btn btn-sm mb-1"
                            :class="es.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                            @click="handleToggleActiveStatus(es)">
                      {{ es.is_active ? 'Disable' : 'Enable' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
// Import specific functions from api.js
import api, { getExamSubjects, getSubjects, createExamSubject, updateExamSubject, deleteExamSubject, getExams } from '../api';
// Assuming getExams can fetch a single exam by ID if we enhance it, or add getExamById
import EditExamSubjectModal from '../components/ExamSubject/EditExamSubjectModal.vue';

const route = useRoute();
const examId = computed(() => route.params.exam_id);

const examName = ref(''); // To store the exam name
const examSubjects = ref([]);
const allSubjects = ref([]); // To populate dropdown for adding subjects
const loading = ref(false);
const error = ref(null);

// Form state for adding a new exam subject
const showAddForm = ref(false);
const newExamSubject = ref({
  exam_id: parseInt(route.params.exam_id),
  subject_id: null,
  number_of_questions: 50, // Default value
  time_limit_seconds: 3600, // Default value (1 hour)
  scoring_scheme: '',
  is_active: true
});

// Edit Modal State
const showEditModal = ref(false);
const editingExamSubject = ref(null);

const addLoading = ref(false);
const addError = ref(null);

// Computed property to get subjects that are not yet added to this exam
const availableSubjects = computed(() => {
  const addedSubjectIds = new Set(examSubjects.value.map(es => es.subject_id));
  return allSubjects.value.filter(s => !addedSubjectIds.has(s.subject_id));
});

const resetNewExamSubjectForm = () => {
  newExamSubject.value = {
    subject_id: null,
    number_of_questions: 50,
    time_limit_seconds: 3600,
    scoring_scheme: '',
    is_active: true
  };
  addError.value = null;
};

const cancelAddForm = () => {
  showAddForm.value = false;
  resetNewExamSubjectForm();
};

const handleAddExamSubject = async () => {
  if (!newExamSubject.value.subject_id) {
    addError.value = "Please select a subject.";
    return;
  }
  addLoading.value = true;
  addError.value = null;
  try {
    const payload = {
      ...newExamSubject.value,
      exam_id: parseInt(examId.value) // Ensure exam_id is an integer
    };
    await createExamSubject(payload);
    await fetchExamSubjectDetails(); // Refresh the list
    showAddForm.value = false;
    resetNewExamSubjectForm();
    // TODO: Add success notification
  } catch (err) {
    console.error('Error adding exam subject:', err);
    addError.value = err.response?.data?.error || err.response?.data?.message || 'Failed to add subject to exam.';
  } finally {
    addLoading.value = false;
  }
};

const openEditModal = (examSubject) => {
  // Ensure all necessary fields are present for the modal, especially subject_name
  // The `examSubjects.value` list already contains `subject_name` from the backend JOIN
  editingExamSubject.value = JSON.parse(JSON.stringify(examSubject));
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editingExamSubject.value = null;
};

const handleUpdateExamSubject = async (updatedData) => {
  // The modal itself will manage its internal loading state for its submit button
  // This function is called when the modal emits 'exam-subject-updated'
  try {
    await updateExamSubject(updatedData.exam_subject_id, updatedData);
    await fetchExamSubjectDetails(); // Refresh list
    closeEditModal();
    // TODO: Add success notification
  } catch (err) {
    console.error('Error updating exam subject:', err);
    // Optionally, pass error back to modal or display globally
    alert(err.response?.data?.error || err.response?.data?.message || 'Failed to update exam subject details.');
    // Keep modal open if error for user to retry or see error (modal needs to handle error display)
    // For now, we close it, but modal could have its own error display.
    // The modal's internal 'error' ref can be set from here if needed, or it handles its own.
  }
};

const handleToggleActiveStatus = async (examSubjectToToggle) => {
  const originalStatus = examSubjectToToggle.is_active;
  const action = originalStatus ? 'Disable' : 'Enable';
  // Optimistically update UI or use a loading state per row if preferred
  // For simplicity, we'll refresh the whole list after action.

  // Optional: Add a confirmation dialog here
  if (!confirm(`Are you sure you want to ${action.toLowerCase()} the subject "${examSubjectToToggle.subject_name}" for this exam?`)) {
    return;
  }

  try {
    if (originalStatus) {
      // If active, we want to disable it (soft delete)
      await deleteExamSubject(examSubjectToToggle.exam_subject_id);
    } else {
      // If inactive, we want to enable it (update is_active to true)
      await updateExamSubject(examSubjectToToggle.exam_subject_id, { ...examSubjectToToggle, is_active: true });
    }
    await fetchExamSubjectDetails(); // Refresh the list
    // TODO: Add success notification
    alert(`Subject association ${action.toLowerCase()}d successfully.`);
  } catch (err) {
    console.error(`Error ${action.toLowerCase()}ing exam subject:`, err);
    alert(err.response?.data?.error || err.response?.data?.message || `Failed to ${action.toLowerCase()} subject association.`);
    // Optionally, revert optimistic UI update if one was made
  }
};


const fetchExamSubjectDetails = async () => {
  if (!examId.value) return;
  loading.value = true;
  error.value = null;
  try {
    // Fetch subjects associated with this exam
    // The backend getExamSubjects already filters by is_active=true by default.
    // We might need a way to fetch all (including inactive) if we want to "re-enable" them from this UI.
    // For now, assuming we only manage active ones or soft-delete makes them disappear from the main list.
    // The API needs to support filtering by exam_id.
    // Let's assume the getExamSubjects can take a params object.
    const examSubjectsResponse = await getExamSubjects({ exam_id: examId.value, limit: 100 }); // Fetch all for this exam
    examSubjects.value = examSubjectsResponse.data.data || [];

    // Fetch exam details to display exam name (optional, but nice UX)
    // This assumes an API endpoint like GET /api/v1/exams/{examId} exists
    // For now, we don't have getExamById in api.js, so we'll skip this or add it.
    // Let's assume examName is passed or fetched separately if needed.
    // For this example, we'll just use the ID.
  // Try to fetch the exam name
    try {
        const examDetailsResponse = await getExams(1, 1, { id: examId.value }); // Assuming getExams can filter by id or use a getExamById
        if (examDetailsResponse.data.data && examDetailsResponse.data.data.length > 0) {
            examName.value = examDetailsResponse.data.data[0].exam_name;
        } else {
            // Fallback if exam name couldn't be fetched
            const singleExamResponse = await api.get(`/api/v1/exams/${examId.value}`);
            examName.value = singleExamResponse.data.exam_name;
        }
    } catch (e) {
        console.warn("Could not fetch exam name:", e);
        examName.value = `ID: ${examId.value}`; // Fallback
    }


    // Fetch all subjects for the "Add Subject" form dropdown
    // Ensure getSubjects can fetch all subjects (e.g., by passing a large limit)
    const allSubjectsResponse = await getSubjects(1, 1000, true); // page 1, limit 1000, activeOnly = true
    allSubjects.value = allSubjectsResponse.data.data || [];

  } catch (err) {
    console.error('Error fetching exam subject details:', err);
    error.value = err.response?.data?.message || 'Failed to load subject details for the exam.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchExamSubjectDetails();
});

// TODO: Implement methods for:
// - Showing add/edit modals/forms
// - handleAddExamSubject
// - handleUpdateExamSubject
// - handleToggleExamSubjectActiveStatus (for disable/enable)
</script>

<style scoped>
/* Add any component-specific styles here */
.card {
  overflow: hidden; /* Ensures rounded corners are applied to inner table too */
}
</style>
