<script setup>
// imports para Vue, router y API
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

// variables reactivas
const users = ref([]);
const loading = ref(true);
const error = ref("");
const searchQuery = ref("");
const filterRole = ref("all");

// funcion para cargar todos los usuarios
function fetchUsers() {
  loading.value = true;
  error.value = "";

  let url = rutaApi + "?entity=admin&requester_id=" + userStore.user.id;

  fetch(url)
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "error") {
        error.value = data.message;
        loading.value = false;
        return;
      }
      users.value = data;
      loading.value = false;
    })
    .catch(function () {
      error.value = "Error loading users";
      loading.value = false;
    });
}

// funcion para cambiar el rol de un usuario
function toggleRole(user) {
  let newRole = user.role === "admin" ? "user" : "admin";
  let label =
    newRole === "admin" ? "promote to admin" : "remove admin role from";

  let confirmed = confirm(
    "Are you sure you want to " + label + " " + user.nickName + "?",
  );
  if (!confirmed) return;

  fetch(rutaApi + "?entity=admin&id=" + user.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ requester_id: userStore.user.id, role: newRole }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        user.role = newRole;
      } else {
        error.value = data.message;
      }
    });
}

// funcion para borrar un usuario
function deleteUser(user) {
  let confirmed = confirm(
    "DELETE account of " +
      user.nickName +
      "? This will also remove all their habits, tasks and routines. This cannot be undone.",
  );
  if (!confirmed) return;

  fetch(rutaApi + "?entity=admin&id=" + user.id, {
    method: "DELETE",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ requester_id: userStore.user.id }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        users.value = users.value.filter(function (u) {
          return u.id !== user.id;
        });
      } else {
        error.value = data.message;
      }
    });
}

// formatear fecha de registro
function formatDate(dateStr) {
  if (!dateStr) return "—";
  return new Date(dateStr).toLocaleDateString("en-GB");
}

// iniciales para el avatar
function getInitials(name) {
  if (!name) return "?";
  return name.slice(0, 2).toUpperCase();
}

// estadisticas globales calculadas 
const totalUsers = computed(function () {
  return users.value.length;
});
const totalAdmins = computed(function () {
  return users.value.filter(function (u) {
    return u.role === "admin";
  }).length;
});
const totalHabits = computed(function () {
  return users.value.reduce(function (acc, u) {
    return acc + (u.habits_count || 0);
  }, 0);
});
const totalTasks = computed(function () {
  return users.value.reduce(function (acc, u) {
    return acc + (u.tasks_count || 0);
  }, 0);
});

// lista filtrada por busqueda y rol
const filteredUsers = computed(function () {
  let list = users.value;

  if (filterRole.value === "admin") {
    list = list.filter(function (u) {
      return u.role === "admin";
    });
  } else if (filterRole.value === "user") {
    list = list.filter(function (u) {
      return u.role === "user";
    });
  }

  if (searchQuery.value.trim() !== "") {
    let q = searchQuery.value.toLowerCase();
    list = list.filter(function (u) {
      return u.nickName.toLowerCase().includes(q);
    });
  }

  return list;
});

onMounted(function () {
  // si no es admin, redirigir a home
  if (!userStore.user || userStore.user.role !== "admin") {
    router.push("/");
    return;
  }
  fetchUsers();
});
</script>

