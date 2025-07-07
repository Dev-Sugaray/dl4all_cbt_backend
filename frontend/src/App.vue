<template>
  <AppLayout />
</template>

<script setup>
import AppLayout from './components/AppLayout.vue'
import { useRouter } from 'vue-router'
const router = useRouter()
router.replace({ name: 'login' })
</script>

<style>
/* Global styles can go here or in index.css */
/* For now, ensure basic HTML elements don't have weird margins/paddings */
body, #app {
  margin: 0;
  padding: 0;
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
}
</style>
