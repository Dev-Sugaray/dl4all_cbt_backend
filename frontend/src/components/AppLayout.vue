<template>
  <div class="app-layout">
    <header class="app-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-6">
            <h1 class="header-title">ExamFlow - Admin Portal</h1>
          </div>
          <div class="col-6 text-end">
            <span class="user-info" v-if="authStore.user">
              Welcome, {{ authStore.user.full_name || authStore.user.email || authStore.user.user_id }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <div class="app-body">
      <aside class="app-sidebar">
        <AdminNavigation />
      </aside>

      <main class="app-main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/authStore'
import AdminNavigation from './AdminNavigation.vue'

const authStore = useAuthStore()
</script>

<style scoped>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.app-header {
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

.app-body {
  display: flex;
  flex: 1;
}

.app-sidebar {
  width: 250px;
  background-color: #f8f9fa;
  border-right: 1px solid #dee2e6;
}

.app-main {
  flex: 1;
  padding: 2rem;
  background-color: #ffffff;
  overflow-y: auto;
}

/* Responsive design */
@media (max-width: 768px) {
  .app-sidebar {
    width: 200px;
  }
  
  .app-main {
    padding: 1rem;
  }
  
  .header-title {
    font-size: 1.25rem;
  }
}

@media (max-width: 576px) {
  .app-body {
    flex-direction: column;
  }
  
  .app-sidebar {
    width: 100%;
    order: 2;
  }
  
  .app-main {
    order: 1;
  }
}
</style>