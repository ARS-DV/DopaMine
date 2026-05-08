<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'

const userStore = useUserStore()
const router    = useRouter()
const dropdown  = ref(false)

function logout() {
  userStore.logout()
  router.push('/login')
}
</script>

<template>
  <nav>

    <!-- LOGO -->
    <RouterLink to="/">Dopamine</RouterLink>

    <!-- SIN SESIÓN -->
    <template v-if="!userStore.isLogged()">
      <RouterLink to="/login">Sign In</RouterLink>
      <RouterLink to="/singup">Sign Up</RouterLink>
    </template>

    <!-- CON SESIÓN -->
    <template v-else>

      <!-- DROPDOWN NAVEGACIÓN -->
      <div>
        <button @click="dropdown = !dropdown">Views ▼</button>
        <ul v-if="dropdown">
          <li><RouterLink to="/"         @click="dropdown = false">Today</RouterLink></li>
          <li><RouterLink to="/tasks"    @click="dropdown = false">Tasks</RouterLink></li>
          <li><RouterLink to="/habits"   @click="dropdown = false">Habits</RouterLink></li>
          <li><RouterLink to="/routines" @click="dropdown = false">Routines</RouterLink></li>
          <li><RouterLink to="/calendar" @click="dropdown = false">Calendar</RouterLink></li>
          <li><RouterLink to="/progress" @click="dropdown = false">Progress</RouterLink></li>
          <li v-if="userStore.isAdmin()">
            <RouterLink to="/admin"      @click="dropdown = false">Admin</RouterLink>
          </li>
        </ul>
      </div>

      <!-- USUARIO Y LOGOUT -->
      <span>{{ userStore.user.nickName }}</span>
      <button @click="logout">Sign Out</button>

    </template>

  </nav>
</template>