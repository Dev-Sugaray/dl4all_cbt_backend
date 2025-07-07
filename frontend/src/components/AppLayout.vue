<template>
  <div class="app-layout">
    <header v-if="showLayout" class="app-header">
      <!-- Header content will go here -->
      <h1>CBT App Header</h1>
    </header>

    <aside v-if="showLayout" class="app-sidebar">
      <!-- Sidebar content will go here -->
      <nav>
        <ul>
          <li><router-link to="/">Home</router-link></li>
          <!-- Other navigation links will be added later -->
        </ul>
      </nav>
    </aside>

    <main class="main-content">
      <!-- The router-view will render the current route's component here -->
      <router-view></router-view>
    </main>

    <footer v-if="showLayout" class="app-footer">
      <!-- Footer content will go here -->
      <p>&copy; 2024 CBT App</p>
    </footer>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
const route = useRoute()
const showLayout = !['login', 'register'].includes(route.name)
</script>

<style scoped>
.app-layout {
  display: grid;
  grid-template-areas:
    "header header"
    "sidebar main"
    "footer footer";
  grid-template-rows: auto 1fr auto;
  grid-template-columns: 200px 1fr; /* Sidebar width and main content */
  min-height: 100vh;
}

.app-header {
  grid-area: header;
  background-color: #f0f0f0;
  padding: 1rem;
  border-bottom: 1px solid #ccc;
}

.app-sidebar {
  grid-area: sidebar;
  background-color: #e9e9e9;
  padding: 1rem;
  border-right: 1px solid #ccc;
}

.app-sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.app-sidebar li a {
  display: block;
  padding: 0.5rem 0;
  text-decoration: none;
  color: #333;
}

.app-sidebar li a:hover {
  color: #007bff;
}

.main-content {
  grid-area: main;
  padding: 1rem;
  overflow-y: auto; /* Allows scrolling for content longer than the viewport */
}

.app-footer {
  grid-area: footer;
  background-color: #f0f0f0;
  padding: 1rem;
  text-align: center;
  border-top: 1px solid #ccc;
}
</style>
