<template>
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
      <h5 class="mb-0">Add New Question</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="handleSubmit">
        <div class="mb-3">
          <label for="examSubjectSelect" class="form-label">Exam Subject <span class="text-danger">*</span></label>
          <select
            class="form-select"
            :class="{ 'is-invalid': errors.exam_subject_id }"
            id="examSubjectSelect"
            v-model="question.exam_subject_id"
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
          <label for="topicSelect" class="form-label">Topic (Optional)</label>
          <select
            class="form-select"
            :class="{ 'is-invalid': errors.topic_id }"
            id="topicSelect"
            v-model="question.topic_id"
          >
            <option value="" selected>Select a Topic</option>
            <option v-for="topic in topics" :key="topic.topic_id" :value="topic.topic_id">
              {{ topic.topic_name }}
            </option>
          </select>
          <div class="invalid-feedback" v-if="errors.topic_id">{{ errors.topic_id }}</div>
        </div>

        <div class="mb-3">
          <label for="questionText" class="form-label">Question Text <span class="text-danger">*</span></label>
          <textarea
            class="form-control"
            :class="{ 'is-invalid': errors.question_text }"
            id="questionText"
            rows="4"
            v-model="question.question_text"
            required
          ></textarea>
          <div class="invalid-feedback" v-if="errors.question_text">{{ errors.question_text }}</div>
        </div>

        <div class="mb-3">
          <label for="questionType" class="form-label">Question Type <span class="text-danger">*</span></label>
          <select
            class="form-select"
            :class="{ 'is-invalid': errors.question_type }"
            id="questionType"
            v-model="question.question_type"
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
          <label for="correctAnswer" class="form-label">Correct Answer (Option Letter for MC, Text for SA) <span class="text-danger">*</span></label>
          <input
            type="text"
            class="form-control"
            :class="{ 'is-invalid': errors.correct_answer }"
            id="correctAnswer"
            v-model="question.correct_answer"
            required
          >
          <div class="invalid-feedback" v-if="errors.correct_answer">{{ errors.correct_answer }}</div>
        </div>

        <div class="mb-3">
          <label for="explanation" class="form-label">Explanation (Optional)</label>
          <textarea
            class="form-control"
            :class="{ 'is-invalid': errors.explanation }"
            id="explanation"
            rows="2"
            v-model="question.explanation"
          ></textarea>
          <div class="invalid-feedback" v-if="errors.explanation">{{ errors.explanation }}</div>
        </div>

        <div class="mb-3">
          <label for="difficultyLevel" class="form-label">Difficulty Level (Optional)</label>
          <input
            type="text"
            class="form-control"
            :class="{ 'is-invalid': errors.difficulty_level }"
            id="difficultyLevel"
            v-model="question.difficulty_level"
          >
          <div class="invalid-feedback" v-if="errors.difficulty_level">{{ errors.difficulty_level }}</div>
        </div>

        <hr>
        <h5>Options <span class="text-danger">*</span></h5>
        <div v-for="(option, index) in question.options" :key="index" class="row mb-2 align-items-center">
          <div class="col-2">
            <label :for="`optionLetter-${index}`" class="form-label">Letter</label>
            <input type="text" class="form-control" :id="`optionLetter-${index}`" v-model="option.option_letter" required>
          </div>
          <div class="col-8">
            <label :for="`optionText-${index}`" class="form-label">Text</label>
            <input type="text" class="form-control" :id="`optionText-${index}`" v-model="option.option_text" required>
          </div>
          <div class="col-1 d-flex align-items-end">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" v-model="option.is_correct" :id="`isCorrect-${index}`">
              <label class="form-check-label" :for="`isCorrect-${index}`">Correct</label>
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
            {{ loading ? 'Adding...' : 'Add Question' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { createQuestion } from '../../services/questionService';
import { getExamSubjects } from '../../api'; // Assuming this fetches exam subjects
import { getTopics } from '../../services/topicService'; // Assuming this fetches topics

const emit = defineEmits(['question-added', 'cancel']);

const question = reactive({
  exam_subject_id: '',
  topic_id: null,
  question_text: '',
  question_type: '',
  correct_answer: '',
  explanation: '',
  difficulty_level: '',
  options: [
    { option_letter: 'A', option_text: '', is_correct: false },
    { option_letter: 'B', option_text: '', is_correct: false },
    { option_letter: 'C', option_text: '', is_correct: false },
    { option_letter: 'D', option_text: '', is_correct: false },
  ],
});

const examSubjects = ref([]);
const topics = ref([]);
const loading = ref(false);
const errors = reactive({});

const fetchDependencies = async () => {
  try {
    const [examSubjectsRes, topicsRes] = await Promise.all([
      getExamSubjects({ active_only: true }), // Fetch only active exam subjects
      getTopics(1, 100, null, true) // Fetch only active topics
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

onMounted(fetchDependencies);

// Watch for changes in exam_subject_id to filter topics
watch(() => question.exam_subject_id, async (newExamSubjectId) => {
  // Clear existing topic selection and list
  question.topic_id = null;
  topics.value = [];

  if (newExamSubjectId) {
    // Find the selected exam subject from the fetched list
    const selectedExamSubject = examSubjects.value.find(es => es.exam_subject_id === newExamSubjectId);

    if (selectedExamSubject && selectedExamSubject.subject_id) {
      try {
        // Fetch topics using the correct subject_id
        const response = await getTopics(1, 100, selectedExamSubject.subject_id, true);
        if (response && response.data && Array.isArray(response.data.data)) {
          topics.value = response.data.data;
        }
      } catch (err) {
        console.error('Error fetching topics for the selected subject:', err);
        errors.general = 'Failed to load topics for the selected subject.';
      }
    }
  }
});

const addOption = () => {
  const nextLetter = String.fromCharCode(65 + question.options.length); // A, B, C, ...
  question.options.push({ option_letter: nextLetter, option_text: '', is_correct: false });
};

const removeOption = (index) => {
  question.options.splice(index, 1);
  // Re-letter options after removal
  question.options.forEach((option, i) => {
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

  if (!question.exam_subject_id) {
    errors.exam_subject_id = 'Exam Subject is required.';
    isValid = false;
  }
  if (!question.question_text.trim()) {
    errors.question_text = 'Question text is required.';
    isValid = false;
  }
  if (!question.question_type) {
    errors.question_type = 'Question type is required.';
    isValid = false;
  }
  if (!question.correct_answer.trim()) {
    errors.correct_answer = 'Correct answer is required.';
    isValid = false;
  }

  if (question.options.length === 0) {
    errors.options = 'At least one option is required.';
    isValid = false;
  }

  let hasCorrectOption = false;
  const optionLetters = new Set();
  for (const option of question.options) {
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

  if (question.question_type === 'multiple_choice' && !hasCorrectOption) {
    errors.options = 'Multiple choice questions must have at least one correct option.';
    isValid = false;
  }

  if (question.question_type === 'multiple_choice' && !optionLetters.has(question.correct_answer.trim().toUpperCase())) {
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
      ...question,
      options: question.options.map(opt => ({
        ...opt,
        is_correct: opt.is_correct ? 1 : 0 // Convert boolean to integer for backend
      })),
    };
    const response = await createQuestion(payload);
    if (response.status === 201) {
      emit('question-added');
      // Reset form
      Object.assign(question, {
        exam_subject_id: '',
        topic_id: null,
        question_text: '',
        question_type: '',
        correct_answer: '',
        explanation: '',
        difficulty_level: '',
        options: [
          { option_letter: 'A', option_text: '', is_correct: false },
          { option_letter: 'B', option_text: '', is_correct: false },
          { option_letter: 'C', option_text: '', is_correct: false },
          { option_letter: 'D', option_text: '', is_correct: false },
        ],
      });
      Object.keys(errors).forEach(key => errors[key] = ''); // Clear errors
    } else {
      console.warn('Unexpected response status:', response.status, response.data);
      errors.general = response.data?.error || 'An unexpected error occurred.';
    }
  } catch (err) {
    console.error('Error adding question:', err);
    if (err.response && err.response.data) {
      errors.general = err.response.data.error || 'Failed to add question. Please try again.';
    } else {
      errors.general = 'Network error or server is unreachable.';
    }
  } finally {
    loading.value = false;
  }
};

const handleCancel = () => {
  emit('cancel');
  // Optionally reset form on cancel
  Object.assign(question, {
    exam_subject_id: '',
    topic_id: null,
    question_text: '',
    question_type: '',
    correct_answer: '',
    explanation: '',
    difficulty_level: '',
    options: [
      { option_letter: 'A', option_text: '', is_correct: false },
      { option_letter: 'B', option_text: '', is_correct: false },
      { option_letter: 'C', option_text: '', is_correct: false },
      { option_letter: 'D', option_text: '', is_correct: false },
    ],
  });
  Object.keys(errors).forEach(key => errors[key] = '');
};
</script>

<style scoped>
/* Add any specific styles for the form here if needed */
</style>