<template>
  <div class="student-layout">
    <header class="student-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-6">
            <h1 class="header-title">ExamFlow - Student Portal</h1>
          </div>
          <div class="col-6 text-end">
            <span class="user-info" v-if="authStore.user">
              Welcome, {{ authStore.user.full_name || authStore.user.email || authStore.user.user_id }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <div class="student-body">
      <aside class="student-sidebar">
        <StudentNavigation />
      </aside>

      <main class="student-main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/authStore'
import StudentNavigation from './StudentNavigation.vue'

const authStore = useAuthStore()
</script>

<style scoped>
.student-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.student-header {
  background-color: #007bff;
  color: white;
  padding: 1rem 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.user-info {
  font-size: 0.9rem;
  opacity: 0.9;
}

.student-body {
  display: flex;
  flex: 1;
}

.student-sidebar {
  width: 250px;
  background-color: #f8f9fa;
  border-right: 1px solid #dee2e6;
}

.student-main {
  flex: 1;
  padding: 2rem;
  background-color: #ffffff;
  overflow-y: auto;
}

/* Responsive design */
@media (max-width: 768px) {
  .student-sidebar {
    width: 200px;
  }
  
  .student-main {
    padding: 1rem;
  }
  
  .header-title {
    font-size: 1.25rem;
  }
}

@media (max-width: 576px) {
  .student-body {
    flex-direction: column;
  }
  
  .student-sidebar {
    width: 100%;
    order: 2;
  }
  
  .student-main {
    order: 1;
  }
}
</style>