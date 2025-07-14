<template>
  <div
    class="modal fade"
    :class="{ 'show d-block': show }"
    tabindex="-1"
    role="dialog"
    aria-labelledby="toggleStatusModalLabel"
    :aria-hidden="!show"
  >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="toggleStatusModalLabel">
            Confirm Status Change
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="$emit('cancel')"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <p v-if="topic">
            Are you sure you want to
            <strong>{{ topic.is_active ? 'disable' : 'enable' }}</strong> the
            topic "{{ topic.topic_name }}"?
          </p>
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
            class="btn"
            :class="topic && topic.is_active ? 'btn-warning' : 'btn-success'"
            @click="handleConfirm"
            :disabled="isSubmitting"
          >
            <span
              v-if="isSubmitting"
              class="spinner-border spinner-border-sm"
              role="status"
              aria-hidden="true"
            ></span>
            {{ isSubmitting ? 'Processing...' : (topic && topic.is_active ? 'Disable' : 'Enable') }}
          </button>
        </div>
        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
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
import { ref } from 'vue';
import { updateTopic, deleteTopic } from '../../api';

const props = defineProps({
  show: Boolean,
  topic: Object,
});
const emit = defineEmits(['status-changed', 'cancel']);

const isSubmitting = ref(false);
const error = ref(null);

const handleConfirm = async () => {
  if (!props.topic) return;

  isSubmitting.value = true;
  error.value = null;

  try {
    if (props.topic.is_active) {
      await deleteTopic(props.topic.topic_id);
    } else {
      await updateTopic(props.topic.topic_id, { ...props.topic, is_active: true });
    }
    emit('status-changed');
  } catch (err) {
    error.value = err.response?.data?.error || 'An unexpected error occurred.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.modal.show {
  display: block;
}
.modal-backdrop.show {
  opacity: 0.5;
}
</style>
