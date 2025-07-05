import { createRouter, createWebHashHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue' // Will create this basic view later

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  // Add other routes here later
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
