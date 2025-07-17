<template>
  <div class="student-dashboard">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <h1 class="mb-4">Student Dashboard</h1>
          <p class="lead">Welcome to your exam portal. Here you can view available exams and track your progress.</p>
        </div>
      </div>
      
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Available Exams</h5>
              <div v-if="loading" class="text-center">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading exams...</p>
              </div>
              <div v-else-if="error" class="alert alert-danger" role="alert">
                Error loading exams: {{ error }}
              </div>
              <div v-else-if="exams.length === 0" class="alert alert-info" role="alert">
                No active exams available at the moment.
              </div>
              <ul v-else class="list-group">
                <li v-for="exam in exams" :key="exam.exam_id" class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h5>{{ exam.exam_name }} ({{ exam.exam_abbreviation }})</h5>
                    <p class="mb-0">{{ exam.description || 'No description provided.' }}</p>
                  </div>
                  <button class="btn btn-primary btn-sm">Start Exam</button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { getAllActiveExams } from '../../api';

const exams = ref([]);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
  try {
    const response = await getAllActiveExams();
    exams.value = response.data.data; // Adjust based on your API response structure
  } catch (err) {
    error.value = err.message;
    console.error("Failed to fetch active exams:", err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.student-dashboard {
  padding: 20px 0;
}
</style>