<template>
  <div class="modal fade" :class="{ 'show d-block': show }" tabindex="-1" aria-labelledby="editTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white py-2">
          <h5 class="modal-title" id="editTopicModalLabel">Edit Topic</h5>
          <button type="button" class="btn-close" @click="handleCancel" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="mb-3">
              <label for="editTopicName" class="form-label">Topic Name <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.topic_name }"
                id="editTopicName"
                v-model="editableTopic.topic_name"
                required
              >
              <div class="invalid-feedback" v-if="errors.topic_name">{{ errors.topic_name }}</div>
            </div>

            <div class="mb-3">
              <label for="editSubjectSelect" class="form-label">Subject <span class="text-danger">*</span></label>
              <select
                class="form-select"
                :class="{ 'is-invalid': errors.subject_id }"
                id="editSubjectSelect"
                v-model="editableTopic.subject_id"
                required
              >
                <option value="" disabled>Select a Subject</option>
                <option v-for="subject in subjects" :key="subject.subject_id" :value="subject.subject_id">
                  {{ subject.subject_name }} ({{ subject.subject_code }})
                </option>
              </select>
              <div class="invalid-feedback" v-if="errors.subject_id">{{ errors.subject_id }}</div>
            </div>

            <div class="mb-3">
              <label for="editTopicDescription" class="form-label">Description (Optional)</label>
              <textarea
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
                id="editTopicDescription"
                rows="3"
                v-model="editableTopic.description"
              ></textarea>
              <div class="invalid-feedback" v-if="errors.description">{{ errors.description }}</div>
            </div>

            <div v-if="errors.general" class="alert alert-danger mt-3">{{ errors.general }}</div>

            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-secondary me-2" @click="handleCancel">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ loading ? 'Updating...' : 'Update Topic' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { updateTopic } from '../../services/topicService'; // Import from new service
import { getSubjects } from '../../api'; // Assuming getSubjects is available

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  topic: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['topic-updated', 'cancel']);

const editableTopic = reactive({
  topic_id: null,
  topic_name: '',
  subject_id: '',
  description: ''
});

const subjects = ref([]);
const loading = ref(false);
const errors = reactive({});

// Watch for changes in the 'topic' prop to populate the form
watch(() => props.topic, (newTopic) => {
  if (newTopic) {
    editableTopic.topic_id = newTopic.topic_id;
    editableTopic.topic_name = newTopic.topic_name;
    editableTopic.subject_id = newTopic.subject_id;
    editableTopic.description = newTopic.description;
    Object.keys(errors).forEach(key => errors[key] = ''); // Clear errors on new topic load
  }
}, { immediate: true }); // Run immediately when component is mounted if topic is already set

// Watch for changes in the 'show' prop to fetch subjects when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchSubjects();
    Object.keys(errors).forEach(key => errors[key] = ''); // Clear errors when modal opens
  }
});

const fetchSubjects = async () => {
  try {
    // Fetch only active subjects for selection
    const response = await getSubjects(1, 100, true); // Assuming getSubjects supports active_only filter
    if (response && response.data && Array.isArray(response.data.data)) {
      subjects.value = response.data.data.filter(s => s.is_active);
    }
  } catch (error) {
    console.error('Error fetching subjects:', error);
    errors.general = 'Failed to load subjects for selection.';
  }
};

const validateForm = () => {
  let isValid = true;
  errors.topic_name = '';
  errors.subject_id = '';
  errors.description = '';
  errors.general = '';

  if (!editableTopic.topic_name.trim()) {
    errors.topic_name = 'Topic name is required.';
    isValid = false;
  }
  if (!editableTopic.subject_id) {
    errors.subject_id = 'Subject is required.';
    isValid = false;
  }
  // No specific validation for description other than trimming

  return isValid;
};

const handleSubmit = async () => {
  if (!validateForm()) {
    return;
  }

  loading.value = true;
  try {
    const payload = {
      topic_name: editableTopic.topic_name,
      subject_id: editableTopic.subject_id,
      description: editableTopic.description
    };
    const response = await updateTopic(editableTopic.topic_id, payload);
    if (response.status === 200) {
      emit('topic-updated');
    } else {
      console.warn('Unexpected response status:', response.status, response.data);
      errors.general = response.data?.error || 'An unexpected error occurred.';
    }
  } catch (err) {
    console.error('Error updating topic:', err);
    if (err.response && err.response.data) {
      if (err.response.data.error) {
        if (err.response.data.error.includes('Topic name already exists')) {
          errors.topic_name = err.response.data.error;
        } else if (err.response.data.error.includes('Subject not found')) {
          errors.subject_id = err.response.data.error;
        } else {
          errors.general = err.response.data.error;
        }
      }
    } else {
      errors.general = 'Network error or server is unreachable.';
    }
  } finally {
    loading.value = false;
  }
};

const handleCancel = () => {
  emit('cancel');
  // Reset errors when modal is cancelled
  Object.keys(errors).forEach(key => errors[key] = '');
};
</script>

<style scoped>
/* Add any specific styles for the modal here if needed */
.modal-backdrop.show {
  opacity: 0.5;
}
</style>