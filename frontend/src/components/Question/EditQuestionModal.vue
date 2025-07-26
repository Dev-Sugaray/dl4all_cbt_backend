<template>
  <div class="modal fade" :class="{ 'show d-block': show }" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white py-2">
          <h5 class="modal-title" id="editQuestionModalLabel">Edit Question</h5>
          <button type="button" class="btn-close" @click="handleCancel" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="mb-3">
              <label for="editExamSubjectSelect" class="form-label">Exam Subject <span class="text-danger">*</span></label>
              <select
                class="form-select"
                :class="{ 'is-invalid': errors.exam_subject_id }"
                id="editExamSubjectSelect"
                v-model="editableQuestion.exam_subject_id"
                required
              >
                <option value="" disabled>Select an Exam Subject</option>
                <option v-for="es in examSubjects" :key="es.exam_subject_id" :value="es.exam_subject_id">
                  {{ es.exam_name }} - {{ es.subject_name }} ({{ es.subject_code }})
                </option>
              </select>
              <div class="invalid-feedback" v-if="errors.exam_subject_id">{{ errors.exam_subject_id }}</div>
            </div>

            <div class="mb-3">
              <label for="editTopicSelect" class="form-label">Topic (Optional)</label>
              <select
                class="form-select"
                :class="{ 'is-invalid': errors.topic_id }"
                id="editTopicSelect"
                v-model="editableQuestion.topic_id"
              >
                <option :value="null" selected>Select a Topic</option>
                <option v-for="topic in topics" :key="topic.topic_id" :value="topic.topic_id">
                  {{ topic.topic_name }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="errors.topic_id">{{ errors.topic_id }}</div>
            </div>

            <div class="mb-3">
              <label for="editQuestionText" class="form-label">Question Text <span class="text-danger">*</span></label>
              <textarea
                class="form-control"
                :class="{ 'is-invalid': errors.question_text }"
                id="editQuestionText"
                rows="4"
                v-model="editableQuestion.question_text"
                required
              ></textarea>
              <div class="invalid-feedback" v-if="errors.question_text">{{ errors.question_text }}</div>
            </div>

            <div class="mb-3">
              <label for="editQuestionType" class="form-label">Question Type <span class="text-danger">*</span></label>
              <select
                class="form-select"
                :class="{ 'is-invalid': errors.question_type }"
                id="editQuestionType"
                v-model="editableQuestion.question_type"
                required
              >
                <option value="" disabled>Select Type</option>
                <option value="multiple_choice">Multiple Choice</option>
                <option value="true_false">True/False</option>
                <option value="short_answer">Short Answer</option>
              </select>
              <div class="invalid-feedback" v-if="errors.question_type">{{ errors.question_type }}</div>
            </div>

            <div class="mb-3">
              <label for="editCorrectAnswer" class="form-label">Correct Answer (Option Letter for MC, Text for SA) <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.correct_answer }"
                id="editCorrectAnswer"
                v-model="editableQuestion.correct_answer"
                required
              >
              <div class="invalid-feedback" v-if="errors.correct_answer">{{ errors.correct_answer }}</div>
            </div>

            <div class="mb-3">
              <label for="editExplanation" class="form-label">Explanation (Optional)</label>
              <textarea
                class="form-control"
                :class="{ 'is-invalid': errors.explanation }"
                id="editExplanation"
                rows="2"
                v-model="editableQuestion.explanation"
              ></textarea>
              <div class="invalid-feedback" v-if="errors.explanation">{{ errors.explanation }}</div>
            </div>

            <div class="mb-3">
              <label for="editDifficultyLevel" class="form-label">Difficulty Level (Optional)</label>
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.difficulty_level }"
                id="editDifficultyLevel"
                v-model="editableQuestion.difficulty_level"
              >
              <div class="invalid-feedback" v-if="errors.difficulty_level">{{ errors.difficulty_level }}</div>
            </div>

            <hr>
            <h5>Options <span class="text-danger">*</span></h5>
            <div v-for="(option, index) in editableQuestion.options" :key="index" class="row mb-2 align-items-center">
              <div class="col-2">
                <label :for="`editOptionLetter-${index}`" class="form-label">Letter</label>
                <input type="text" class="form-control" :id="`editOptionLetter-${index}`" v-model="option.option_letter" required>
              </div>
              <div class="col-8">
                <label :for="`editOptionText-${index}`" class="form-label">Text</label>
                <input type="text" class="form-control" :id="`editOptionText-${index}`" v-model="option.option_text" required>
              </div>
              <div class="col-1 d-flex align-items-end">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" v-model="option.is_correct" :id="`editIsCorrect-${index}`">
                  <label class="form-check-label" :for="`editIsCorrect-${index}`">Correct</label>
                </div>
              </div>
              <div class="col-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm" @click="removeOption(index)">X</button>
              </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-2" @click="addOption">Add Option</button>
            <div class="invalid-feedback d-block" v-if="errors.options">{{ errors.options }}</div>

            <div v-if="errors.general" class="alert alert-danger mt-3">{{ errors.general }}</div>

            <div class="d-flex justify-content-end mt-4">
              <button type="button" class="btn btn-secondary me-2" @click="handleCancel">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ loading ? 'Updating...' : 'Update Question' }}
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
import { updateQuestion } from '../../services/questionService';
import { getExamSubjects } from '../../api'; // Assuming this fetches exam subjects
import { getTopics } from '../../services/topicService'; // Assuming this fetches topics

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  question: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['question-updated', 'cancel']);

const editableQuestion = reactive({
  question_id: null,
  exam_subject_id: '',
  topic_id: null,
  question_text: '',
  question_type: '',
  correct_answer: '',
  explanation: '',
  difficulty_level: '',
  options: [],
});

