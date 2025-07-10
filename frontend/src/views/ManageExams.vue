<template>
  <div class="p-8">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Manage Exams</h1>
      <button
        @click="showAddForm = !showAddForm"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        {{ showAddForm ? 'Cancel' : 'Add New Exam' }}
      </button>
    </div>

    <AddExamForm
      v-if="showAddForm"
      @exam-added="handleExamAdded"
      @cancel="showAddForm = false"
      class="mb-6"
    />

    <div v-if="loading" class="text-center text-gray-500">Loading exams...</div>
    <div v-else-if="error" class="text-center text-red-500">{{ error }}</div>
    <div v-else>
      <div v-if="exams.length === 0 && !showAddForm" class="text-center text-gray-400 py-8">
        No exams found. Click "Add New Exam" to create one.
      </div>
      <div v-else-if="exams.length > 0">
        <div class="overflow-x-auto">
          <table class="min-w-full border mb-4 rounded-lg shadow-sm bg-white">
            <thead>
              <tr class="bg-gray-100 text-gray-700">
                <th class="py-2 px-4 border">S/N</th>
                <th class="py-2 px-4 border">Name</th>
                <th class="py-2 px-4 border">Abbreviation</th>
                <th class="py-2 px-4 border">Description</th>
                <th class="py-2 px-4 border">Active</th>
                <th class="py-2 px-4 border">Created</th>
                <th class="py-2 px-4 border">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(exam, idx) in exams" :key="exam.exam_id" class="hover:bg-gray-50">
                <td class="py-2 px-4 border text-center">{{ (page - 1) * pageSize + idx + 1 }}</td>
                <td class="py-2 px-4 border">{{ exam.exam_name }}</td>
                <td class="py-2 px-4 border">{{ exam.exam_abbreviation }}</td>
                <td class="py-2 px-4 border">{{ exam.description || '-' }}</td>
                <td class="py-2 px-4 border text-center">
                  <span :class="exam.is_active ? 'text-green-600 font-semibold' : 'text-gray-400'">
                    {{ exam.is_active ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td class="py-2 px-4 border text-center">{{ new Date(exam.creation_date).toLocaleString() }}</td>
                <td class="py-2 px-4 border text-center">--</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex justify-center items-center gap-2">
          <button
            class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200"
            :disabled="page === 1"
            @click="page--"
          >Prev</button>
          <span>Page {{ page }} of {{ totalPages }}</span>
          <button
            class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200"
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