<template>
  <div class="admin-container">
    <!-- cabecera -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title">
        <em>manage the</em>
        Admin Panel
      </h1>
      <span class="admin-badge">
        <i class="bi bi-shield-check me-1" aria-hidden="true"></i> Admin
      </span>
    </div>

    <!-- error -->
    <div v-if="error" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i
      >{{ error }}
    </div>

    <!-- loading -->
    <div v-if="loading" class="loading-text" aria-live="polite">
      <div class="spinner-border spinner-border-sm me-2" role="status">
        <span class="visually-hidden">Loading users...</span>
      </div>
      Loading users...
    </div>

    <template v-else>
      <div
        class="row g-3 mb-4 fade-up delay-1"
        aria-label="Platform statistics"
      >
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-neutral">
            <div>
              <div class="stat-num">{{ totalUsers }}</div>
              <div class="stat-label">Total users</div>
            </div>
            <i class="bi bi-people stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-info">
            <div>
              <div class="stat-num">{{ totalAdmins }}</div>
              <div class="stat-label">Admins</div>
            </div>
            <i class="bi bi-shield-check stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-ok">
            <div>
              <div class="stat-num">{{ totalHabits }}</div>
              <div class="stat-label">Total habits</div>
            </div>
            <i class="bi bi-arrow-repeat stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-warn">
            <div>
              <div class="stat-num">{{ totalTasks }}</div>
              <div class="stat-label">Total tasks</div>
            </div>
            <i class="bi bi-list-check stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <!-- buscdor y filtros -->
      <div class="admin-toolbar mb-4 fade-up delay-2">
        <div class="search-wrapper">
          <i class="bi bi-search search-icon" aria-hidden="true"></i>
          <label for="user-search" class="visually-hidden"
            >Search users by nickname</label
          >
          <input
            id="user-search"
            v-model="searchQuery"
            type="search"
            class="form-control dopamine-input search-input"
            placeholder="Search by nickname..."
            aria-label="Search users by nickname"
          />
        </div>
        <div class="d-flex gap-2" role="group" aria-label="Filter by role">
          <button
            class="filter-tab"
            :class="filterRole === 'all' ? 'active' : ''"
            @click="filterRole = 'all'"
          >
            All ({{ totalUsers }})
          </button>
          <button
            class="filter-tab"
            :class="filterRole === 'user' ? 'active' : ''"
            @click="filterRole = 'user'"
          >
            Users
          </button>
          <button
            class="filter-tab"
            :class="filterRole === 'admin' ? 'active' : ''"
            @click="filterRole = 'admin'"
          >
            Admins
          </button>
        </div>
      </div>

      <!-- lista vacia -->
      <div v-if="filteredUsers.length === 0" class="empty-state fade-up">
        <i class="bi bi-people empty-icon" aria-hidden="true"></i>
        <p class="empty-title">No users found</p>
      </div>

      <!-- lista de usuarios -->
      <div
        v-else
        class="d-flex flex-column gap-3"
        role="list"
        aria-label="User list"
      >
        <article
          v-for="(user, index) in filteredUsers"
          :key="user.id"
          class="user-card fade-up"
          :class="user.role === 'admin' ? 'card-admin' : 'card-user'"
          :style="{ animationDelay: index * 0.04 + 's' }"
          role="listitem"
        >
          <div class="user-card-body">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
              <!-- avatar -->
              <div
                class="user-avatar"
                :class="user.role === 'admin' ? 'avatar-admin' : 'avatar-user'"
                :aria-label="'Avatar of ' + user.nickName"
              >
                {{ getInitials(user.nickName) }}
              </div>

              <!-- info -->
              <div class="flex-grow-1" style="min-width: 0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                  <span class="user-name">{{ user.nickName }}</span>
                  <span
                    class="bdg"
                    :class="user.role === 'admin' ? 'bdg-info' : 'bdg-daily'"
                  >
                    <i
                      class="bi me-1"
                      :class="
                        user.role === 'admin' ? 'bi-shield-check' : 'bi-person'
                      "
                      aria-hidden="true"
                    ></i>
                    {{ user.role }}
                  </span>
                  <span
                    v-if="user.id === userStore.user.id"
                    class="bdg bdg-done"
                  >
                    <i class="bi bi-star me-1" aria-hidden="true"></i>You
                  </span>
                </div>
                <div class="user-meta">
                  <span>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    Registered {{ formatDate(user.createdDate) }}
                  </span>
                  <span class="meta-sep" aria-hidden="true">·</span>
                  <span :aria-label="user.habits_count + ' habits'">
                    <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                    {{ user.habits_count }} habits
                  </span>
                  <span class="meta-sep" aria-hidden="true">·</span>
                  <span :aria-label="user.tasks_count + ' tasks'">
                    <i class="bi bi-check2-square me-1" aria-hidden="true"></i>
                    {{ user.tasks_count }} tasks
                  </span>
                  <span class="meta-sep" aria-hidden="true">·</span>
                  <span :aria-label="user.routines_count + ' routines'">
                    <i class="bi bi-list-check me-1" aria-hidden="true"></i>
                    {{ user.routines_count }} routines
                  </span>
                </div>
              </div>
            </div>

            <!-- acciones -->
            <div
              v-if="user.id !== userStore.user.id"
              class="d-flex gap-2 flex-shrink-0"
            >
              <button
                class="btn-dopamine btn-dopamine-ghost admin-action-btn"
                :aria-label="
                  (user.role === 'admin'
                    ? 'Remove admin role from '
                    : 'Promote to admin: ') + user.nickName
                "
                :title="user.role === 'admin' ? 'Remove admin' : 'Make admin'"
                @click="toggleRole(user)"
              >
                <i
                  class="bi"
                  :class="
                    user.role === 'admin' ? 'bi-person-dash' : 'bi-shield-plus'
                  "
                  aria-hidden="true"
                ></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-danger admin-action-btn"
                :aria-label="'Delete account of ' + user.nickName"
                title="Delete user"
                @click="deleteUser(user)"
              >
                <i class="bi bi-trash" aria-hidden="true"></i>
              </button>
            </div>

            <!-- indicadior visual de que eres tu -->
            <div v-else class="you-indicator" aria-hidden="true">
              <i class="bi bi-lock me-1"></i> Your account
            </div>
          </div>
        </article>
      </div>
    </template>
  </div>
