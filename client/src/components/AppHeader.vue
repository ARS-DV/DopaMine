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

function closeDropdown() {
  dropdown.value = false
}
</script>

<template>
  <header class="app-header">
    <div class="header-inner">

      <!-- logo -->
      <RouterLink to="/" class="header-logo">
        DopaMine
      </RouterLink>

      <!-- sin login -->
      <nav v-if="!userStore.isLogged()" class="header-nav">
        <RouterLink to="/login"  class="btn-dopamine btn-dopamine-ghost">Sign In</RouterLink>
        <RouterLink to="/singup" class="btn-dopamine btn-dopamine-primary">Sign Up</RouterLink>
      </nav>

      <!-- con login -->
      <nav v-else class="header-nav">

        <!-- DROPDOWN VISTAS -->
        <div class="header-dropdown-wrap">
          <button
            class="btn-dopamine btn-dopamine-ghost header-dropdown-btn"
            @click="dropdown = !dropdown"
          >
            Views
            <i class="bi" :class="dropdown ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
          </button>

          <ul v-if="dropdown" class="header-dropdown-menu">
            <li>
              <RouterLink to="/" @click="closeDropdown">
                <i class="bi bi-sun"></i> Today
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/tasks" @click="closeDropdown">
                <i class="bi bi-check2-square"></i> Tasks
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/habits" @click="closeDropdown">
                <i class="bi bi-arrow-repeat"></i> Habits
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/routines" @click="closeDropdown">
                <i class="bi bi-list-check"></i> Routines
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/calendar" @click="closeDropdown">
                <i class="bi bi-calendar3"></i> Calendar
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/progress" @click="closeDropdown">
                <i class="bi bi-bar-chart"></i> Progress
              </RouterLink>
            </li>
            <li v-if="userStore.isAdmin()">
              <RouterLink to="/admin" @click="closeDropdown">
                <i class="bi bi-gear"></i> Admin
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- nombre usuario -->
        <span class="header-username">
          {{ userStore.user.nickName }}
        </span>

        <!-- logout -->
        <button class="btn-dopamine btn-dopamine-ghost" @click="logout">
          <i class="bi bi-box-arrow-right"></i> Sign Out
        </button>

      </nav>
    </div>
  </header>
</template>

<style scoped>
.app-header {
  background-color: var(--cinnamon-dark);
  height: 60px;
  position: sticky;
  top: 0;
  z-index: 1030;
  width: 100%;
}

.header-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 2rem;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}


.header-logo {
  font-family: var(--font-serif);
  font-size: 1.2rem;
  color: var(--bg-base);
  text-decoration: none;
  flex-shrink: 0;
}

.header-logo em {
  font-weight: 300;
  color: var(--vanilla-light);
  font-style: italic;
}

.header-logo:hover {
  color: var(--vanilla-light);
}


.header-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}


.app-header .btn-dopamine-ghost {
  color: var(--bg-base);
  border-color: var(--vanilla-mid);
}

.app-header .btn-dopamine-ghost:hover {
  background: #3E2010;
  border-color: var(--vanilla-light);
  color: var(--vanilla-light);
}

.app-header .btn-dopamine-primary {
  background: var(--vanilla-light);
  color: var(--cinnamon-dark);
  box-shadow: none;
}

.app-header .btn-dopamine-primary:hover {
  background: var(--bg-base);
  color: var(--cinnamon-dark);
  transform: none;
}


.app-header a.btn-dopamine-ghost {
  color: var(--bg-base);
}


.header-username {
  font-size: 0.75rem;
  color: var(--vanilla-light);
}


.header-dropdown-wrap {
  position: relative;
}

.header-dropdown-btn {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.header-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 10px;
  overflow: hidden;
  min-width: 170px;
  box-shadow: 0 8px 24px rgba(92, 51, 23, 0.15);
  list-style: none;
  padding: 0;
  margin: 0;
  z-index: 100;
}

.header-dropdown-menu li {
  border-bottom: 1px solid var(--bg-subtle);
}

.header-dropdown-menu li:last-child {
  border-bottom: none;
}

.header-dropdown-menu a {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 1rem;
  font-family: var(--font-mono);
  font-size: 0.72rem;
  color: var(--cinnamon-dark);
  text-decoration: none;
  transition: background 0.1s;
}

.header-dropdown-menu a:hover {
  background: var(--bg-base);
}

.header-dropdown-menu a.router-link-active {
  background: var(--vanilla-light);
  font-weight: 500;
}

/* RESPONSIVE */
@media (max-width: 576px) {
  .header-inner {
    padding: 0 1rem;
  }

  .header-username {
    display: none;
  }
}
</style>