<template>
  <div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h2 fw-bold">Manage Questions</h1>
      <div class="d-flex gap-2">
        <button
          @click="openAddForm"
          class="btn btn-primary btn-sm"
        >
          Add New Question
        </button>
        <button
          @click="toggleBulkUploadSection"
          class="btn btn-info btn-sm"
        >
          {{ showBulkUpload ? 'Hide Bulk Upload' : 'Bulk Upload Questions' }}
        </button>
      </div>
    </div>

    <!-- Bulk Upload Section -->
    <div v-if="showBulkUpload" class="card mb-4 shadow-sm">
      <div class="card-header bg-light fw-bold">Bulk Upload Questions (CSV)</div>
      <div class="card-body">
        <p class="card-text">Upload a CSV file containing multiple-choice questions. Please ensure your CSV adheres to the specified format:</p>
        <div class="alert alert-info small" role="alert">
          <div v-html="csvFormatInstructionsHtml"></div>
          <p class="mt-3 mb-0">
            <a href="/example.csv" download="example.csv" class="btn btn-sm btn-outline-primary">
              Download Example CSV Format
            </a>
          </p>
        </div>
        <div class="mb-3">
          <label for="examSubjectSelect" class="form-label">Select Exam Subject:</label>
          <select class="form-select" id="examSubjectSelect" v-model="selectedExamSubjectId">
            <option :value="null">-- Select Exam Subject --</option>
            <option v-for="es in examSubjects" :key="es.exam_subject_id" :value="es.exam_subject_id">
              {{ es.exam_name }} - {{ es.subject_name }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <label for="topicSelect" class="form-label">Select Topic (Optional):</label>
          <select class="form-select" id="topicSelect" v-model="selectedTopicId">
            <option :value="null">-- Select Topic --</option>
            <option v-for="topic in topics" :key="topic.topic_id" :value="topic.topic_id">
              {{ topic.topic_name }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <label for="csvFile" class="form-label">Select CSV File:</label>
          <input class="form-control" type="file" id="csvFile" @change="handleFileUpload" accept=".csv">
        </div>
        <button @click="uploadCsv" class="btn btn-success" :disabled="!selectedFile || uploading || !selectedExamSubjectId">
          <span v-if="uploading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          {{ uploading ? 'Uploading...' : 'Upload CSV' }}
        </button>
        <div v-if="uploadMessage" :class="uploadError ? 'text-danger' : 'text-success'" class="mt-2">{{ uploadMessage }}</div>
      </div>
    </div>

    <!-- Add Question Form Component -->
    <AddQuestionForm
      v-if="showAddForm"
      @question-added="handleQuestionAdded"
      @cancel="closeAddForm"
      class="mb-4"
    />

    <!-- Edit Question Modal Component -->
    <EditQuestionModal
      :show="showEditModal"
      :question="questionToEdit"
      @question-updated="handleQuestionUpdated"
      @cancel="closeEditModal"
    />

    <!-- Confirm Toggle Status Modal Component -->
    <ConfirmToggleStatusModal
      :show="showConfirmToggleModal"
      :question="questionToToggleStatus"
      @status-changed="handleStatusChanged"
      @cancel="closeConfirmToggleModal"
    />

    <!-- Loading and Error States for Table -->
    <div v-if="loading" class="text-center text-muted mt-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading questions...</p>
    </div>
    <div v-else-if="error" class="alert alert-danger text-center mt-3">{{ error }}</div>

    <!-- Questions Table -->
    <div v-else>
      <div v-if="questions.length === 0 && !showAddForm" class="text-center text-secondary py-5">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-question-circle mb-3" viewBox="0 0 16 16">
          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
          <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.217-1.134.88 0 1.254.724 1.254 1.572 0 1.007-.282 1.49-.517 1.839-.459.664-.057 1.182.103 1.639.072.2.032.417-.046.609-.307.75-.628 1.093-1.75 1.093-.118 0-.252-.02-.33-.038l-.045-.009c-.447-.075-.59-.228-.674-.362L5.94 8.656H4.28c-.227 0-.393.201-.368.426l.008.095c.09.823.417 1.352 1.02 1.352.396 0 .7-.28.833-.44.51-.659.406-1.135.309-1.488-.076-.281-.147-.574-.22-.854-.149-.581-.073-.925.129-1.124.043-.042.12-.144.256-.315z"/>
          <path d="M8 11.199c-.756 0-1.348.603-1.348 1.348 0 .745.592 1.348 1.348 1.348.745 0 1.348-.603 1.348-1.348 0-.745-.592-1.348-1.348-1.348z"/>
        </svg>
        <p class="h5">No questions found.</p>
        <p>Click "Add New Question" to create one.</p>
      </div>
      <div v-else-if="questions.length > 0">
        <div class="table-responsive shadow-sm rounded-3">
          <table class="table table-bordered table-striped table-hover bg-white mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col" class="text-center small py-2">S/N</th>
                <th scope="col" class="small py-2">Question Text</th>
                <th scope="col" class="small py-2">Type</th>
                <th scope="col" class="small py-2">Subject</th>
                <th scope="col" class="small py-2">Topic</th>
                <th scope="col" class="small py-2">Correct Answer</th>
                <th scope="col" class="text-center small py-2">Active</th>
                <th scope="col" class="text-center small py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(question, idx) in questions" :key="question.question_id">
                <td class="text-center align-middle small">{{ (currentPage - 1) * pageSize + idx + 1 }}</td>
                <td class="align-middle small" style="max-width: 300px; white-space: pre-wrap; word-break: break-word;">{{ question.question_text }}</td>
                <td class="align-middle small">{{ question.question_type }}</td>
                <td class="align-middle small">{{ question.subject_name }} ({{ question.subject_code }})</td>
                <td class="align-middle small">{{ question.topic_name || '-' }}</td>
                <td class="align-middle small">{{ question.correct_answer }}</td>
                <td class="text-center align-middle small">
                  <span :class="question.is_active ? 'badge bg-success-subtle text-success-emphasis' : 'badge bg-secondary-subtle text-secondary-emphasis'">
                    {{ question.is_active ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td class="text-center align-middle">
                  <button class="btn btn-xs btn-outline-primary me-1 px-2 py-1" @click="openEditModal(question)">
                    <small>Edit</small>
                  </button>
                  <button
                    class="btn btn-xs px-2 py-1"
                    :class="question.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                    @click="openConfirmToggleModal(question)"
                  >
                    <small>{{ question.is_active ? 'Disable' : 'Enable' }}</small>
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
import AddQuestionForm from '../components/Question/AddQuestionForm.vue';
import EditQuestionModal from '../components/Question/EditQuestionModal.vue';
import ConfirmToggleStatusModal from '../components/Question/ConfirmToggleStatusModal.vue';
import { getQuestions, uploadQuestionsCsv, getExamSubjects, getTopics } from '../services/questionService';

const questions = ref([]);
const loading = ref(false);
const error = ref(null);

const currentPage = ref(1);
const pageSize = ref(10);
const totalPages = ref(1);

const showAddForm = ref(false);
const showEditModal = ref(false);
const questionToEdit = ref(null);

const showConfirmToggleModal = ref(false);
const questionToToggleStatus = ref(null);

// Bulk Upload Refs
const showBulkUpload = ref(false);
const selectedFile = ref(null);
const uploading = ref(false);
const uploadMessage = ref('');
const uploadError = ref(false);
const examSubjects = ref([]);
const topics = ref([]);
const selectedExamSubjectId = ref(null);
const selectedTopicId = ref(null);

const fetchQuestions = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await getQuestions(currentPage.value, pageSize.value);
    if (response && response.data && Array.isArray(response.data.data)) {
      questions.value = response.data.data.map(q => ({...q, is_active: Boolean(q.is_active)}));
      totalPages.value = response.data.meta?.total_pages || 1;
    } else {
      console.error('Fetch questions error: Unexpected response structure', response);
      error.value = 'Failed to load questions due to unexpected server response.';
      questions.value = [];
      totalPages.value = 1;
    }
  } catch (err) {
    console.error('Fetch questions error (catch block):', err);
    error.value = err.response?.data?.error || err.message || 'Failed to load questions.';
    questions.value = [];
  } finally {
    loading.value = false;
  }
};

const csvFormatInstructionsHtml = ref(`
<h2>Bulk Question Upload CSV Format</h2>
<p>This document describes the required format for the CSV file used to bulk upload multiple-choice questions.</p>
<h3>File Encoding</h3>
<p>The CSV file must be UTF-8 encoded.</p>
<h3>Header Row</h3>
<p>The first row of the CSV file <strong>must</strong> be a header row and contain the following columns in the exact order:</p>
<p><code>question_text,correct_answer,option_A,option_B,option_C,option_D,explanation,difficulty_level</code></p>
<h3>Column Descriptions</h3>
<ul>
<li><strong><code>question_text</code></strong> (Required, String): The full text of the question.</li>
<li><strong><code>correct_answer</code></strong> (Required, String): The letter corresponding to the correct option (e.g., "A", "B", "C", "D"). This must match one of the <code>option_</code> letters provided.</li>
<li><strong><code>option_A</code></strong> (Required, String): The text for option A.</li>
<li><strong><code>option_B</code></strong> (Required, String): The text for option B.</li>
<li><strong><code>option_C</code></strong> (Required, String): The text for option C.</li>
<li><strong><code>option_D</code></strong> (Required, String): The text for option D.</li>
<li><strong><code>explanation</code></strong> (Optional, String): An explanation for the correct answer. Can be left empty.</li>
<li><strong><code>difficulty_level</code></strong> (Optional, String): The difficulty level of the question (e.g., "Easy", "Medium", "Hard"). Can be left empty.</li>
</ul>
<h3>Example CSV</h3>
<pre><code class="language-csv">question_text,correct_answer,option_A,option_B,option_C,option_D,explanation,difficulty_level
"What is the capital of France?",A,"Paris","London","Berlin","Rome","Paris is the capital of France.","Easy"
"Who painted the Mona Lisa?",B,"Vincent van Gogh","Leonardo da Vinci","Pablo Picasso","Claude Monet",,"Medium"
"Which planet is known as the Red Planet?",C,"Earth","Venus","Mars","Jupiter","Mars is often called the Red Planet.","Easy"
</code></pre>
<h3>Important Notes</h3>
<ul>
<li>Each row after the header represents a single multiple-choice question.</li>
<li>Ensure that the <code>correct_answer</code> column contains a letter that corresponds to one of the provided <code>option_</code> columns.</li>
<li>Do not include extra columns beyond <code>difficulty_level</code>.</li>
<li>Empty optional fields should be left blank (e.g., ,, for <code>explanation</code>).</li>
<li>The system currently only supports multiple-choice questions via bulk upload.</li>
</ul>
`);

// Fetch Exam Subjects
const fetchExamSubjects = async () => {
  try {
    const response = await getExamSubjects();
    if (response && response.data && Array.isArray(response.data.data)) {
      examSubjects.value = response.data.data;
    } else {
      console.error('Fetch exam subjects error: Unexpected response structure', response);
    }
  } catch (err) {
    console.error('Fetch exam subjects error:', err);
  }
};

// Fetch Topics (filtered by subject if selected)
const fetchTopics = async (subjectId = null) => {
  try {
    const response = await getTopics(1, 100, subjectId);
    if (response && response.data && Array.isArray(response.data.data)) {
      topics.value = response.data.data;
    } else {
      console.error('Fetch topics error: Unexpected response structure', response);
    }
  } catch (err) {
    console.error('Fetch topics error:', err);
  }
};

// Watch for changes in selectedExamSubjectId to filter topics
watch(selectedExamSubjectId, (newVal) => {
  if (newVal) {
    const selectedExamSubject = examSubjects.value.find(es => es.exam_subject_id === newVal);
    if (selectedExamSubject) {
      fetchTopics(selectedExamSubject.subject_id);
    } else {
      topics.value = [];
      selectedTopicId.value = null;
    }
  } else {
    topics.value = [];
    selectedTopicId.value = null;
  }
});

// Toggle Bulk Upload Section
const toggleBulkUploadSection = () => {
  showBulkUpload.value = !showBulkUpload.value;
  if (showBulkUpload.value) {
    fetchExamSubjects();
  }
};

// Handle File Selection
const handleFileUpload = (event) => {
  selectedFile.value = event.target.files[0];
  uploadMessage.value = '';
  uploadError.value = false;
};

// Upload CSV
const uploadCsv = async () => {
  if (!selectedFile.value) {
    uploadMessage.value = 'Please select a file first.';
    uploadError.value = true;
    return;
  }

  if (!selectedExamSubjectId.value) {
    uploadMessage.value = 'Please select an Exam Subject.';
    uploadError.value = true;
    return;
  }

  uploading.value = true;
  uploadMessage.value = '';
  uploadError.value = false;

  try {
    const formData = new FormData();
    formData.append('csv_file', selectedFile.value);
    formData.append('exam_subject_id', selectedExamSubjectId.value);
    if (selectedTopicId.value) {
      formData.append('topic_id', selectedTopicId.value);
    }

    const response = await uploadQuestionsCsv(formData);
    uploadMessage.value = response.message || 'Questions uploaded successfully!';
    uploadError.value = false;
    selectedFile.value = null; // Clear selected file
    document.getElementById('csvFile').value = ''; // Clear file input
    fetchQuestions(); // Refresh question list
  } catch (err) {
    uploadMessage.value = err.response?.data?.error || err.message || 'Failed to upload questions.';
    uploadError.value = true;
    console.error('CSV Upload Error:', err);
  } finally {
    uploading.value = false;
  }
};

// Open Add Question Form
const openAddForm = () => {
  showAddForm.value = true;
};

// Close Add Question Form
const closeAddForm = () => {
  showAddForm.value = false;
};

// On component mount, fetch questions
onMounted(() => {
  fetchQuestions();
});


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
  z-index: 1050;
}
.table th.small, .table td.small {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  font-size: 0.875em;
}
</style>