</template>

<style scoped>
.admin-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

@media (max-width: 768px) {
  .admin-container {
    padding: 1.5rem 1rem 4rem;
  }
}

.admin-badge {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.82rem;
  font-weight: 700;
  color: #2a5068;
  background: var(--state-info-bg);
  border: 1px solid var(--btn-info);
  border-radius: 20px;
  padding: 0.3rem 0.9rem;
  display: flex;
  align-items: center;
}

/* barra de herramientas */
.admin-toolbar {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.search-wrapper {
  position: relative;
  flex-grow: 1;
  min-width: 200px;
}

.search-icon {
  position: absolute;
  left: 0.9rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--cinnamon-soft);
  font-size: 0.9rem;
  pointer-events: none;
}

.search-input {
  padding-left: 2.4rem !important;
  min-height: 44px;
  font-size: 0.95rem !important;
}

/* card del usuario */
.user-card {
  border-radius: 12px;
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  background: var(--bg-base);
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.06);
  transition: box-shadow 0.15s;
}

.user-card:hover {
  box-shadow: 0 4px 16px rgba(92, 51, 23, 0.11);
}

.card-admin {
  border-left-color: var(--btn-info);
  background: var(--state-info-bg);
}
.card-user {
  border-left-color: var(--vanilla-mid);
}

.user-card-body {
  padding: 1rem 1.3rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* avatar */
.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  flex-shrink: 0;
}

.avatar-admin {
  background: var(--btn-info);
  color: #fff;
}
.avatar-user {
  background: var(--vanilla-mid);
  color: var(--cinnamon-dark);
}

/* nombre */
.user-name {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 1rem;
  font-weight: 800;
  color: var(--cinnamon-dark);
}

/* meta */
.user-meta {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--cinnamon-soft);
  margin-top: 0.25rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
}

.meta-sep {
  color: var(--vanilla-mid);
}

/* botones de accion */
.admin-action-btn {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  border-radius: 10px;
  flex-shrink: 0;
}

.admin-action-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
}

/* indicadores */
.you-indicator {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--cinnamon-soft);
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0 0.4rem;
}
</style>
