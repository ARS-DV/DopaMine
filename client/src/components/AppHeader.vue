<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/userStore'

const userStore = useUserStore()
const router    = useRouter()
const route     = useRoute()

const dropdown       = ref(false)  // dropdown de vistas en desktop
const mobileMenuOpen = ref(false)  // menú hamburguesa en móvil

const avatarBase = 'http://localhost/DopaMine_Server/uploads/avatars/'

function logout() {
  mobileMenuOpen.value = false
  dropdown.value       = false
  userStore.logout()
  router.push('/login')
}

function closeDropdown() {
  dropdown.value = false
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
}

// nombre de la vista actual para el botón del dropdown
const currentViewName = computed(function() {
  let path = route.path
  if (path === '/' || path === '/home') return 'Today'
  if (path.startsWith('/tasks'))        return 'Tasks'
  if (path.startsWith('/habits'))       return 'Habits'
  if (path.startsWith('/routines'))     return 'Routines'
  if (path.startsWith('/calendar'))     return 'Calendar'
  if (path.startsWith('/progress'))     return 'Progress'
  if (path.startsWith('/admin'))        return 'Admin'
  if (path.startsWith('/profile'))      return 'Profile'
  return 'Views'
})

// URL del avatar del usuario
const avatarUrl = computed(function() {
  if (userStore.user && userStore.user.avatar) {
    return avatarBase + userStore.user.avatar
  }
  return null
})

// iniciales para el avatar por defecto
const initials = computed(function() {
  let name = userStore.user ? userStore.user.nickName : ''
  return name ? name.slice(0, 2).toUpperCase() : '?'
})
</script>

