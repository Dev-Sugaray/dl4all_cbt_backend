<template>
  <div
    class="modal fade"
    :class="{ 'show d-block': show }"
    tabindex="-1"
    role="dialog"
    aria-labelledby="editTopicModalLabel"
    :aria-hidden="!show"
  >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content shadow-lg">
        <div class="modal-header">
          <h5 class="modal-title" id="editTopicModalLabel">Edit Topic</h5>
          <button
            type="button"
            class="btn-close"
            @click="$emit('cancel')"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSubmit" v-if="localTopic">
            <div class="mb-3">
              <label for="editTopicName" class="form-label">Topic Name</label>
              <input
                id="editTopicName"
                v-model="localTopic.topic_name"
                type="text"
                class="form-control"
                required
              />
            </div>
            <div class="mb-3">
              <label for="editSubject" class="form-label">Subject</label>
              <select
                id="editSubject"
                v-model="localTopic.subject_id"
                class="form-select"
                required
              >
                <option
                  v-for="subject in subjects"
                  :key="subject.subject_id"
                  :value="subject.subject_id"
                >
                  {{ subject.subject_name }}
                </option>
              </select>
            </div>
            <div class="mb-3">
              <label for="editDescription" class="form-label"
                >Description</label
              >
              <textarea
                id="editDescription"
                v-model="localTopic.description"
                class="form-control"
                rows="3"
              ></textarea>
            </div>
            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
          </form>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="$emit('cancel')"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="handleSubmit"
            :disabled="isSubmitting"
          >
            <span
              v-if="isSubmitting"
              class="spinner-border spinner-border-sm"
              role="status"
              aria-hidden="true"
            ></span>
            {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
    <div
      class="modal-backdrop fade"
      :class="{ show: show }"
      @click="$emit('cancel')"
    ></div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { getSubjects, updateTopic } from '../../api';

const props = defineProps({
  show: Boolean,
  topic: Object,
});
const emit = defineEmits(['topic-updated', 'cancel']);

const localTopic = ref(null);
const subjects = ref([]);
const isSubmitting = ref(false);
const error = ref(null);

watch(
  () => props.topic,
  (newTopic) => {
    if (newTopic) {
      localTopic.value = JSON.parse(JSON.stringify(newTopic));
    } else {
      localTopic.value = null;
    }
  },
  { immediate: true }
);

const fetchSubjects = async () => {
  try {
    const response = await getSubjects(1, 1000, true);
    if (response && response.data && Array.isArray(response.data.data)) {
      subjects.value = response.data.data;
    }
  } catch (err) {
    console.error('Failed to fetch subjects:', err);
    error.value = 'Failed to load subjects for selection.';
  }
};

const handleSubmit = async () => {
  if (!localTopic.value) return;
  isSubmitting.value = true;
  error.value = null;
  try {
    await updateTopic(localTopic.value.topic_id, localTopic.value);
    emit('topic-updated');
  } catch (err) {
    error.value = err.response?.data?.error || 'An unexpected error occurred.';
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(fetchSubjects);
</script>

<style scoped>
.modal.show {
  display: block;
}
.modal-backdrop.show {
  opacity: 0.5;
}
</style>
