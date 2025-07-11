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

    <AddExamForm
      v-if="showAddForm"
      @exam-added="handleExamAdded"
      @cancel="showAddForm = false"
      class="mb-4"
    />

    <div v-if="loading" class="text-center text-muted">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p>Loading exams...</p>
    </div>
    <div v-else-if="error" class="text-center text-danger">{{ error }}</div>
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
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editExam(exam)">Edit</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteExam(exam)">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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
import api from '../api';
import AddExamForm from '../components/Exam/AddExamForm.vue'; // Corrected path

const exams = ref([]);
const loading = ref(false);
const error = ref(null);
const page = ref(1);
const pageSize = ref(10);
const totalPages = ref(1);
const showAddForm = ref(false);

const fetchExams = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get(`/api/v1/exams`, {
      params: { page: page.value, limit: pageSize.value },
    });
    exams.value = response.data.data || response.data.exams || [];
    totalPages.value = response.data.totalPages || response.data.total_pages || 1;
  } catch (err) {
    error.value = 'Failed to load exams.';
  } finally {
    loading.value = false;
  }
};

const handleExamAdded = () => {
  showAddForm.value = false;
  // Reset to page 1 and fetch exams to see the newly added one
  page.value = 1;
  fetchExams();
};

onMounted(fetchExams);
watch(page, fetchExams);
</script>

<style scoped>
/* Add any custom styles for the placeholder here if needed */
</style>