<template>
  <header class="app-header" role="banner">
    <div class="header-inner">

      <!-- LOGO -->
      <RouterLink to="/" class="header-logo" aria-label="DopaMine — go to home">
        <img
          src="../assets/logo.png"
          alt=""
          class="header-logo-img"
          aria-hidden="true"
          onerror="this.style.display='none'"
        >
        Dopa<em>Mine</em>
      </RouterLink>

      <!-- ── DESKTOP NAV (oculto en móvil) ── -->
      <nav v-if="!userStore.isLogged()" class="header-nav desktop-nav" aria-label="Authentication">
        <RouterLink to="/login"  class="btn-dopamine btn-dopamine-ghost">
          <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Sign In
        </RouterLink>
        <RouterLink to="/singup" class="btn-dopamine btn-dopamine-primary">
          <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Sign Up
        </RouterLink>
      </nav>

      <nav v-else class="header-nav desktop-nav" aria-label="Main navigation">

        <!-- DROPDOWN VISTAS -->
        <div class="header-dropdown-wrap">
          <button
            class="btn-dopamine btn-dopamine-ghost header-dropdown-btn"
            :aria-expanded="dropdown"
            aria-controls="views-menu"
            :aria-label="'Navigation menu — current view: ' + currentViewName"
            @click="dropdown = !dropdown"
          >
            <i class="bi bi-grid me-1" aria-hidden="true"></i>
            {{ currentViewName }}
            <i class="bi ms-1" :class="dropdown ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
          </button>

          <ul v-if="dropdown" id="views-menu" class="header-dropdown-menu" role="menu" aria-label="App views">
            <li role="none"><RouterLink to="/home"     role="menuitem" @click="closeDropdown"><i class="bi bi-sun" aria-hidden="true"></i> Today</RouterLink></li>
            <li role="none"><RouterLink to="/tasks"    role="menuitem" @click="closeDropdown"><i class="bi bi-check2-square" aria-hidden="true"></i> Tasks</RouterLink></li>
            <li role="none"><RouterLink to="/habits"   role="menuitem" @click="closeDropdown"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Habits</RouterLink></li>
            <li role="none"><RouterLink to="/routines" role="menuitem" @click="closeDropdown"><i class="bi bi-list-check" aria-hidden="true"></i> Routines</RouterLink></li>
            <li role="none"><RouterLink to="/calendar" role="menuitem" @click="closeDropdown"><i class="bi bi-calendar3" aria-hidden="true"></i> Calendar</RouterLink></li>
            <li role="none"><RouterLink to="/progress" role="menuitem" @click="closeDropdown"><i class="bi bi-bar-chart" aria-hidden="true"></i> Progress</RouterLink></li>
            <li v-if="userStore.isAdmin()" role="none">
              <RouterLink to="/admin" role="menuitem" @click="closeDropdown"><i class="bi bi-shield-check" aria-hidden="true"></i> Admin</RouterLink>
            </li>
          </ul>
        </div>

        <div class="header-sep" aria-hidden="true"></div>

        <!-- AVATAR + NOMBRE -->
        <RouterLink
          to="/profile"
          class="header-user-link"
          :aria-label="'Profile of ' + userStore.user.nickName"
        >
          <img v-if="avatarUrl" :src="avatarUrl" :alt="userStore.user.nickName + ' profile picture'" class="header-avatar-img">
          <div v-else class="header-avatar-initials" aria-hidden="true">{{ initials }}</div>
          <span class="header-username">{{ userStore.user.nickName }}</span>
        </RouterLink>

        <div class="header-sep" aria-hidden="true"></div>

        <!-- LOGOUT -->
        <button class="btn-dopamine btn-dopamine-ghost header-logout-btn" aria-label="Sign out" @click="logout">
          <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>
          <span class="logout-label">Sign Out</span>
        </button>

      </nav>

      <!-- ── HAMBURGUESA (solo móvil) ── -->
      <button
        v-if="userStore.isLogged()"
        class="hamburger-btn mobile-only"
        :aria-label="mobileMenuOpen ? 'Close menu' : 'Open menu'"
        :aria-expanded="mobileMenuOpen"
        aria-controls="mobile-menu"
        @click="mobileMenuOpen = !mobileMenuOpen"
      >
        <i class="bi" :class="mobileMenuOpen ? 'bi-x-lg' : 'bi-list'" aria-hidden="true"></i>
      </button>

      <!-- Botones login/singup en móvil sin login -->
      <nav v-if="!userStore.isLogged()" class="header-nav mobile-auth-nav mobile-only" aria-label="Authentication">
        <RouterLink to="/login"  class="btn-dopamine btn-dopamine-ghost btn-sm-mobile">Sign In</RouterLink>
        <RouterLink to="/singup" class="btn-dopamine btn-dopamine-primary btn-sm-mobile">Sign Up</RouterLink>
      </nav>

    </div>

    <!-- ── PANEL MÓVIL ── -->
    <nav
      v-if="mobileMenuOpen && userStore.isLogged()"
      id="mobile-menu"
      class="mobile-menu"
      aria-label="Mobile navigation"
    >
      <!-- PERFIL -->
      <RouterLink to="/profile" class="mobile-profile-row" @click="closeMobileMenu">
        <img v-if="avatarUrl" :src="avatarUrl" :alt="userStore.user.nickName + ' profile picture'" class="mobile-avatar-img">
        <div v-else class="mobile-avatar-initials" aria-hidden="true">{{ initials }}</div>
        <div>
          <div class="mobile-username">{{ userStore.user.nickName }}</div>
          <div class="mobile-profile-hint">View profile</div>
        </div>
        <i class="bi bi-chevron-right ms-auto" aria-hidden="true"></i>
      </RouterLink>

      <div class="mobile-divider" aria-hidden="true"></div>

      <!-- NAVEGACIÓN -->
      <div class="mobile-nav-links">
        <RouterLink to="/home"     class="mobile-nav-link" @click="closeMobileMenu"><i class="bi bi-sun" aria-hidden="true"></i> Today</RouterLink>
        <RouterLink to="/tasks"    class="mobile-nav-link" @click="closeMobileMenu"><i class="bi bi-check2-square" aria-hidden="true"></i> Tasks</RouterLink>
        <RouterLink to="/habits"   class="mobile-nav-link" @click="closeMobileMenu"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Habits</RouterLink>
        <RouterLink to="/routines" class="mobile-nav-link" @click="closeMobileMenu"><i class="bi bi-list-check" aria-hidden="true"></i> Routines</RouterLink>
        <RouterLink to="/calendar" class="mobile-nav-link" @click="closeMobileMenu"><i class="bi bi-calendar3" aria-hidden="true"></i> Calendar</RouterLink>
        <RouterLink to="/progress" class="mobile-nav-link" @click="closeMobileMenu"><i class="bi bi-bar-chart" aria-hidden="true"></i> Progress</RouterLink>
        <RouterLink v-if="userStore.isAdmin()" to="/admin" class="mobile-nav-link" @click="closeMobileMenu">
          <i class="bi bi-shield-check" aria-hidden="true"></i> Admin
        </RouterLink>
      </div>

      <div class="mobile-divider" aria-hidden="true"></div>

      <!-- LOGOUT -->
      <button class="mobile-logout-btn" aria-label="Sign out of your account" @click="logout">
        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i> Sign Out
      </button>
    </nav>

  </header>
