<template>
  <nav class="admin-navigation">
    <div class="nav-header">
      <h2 class="nav-title">Admin Portal</h2>
    </div>
    
    <ul class="nav-menu">
      <li class="nav-item">
        <router-link 
          to="/" 
          class="nav-link"
          :class="{ active: $route.path === '/' }"
        >
          <i class="fas fa-home"></i>
          Home
        </router-link>
      </li>
      
      <li class="nav-item">
        <router-link 
          to="/manage-exams" 
          class="nav-link"
          :class="{ active: $route.path.startsWith('/manage-exams') }"
        >
          <i class="fas fa-file-alt"></i>
          Manage Exams
        </router-link>
      </li>
      
      <li class="nav-item">
        <router-link 
          to="/manage-subjects" 
          class="nav-link"
          :class="{ active: $route.path.startsWith('/manage-subjects') }"
        >
          <i class="fas fa-book"></i>
          Manage Subjects
        </router-link>
      </li>
      
      <li class="nav-item">
        <router-link 
          to="/manage-topics" 
          class="nav-link"
          :class="{ active: $route.path.startsWith('/manage-topics') }"
        >
          <i class="fas fa-lightbulb"></i>
          Manage Topics
        </router-link>
      </li>

      <li class="nav-item">
        <router-link 
          to="/manage-questions" 
          class="nav-link"
          :class="{ active: $route.path.startsWith('/manage-questions') }"
        >
          <i class="fas fa-question-circle"></i>
          Manage Questions
        </router-link>
      </li>

      <li class="nav-item">
        <router-link 
          to="/profile" 
          class="nav-link"
          :class="{ active: $route.path === '/profile' }"
        >
          <i class="fas fa-user"></i>
          Profile
        </router-link>
      </li>
    </ul>
    
    <div class="nav-footer">
      <button @click="handleLogout" class="btn btn-outline-danger btn-sm">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </button>
    </div>
  </nav>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const handleLogout = () => {
  authStore.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.admin-navigation {
  background-color: #f8f9fa;
  border-right: 1px solid #dee2e6;
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 1rem 0;
}

.nav-header {
  padding: 0 1rem 1rem;
  border-bottom: 1px solid #dee2e6;
  margin-bottom: 1rem;
}

.nav-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #495057;
  margin: 0;
}

.nav-menu {
  list-style: none;
  padding: 0;
  margin: 0;
  flex: 1;
}

.nav-item {
  margin-bottom: 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: #6c757d;
  text-decoration: none;
  transition: all 0.2s ease;
}

.nav-link:hover {
  background-color: #e9ecef;
  color: #495057;
}

.nav-link.active {
  background-color: #007bff;
  color: white;
}

.nav-link i {
  margin-right: 0.75rem;
  width: 1rem;
  text-align: center;
}

.nav-footer {
  padding: 1rem;
  border-top: 1px solid #dee2e6;
  margin-top: auto;
}

.nav-footer .btn {
  width: 100%;
}
</style>