<template>
  <div class="p-4 border rounded-lg shadow-sm bg-white mt-4">
    <h2 class="text-xl font-semibold mb-3">Add New Exam</h2>
    <form @submit.prevent="handleSubmit">
      <div class="mb-3">
        <label for="examName" class="block text-sm font-medium text-gray-700">Exam Name</label>
        <input
          type="text"
          id="examName"
          v-model="examName"
          required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
      </div>
      <div class="mb-3">
        <label for="examAbbreviation" class="block text-sm font-medium text-gray-700">Abbreviation</label>
        <input
          type="text"
          id="examAbbreviation"
          v-model="examAbbreviation"
          required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
      </div>
      <div class="mb-3">
        <label for="examDescription" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
        <textarea
          id="examDescription"
          v-model="examDescription"
          rows="3"
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        ></textarea>
      </div>
      <div v-if="error" class="mb-3 text-sm text-red-600">
        {{ error }}
      </div>
      <div class="flex justify-end">
        <button
          type="button"
          @click="$emit('cancel')"
          class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="loading">Adding...</span>
          <span v-else>Add Exam</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../../api'; // Adjusted path based on typical project structure

const emit = defineEmits(['exam-added', 'cancel']);

const examName = ref('');
const examAbbreviation = ref('');
const examDescription = ref('');
const loading = ref(false);
const error = ref(null);

const handleSubmit = async () => {
  loading.value = true;
  error.value = null;
  try {
    const payload = {
      exam_name: examName.value,
      exam_abbreviation: examAbbreviation.value,
      description: examDescription.value || null, // Using 'description' as the key
    };
    await api.post('/api/v1/exams', payload);
    emit('exam-added');
    // Reset form
    examName.value = '';
    examAbbreviation.value = '';
    examDescription.value = '';
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.error || 'Failed to add exam. Please check the details and try again.';
    // Keep form data for correction if there was an error
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