</template>

<style scoped>
/* ── HEADER BASE ── */
.app-header {
  background-color: var(--cinnamon-dark);
  position: sticky;
  top: 0;
  z-index: 1030;
  width: 100%;
  box-shadow: 0 2px 12px rgba(0,0,0,0.18);
}

.header-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  height: 72px; /* un poco más grande que antes (era 64px) */
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

/* LOGO */
.header-logo {
  font-family: var(--font-serif);
  font-size: 1.35rem; /* ligeramente más grande */
  font-weight: 700;
  color: var(--bg-base);
  text-decoration: none;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.header-logo-img {
  width: 80px;
  height: 80px;
  border-radius: 6px;
  object-fit: contain;
  flex-shrink: 0;
}

.header-logo em { font-weight: 300; color: var(--vanilla-light); font-style: italic; }
.header-logo:hover { color: var(--vanilla-light); }
.header-logo:focus-visible { outline: 2px solid var(--vanilla-light); outline-offset: 4px; border-radius: 4px; }

/* NAV */
.header-nav { display: flex; align-items: center; gap: 0.75rem; }

/* SEPARADOR VERTICAL */
.header-sep { width: 1px; height: 26px; background: rgba(237, 217, 163, 0.25); flex-shrink: 0; }

/* BOTONES EN HEADER */
.app-header .btn-dopamine-ghost {
  color: var(--bg-base);
  border-color: rgba(237, 217, 163, 0.35);
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-weight: 700;
  font-size: 0.85rem;
}

.app-header .btn-dopamine-ghost:hover { background: rgba(255,255,255,0.1); border-color: var(--vanilla-light); color: var(--vanilla-light); }
.app-header .btn-dopamine-ghost:focus-visible { outline: 2px solid var(--vanilla-light); outline-offset: 3px; }

.app-header .btn-dopamine-primary { background: var(--vanilla-light); color: var(--cinnamon-dark); box-shadow: none; font-family: 'Atkinson Hyperlegible', sans-serif; font-weight: 700; }
.app-header .btn-dopamine-primary:hover { background: var(--bg-base); color: var(--cinnamon-dark); transform: none; }
.app-header a.btn-dopamine-ghost { color: var(--bg-base); }

/* DROPDOWN DESKTOP */
.header-dropdown-wrap { position: relative; }

.header-dropdown-btn { display: flex; align-items: center; gap: 0.3rem; min-width: 120px; justify-content: center; }

.header-dropdown-menu {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  overflow: hidden;
  min-width: 185px;
  box-shadow: 0 8px 28px rgba(92, 51, 23, 0.18);
  list-style: none;
  padding: 0.3rem 0;
  margin: 0;
  z-index: 200;
}

.header-dropdown-menu li { margin: 0; }

.header-dropdown-menu a {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.75rem 1.1rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.9rem; font-weight: 600; color: var(--cinnamon-dark);
  text-decoration: none; transition: background 0.1s;
}

.header-dropdown-menu a:hover { background: var(--bg-subtle); }
.header-dropdown-menu a:focus-visible { outline: 2px solid var(--cinnamon-mid); outline-offset: -2px; }

.header-dropdown-menu a.router-link-active,
.header-dropdown-menu a.router-link-exact-active {
  background: var(--vanilla-light);
  color: var(--cinnamon-dark);
  font-weight: 700;
  border-left: 3px solid var(--cinnamon-mid);
}

/* AVATAR + USERNAME */
.header-user-link {
  display: flex; align-items: center; gap: 0.6rem;
  text-decoration: none; padding: 0.3rem 0.65rem;
  border-radius: 8px; border: 1px solid transparent;
  transition: background 0.15s, border-color 0.15s;
}

.header-user-link:hover { background: rgba(255,255,255,0.1); border-color: rgba(237, 217, 163, 0.35); }
.header-user-link:focus-visible { outline: 2px solid var(--vanilla-light); outline-offset: 3px; border-radius: 8px; }

.header-avatar-img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(237, 217, 163, 0.5); flex-shrink: 0; }