const examSubjects = ref([]);
const topics = ref([]);
const loading = ref(false);
const errors = reactive({});

// Watch for changes in the 'question' prop to populate the form
watch(() => props.question, (newQuestion) => {
  if (newQuestion) {
    Object.assign(editableQuestion, JSON.parse(JSON.stringify(newQuestion)));
    // Ensure is_correct is boolean for checkboxes
    editableQuestion.options = editableQuestion.options.map(opt => ({
      ...opt,
      is_correct: Boolean(opt.is_correct)
    }));
    Object.keys(errors).forEach(key => errors[key] = ''); // Clear errors on new question load
  }
}, { immediate: true });

// Watch for changes in the 'show' prop to fetch dependencies when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchDependencies();
    Object.keys(errors).forEach(key => errors[key] = ''); // Clear errors when modal opens
  }
});

const fetchDependencies = async () => {
  try {
    const [examSubjectsRes, topicsRes] = await Promise.all([
      getExamSubjects({ active_only: true }),
      getTopics(1, 100, null, true)
    ]);

    if (examSubjectsRes && examSubjectsRes.data && Array.isArray(examSubjectsRes.data.data)) {
      examSubjects.value = examSubjectsRes.data.data;
    }
    if (topicsRes && topicsRes.data && Array.isArray(topicsRes.data.data)) {
      topics.value = topicsRes.data.data;
    }
  } catch (err) {
    console.error('Error fetching dependencies:', err);
    errors.general = 'Failed to load necessary data (Exam Subjects, Topics).';
  }
};

// Watch for changes in exam_subject_id to filter topics
watch(() => editableQuestion.exam_subject_id, async (newExamSubjectId) => {
  // Find the selected exam subject from the fetched list
  const selectedExamSubject = examSubjects.value.find(es => es.exam_subject_id === newExamSubjectId);

  if (selectedExamSubject && selectedExamSubject.subject_id) {
    try {
      // Fetch topics using the correct subject_id
      const response = await getTopics(1, 100, selectedExamSubject.subject_id, true);
      if (response && response.data && Array.isArray(response.data.data)) {
        topics.value = response.data.data;
      } else {
        topics.value = []; // Clear topics if no topics are found for the subject
      }
    } catch (err) {
      console.error('Error fetching topics for the selected subject:', err);
      errors.general = 'Failed to load topics for the selected subject.';
    }
  } else {
    topics.value = []; // Clear topics if no exam subject is selected
  }
  // Do not reset topic_id here, as it might be loaded from the question prop
});

const addOption = () => {
  const nextLetter = String.fromCharCode(65 + editableQuestion.options.length);
  editableQuestion.options.push({ option_letter: nextLetter, option_text: '', is_correct: false });
};

const removeOption = (index) => {
  editableQuestion.options.splice(index, 1);
  // Re-letter options after removal
  editableQuestion.options.forEach((option, i) => {
    option.option_letter = String.fromCharCode(65 + i);
  });
};

const validateForm = () => {
  let isValid = true;
  errors.exam_subject_id = '';
  errors.topic_id = '';
  errors.question_text = '';
  errors.question_type = '';
  errors.correct_answer = '';
  errors.options = '';
  errors.general = '';

  if (!editableQuestion.exam_subject_id) {
    errors.exam_subject_id = 'Exam Subject is required.';
    isValid = false;
  }
  if (!editableQuestion.question_text.trim()) {
    errors.question_text = 'Question text is required.';
    isValid = false;
  }
  if (!editableQuestion.question_type) {
    errors.question_type = 'Question type is required.';
    isValid = false;
  }
  if (!editableQuestion.correct_answer.trim()) {
    errors.correct_answer = 'Correct answer is required.';
    isValid = false;
  }

  if (editableQuestion.options.length === 0) {
    errors.options = 'At least one option is required.';
    isValid = false;
  }

  let hasCorrectOption = false;
  const optionLetters = new Set();
  for (const option of editableQuestion.options) {
    if (!option.option_letter.trim() || !option.option_text.trim()) {
      errors.options = 'All option letters and texts are required.';
      isValid = false;
    }
    if (optionLetters.has(option.option_letter.trim().toUpperCase())) {
      errors.options = `Duplicate option letter found: ${option.option_letter.trim().toUpperCase()}.`;
      isValid = false;
    }
    optionLetters.add(option.option_letter.trim().toUpperCase());

    if (option.is_correct) {
      hasCorrectOption = true;
    }
  }

  if (editableQuestion.question_type === 'multiple_choice' && !hasCorrectOption) {
    errors.options = 'Multiple choice questions must have at least one correct option.';
    isValid = false;
  }

  if (editableQuestion.question_type === 'multiple_choice' && !optionLetters.has(editableQuestion.correct_answer.trim().toUpperCase())) {
    errors.correct_answer = 'Correct answer must match one of the option letters.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = async () => {
  if (!validateForm()) {
    return;
  }

  loading.value = true;
  try {
    const payload = {
      ...editableQuestion,
      options: editableQuestion.options.map(opt => ({
        ...opt,
        is_correct: opt.is_correct ? 1 : 0 // Convert boolean to integer for backend
      })),
    };
    const response = await updateQuestion(editableQuestion.question_id, payload);
    if (response.status === 200) {
      emit('question-updated');
    } else {
      console.warn('Unexpected response status:', response.status, response.data);
      errors.general = response.data?.error || 'An unexpected error occurred.';
    }
  } catch (err) {
    console.error('Error updating question:', err);
    if (err.response && err.response.data) {
      errors.general = err.response.data.error || 'Failed to update question. Please try again.';
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