.header-avatar-initials {
  width: 34px; height: 34px; border-radius: 50%;
  background: var(--cinnamon-mid); color: #fff;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.72rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid rgba(237, 217, 163, 0.5); flex-shrink: 0;
}

.header-username { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.85rem; font-weight: 700; color: var(--vanilla-light); white-space: nowrap; }

.header-logout-btn { display: flex; align-items: center; gap: 0.3rem; }

/* ── HAMBURGUESA ── */
.hamburger-btn {
  background: none;
  border: 1.5px solid rgba(237, 217, 163, 0.4);
  border-radius: 8px;
  color: var(--vanilla-light);
  font-size: 1.3rem;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s;
  flex-shrink: 0;
}

.hamburger-btn:hover { background: rgba(255,255,255,0.1); }
.hamburger-btn:focus-visible { outline: 2px solid var(--vanilla-light); outline-offset: 3px; }

/* ── PANEL MÓVIL ── */
.mobile-menu {
  background: var(--cinnamon-dark);
  border-top: 1px solid rgba(237, 217, 163, 0.15);
  padding: 0.5rem 0 1rem;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* fila de perfil */
.mobile-profile-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.9rem 1.5rem;
  text-decoration: none;
  transition: background 0.15s;
}

.mobile-profile-row:hover { background: rgba(255,255,255,0.06); }

.mobile-avatar-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(237, 217, 163, 0.4); flex-shrink: 0; }

.mobile-avatar-initials {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--cinnamon-mid); color: #fff;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid rgba(237, 217, 163, 0.4); flex-shrink: 0;
}

.mobile-username { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 1rem; font-weight: 700; color: var(--vanilla-light); }
.mobile-profile-hint { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.75rem; color: rgba(237, 217, 163, 0.5); }

.mobile-divider { height: 1px; background: rgba(237, 217, 163, 0.12); margin: 0.3rem 0; }

/* links de navegación */
.mobile-nav-links { display: flex; flex-direction: column; padding: 0.3rem 0; }

.mobile-nav-link {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1.5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 600;
  color: rgba(237, 217, 163, 0.85);
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
}

.mobile-nav-link:hover { background: rgba(255,255,255,0.06); color: var(--vanilla-light); }

.mobile-nav-link.router-link-active,
.mobile-nav-link.router-link-exact-active {
  color: var(--vanilla-light);
  font-weight: 700;
  background: rgba(255,255,255,0.07);
  border-left: 3px solid var(--vanilla-light);
}

.mobile-nav-link:focus-visible { outline: 2px solid var(--vanilla-light); outline-offset: -2px; }

/* logout móvil */
.mobile-logout-btn {
  display: flex;
  align-items: center;
  margin: 0.5rem 1.5rem 0;
  padding: 0.75rem 1rem;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(237, 217, 163, 0.2);
  border-radius: 10px;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: rgba(237, 217, 163, 0.8);
  cursor: pointer;
  width: calc(100% - 3rem);
  transition: background 0.15s;
}

.mobile-logout-btn:hover { background: rgba(255,255,255,0.1); color: var(--vanilla-light); }
.mobile-logout-btn:focus-visible { outline: 2px solid var(--vanilla-light); outline-offset: 3px; }

/* ── VISIBILIDAD POR BREAKPOINT ── */
.desktop-nav  { display: flex; }
.mobile-only  { display: none; }
.mobile-auth-nav { display: none; }

@media (max-width: 768px) {
  .header-inner { padding: 0 1rem; }
  .desktop-nav  { display: none; }
  .mobile-only  { display: flex; }
  .mobile-auth-nav { display: flex; gap: 0.5rem; }

  .btn-sm-mobile {
    font-size: 0.8rem !important;
    padding: 0.4rem 0.8rem !important;
    min-height: 40px !important;
  }
}
</style>